<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkEnsureImportSchema($pdo);
wkEnsureKoSchema($pdo);

$importId = isset($_GET['import_id']) ? (int) $_GET['import_id'] : (int) ($_POST['import_id'] ?? 0);
if ($importId <= 0) {
    http_response_code(400);
    exit('Ongeldig import_id');
}

$importStmt = $pdo->prepare('SELECT * FROM prediction_imports WHERE id = ? LIMIT 1');
$importStmt->execute([$importId]);
$import = $importStmt->fetch();
if (!$import) {
    http_response_code(404);
    exit('Import niet gevonden');
}

$participants = $pdo->query('SELECT id, name FROM participants ORDER BY name ASC')->fetchAll();
$roundLabels = wkKoImportRoundLabels();
$expectedCounts = wkKoImportRoundExpectedCounts();
$teams = array_map(static fn(array $team): string => (string) ($team['name_de'] ?? $team['name_en'] ?? ''), wkQualifiedTeams($pdo));
$ocrPrefillText = (string) ($_POST['ocr_prefill_text'] ?? ($import['extracted_text'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participantId = (int) ($_POST['participant_id'] ?? 0);
    $extractedName = trim((string) ($_POST['extracted_name'] ?? ''));
    $rows = $_POST['rows'] ?? [];
    $ocrPrefillText = trim((string) ($_POST['ocr_prefill_text'] ?? ''));

    if (isset($_POST['apply_ocr_prefill']) && $ocrPrefillText !== '') {
        $parsedSections = wkParseKoReviewTextarea($ocrPrefillText);
        foreach ($parsedSections as $roundKey => $values) {
            $rows[$roundKey] = $values;
        }
    }

    $pdo->beginTransaction();
    try {
        $updateImport = $pdo->prepare('UPDATE prediction_imports SET participant_id = :participant_id, extracted_name = :extracted_name, status = :status WHERE id = :id');
        $updateImport->execute([
            ':participant_id' => $participantId > 0 ? $participantId : null,
            ':extracted_name' => $extractedName !== '' ? $extractedName : null,
            ':status' => 'review_needed',
            ':id' => $importId,
        ]);

        $deleteRows = $pdo->prepare('DELETE FROM ko_prediction_import_rows WHERE import_id = ?');
        $deleteRows->execute([$importId]);

        $insertRow = $pdo->prepare(
            'INSERT INTO ko_prediction_import_rows (import_id, round_key, position, raw_value, normalized_team_name, confidence, status, notes) VALUES (:import_id, :round_key, :position, :raw_value, :normalized_team_name, :confidence, :status, :notes)'
        );

        foreach ($roundLabels as $roundKey => $roundLabel) {
            $values = $rows[$roundKey] ?? [];
            foreach ($values as $index => $rawValue) {
                $rawValue = trim((string) $rawValue);
                if ($rawValue === '') {
                    continue;
                }
                $normalized = wkNormalizeKoTeamName($pdo, $rawValue);
                $status = $normalized === null ? 'review_needed' : 'matched';
                $notes = $normalized === null ? 'Geen zekere landenmatch' : null;
                $insertRow->execute([
                    ':import_id' => $importId,
                    ':round_key' => $roundKey,
                    ':position' => $index + 1,
                    ':raw_value' => $rawValue,
                    ':normalized_team_name' => $normalized,
                    ':confidence' => $normalized === null ? 0.4 : 0.95,
                    ':status' => $status,
                    ':notes' => $notes,
                ]);
            }
        }

        if (isset($_POST['save_to_ko_predictions']) && $participantId > 0) {
            $deletePredictions = $pdo->prepare('DELETE FROM ko_predictions WHERE participant_id = ?');
            $deletePredictions->execute([$participantId]);

            $rowsStmt = $pdo->prepare('SELECT round_key, position, normalized_team_name FROM ko_prediction_import_rows WHERE import_id = ? AND normalized_team_name IS NOT NULL ORDER BY id ASC');
            $rowsStmt->execute([$importId]);
            $insertPrediction = $pdo->prepare('INSERT INTO ko_predictions (participant_id, round_key, team_name) VALUES (?, ?, ?)');
            foreach ($rowsStmt->fetchAll() as $row) {
                $insertPrediction->execute([
                    $participantId,
                    wkKoImportStorageRoundKey((string) $row['round_key']),
                    (string) $row['normalized_team_name'],
                ]);
            }

            $pdo->prepare("UPDATE prediction_imports SET status = 'imported', imported_at = NOW() WHERE id = ?")->execute([$importId]);
        }

        $pdo->commit();
        header('Location: ko-import-review.php?import_id=' . $importId . '&saved=1');
        exit;
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        exit('Opslaan mislukt: ' . $e->getMessage());
    }
}

$existingRowsStmt = $pdo->prepare('SELECT * FROM ko_prediction_import_rows WHERE import_id = ? ORDER BY FIELD(round_key, "last32_left","last32_right","last16","quarterfinal","semifinal","final","third_place","champion"), position ASC');
$existingRowsStmt->execute([$importId]);
$existingRows = [];
foreach ($existingRowsStmt->fetchAll() as $row) {
    $existingRows[$row['round_key']][(int) $row['position']] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($rows)) {
    foreach ($rows as $roundKey => $values) {
        foreach ((array) $values as $index => $rawValue) {
            $rawValue = trim((string) $rawValue);
            if ($rawValue === '') {
                continue;
            }
            $existingRows[$roundKey][$index + 1] = [
                'round_key' => $roundKey,
                'position' => $index + 1,
                'raw_value' => $rawValue,
                'normalized_team_name' => wkNormalizeKoTeamName($pdo, $rawValue),
                'notes' => wkNormalizeKoTeamName($pdo, $rawValue) === null ? 'Geen zekere landenmatch' : null,
            ];
        }
    }
}

$message = isset($_GET['saved']) ? 'KO-review opgeslagen.' : null;
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · KO import review', 'imports') ?>
<div class="container stack">
    <section class="panel stack">
        <div class="toolbar">
            <div>
                <h1>KO import review</h1>
                <p class="small">Controleer per ronde de uitgelezen landen, laat de normalisatie meelopen en zet daarna alles in de KO-voorspellingen.</p>
            </div>
            <a class="secondary" href="import-detail.php?id=<?= (int) $importId ?>">Terug naar import</a>
        </div>

        <?php if ($message !== null): ?>
            <div class="flash"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" class="stack">
            <input type="hidden" name="import_id" value="<?= (int) $importId ?>">

            <section class="muted-box stack">
                <div>
                    <h2>OCR prefill</h2>
                    <p class="small">Plak hier OCR-uitvoer per rondeblok. Gebruik koppen zoals <strong>16e finalisten links</strong>, <strong>16e finalisten rechts</strong>, <strong>8e finalisten</strong>, <strong>Kwartfinalisten</strong>, <strong>Halve finalisten</strong>, <strong>Finalisten</strong>, <strong>3e plaats</strong> en <strong>Wereldkampioen</strong>.</p>
                </div>
                <textarea name="ocr_prefill_text" rows="14" style="width:100%; border-radius:12px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.04); color:var(--text); padding:12px;"><?= htmlspecialchars($ocrPrefillText, ENT_QUOTES, 'UTF-8') ?></textarea>
                <div>
                    <button type="submit" name="apply_ocr_prefill" value="1" class="secondary" style="width:auto;">OCR tekst over velden verdelen</button>
                </div>
            </section>

            <div class="grid-2">
                <div>
                    <label for="participant_id">Deelnemer</label>
                    <select id="participant_id" name="participant_id">
                        <option value="0">Nog niet gekoppeld</option>
                        <?php foreach ($participants as $participant): ?>
                            <option value="<?= (int) $participant['id'] ?>" <?= (int) ($import['participant_id'] ?? 0) === (int) $participant['id'] ? 'selected' : '' ?>><?= htmlspecialchars($participant['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="extracted_name">Herkenbare naam op formulier</label>
                    <input id="extracted_name" name="extracted_name" value="<?= htmlspecialchars((string) ($import['extracted_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <?php foreach ($roundLabels as $roundKey => $roundLabel): ?>
                <?php $expected = $expectedCounts[$roundKey] ?? 0; ?>
                <section class="panel" style="padding:16px; background:rgba(255,255,255,0.03);">
                    <h2><?= htmlspecialchars($roundLabel, ENT_QUOTES, 'UTF-8') ?> <span class="small">(verwacht: <?= (int) $expected ?>)</span></h2>
                    <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px;">
                        <?php for ($i = 1; $i <= $expected; $i++): ?>
                            <?php $row = $existingRows[$roundKey][$i] ?? null; ?>
                            <div class="muted-box" style="display:grid; gap:6px;">
                                <label>Positie <?= $i ?></label>
                                <input name="rows[<?= htmlspecialchars($roundKey, ENT_QUOTES, 'UTF-8') ?>][]" value="<?= htmlspecialchars((string) ($row['raw_value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Landnaam zoals gelezen">
                                <div class="small">Match: <strong><?= htmlspecialchars((string) ($row['normalized_team_name'] ?? wkNormalizeKoTeamName($pdo, (string) ($row['raw_value'] ?? '')) ?? 'nog geen match'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                <?php if (!empty($row['notes'])): ?><div class="small" style="color:#fde68a;"><?= htmlspecialchars((string) $row['notes'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <button type="submit" class="secondary" style="width:auto;">Review opslaan</button>
                <button type="submit" name="save_to_ko_predictions" value="1" class="primary" style="width:auto;">Review opslaan en in KO-voorspellingen zetten</button>
            </div>
        </form>
    </section>
</div>
<?= wkPageShellEnd() ?>
