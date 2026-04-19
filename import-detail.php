<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkEnsureImportSchema($pdo);

$importId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($importId <= 0) {
    http_response_code(400);
    exit('Ongeldig import-id');
}

$importStmt = $pdo->prepare(<<<SQL
SELECT
    pi.*, 
    p.name AS participant_name
FROM prediction_imports pi
LEFT JOIN participants p ON p.id = pi.participant_id
WHERE pi.id = ?
LIMIT 1
SQL);
$importStmt->execute([$importId]);
$import = $importStmt->fetch();

if (!$import) {
    http_response_code(404);
    exit('Import niet gevonden');
}

$rowsStmt = $pdo->prepare(<<<SQL
SELECT
    pir.*, 
    m.stage,
    m.home_team,
    m.away_team,
    m.match_date
FROM prediction_import_rows pir
LEFT JOIN matches m ON m.id = pir.match_id
WHERE pir.import_id = ?
ORDER BY pir.id ASC
SQL);
$rowsStmt->execute([$importId]);
$rows = $rowsStmt->fetchAll();
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · Import detail', 'home') ?>
<div class="container stack">
        <section class="panel stack">
        <div class="toolbar">
            <div>
                <h1>Import #<?= (int) $import['id'] ?></h1>
                <p class="small">Detailoverzicht van één ingelezen bestand en de herkende regels.</p>
            </div>
            <span class="badge <?= htmlspecialchars(wkStatusBadgeClass($import['status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($import['status'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <div class="grid-2">
            <div class="muted-box">
                <strong>Bestand</strong><br>
                <?= htmlspecialchars($import['source_filename'], ENT_QUOTES, 'UTF-8') ?><br>
                <span class="small"><?= htmlspecialchars($import['source_path'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="muted-box">
                <strong>Deelnemer</strong><br>
                <?= htmlspecialchars((string) ($import['participant_name'] ?: $import['extracted_name'] ?: 'Nog onbekend'), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="muted-box">
                <strong>Type</strong><br>
                <?= htmlspecialchars(strtoupper((string) $import['source_type']), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="muted-box">
                <strong>Ontvangen</strong><br>
                <?= htmlspecialchars((string) $import['created_at'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div class="muted-box">
            <strong>Opmerkingen</strong><br>
            <?= nl2br(htmlspecialchars((string) ($import['notes'] ?: 'Geen opmerkingen'), ENT_QUOTES, 'UTF-8')) ?>
        </div>

        <?php if (!empty($import['source_path']) && is_file(__DIR__ . '/' . $import['source_path'])): ?>
            <div class="muted-box">
                <strong>Bestand openen</strong><br>
                <a href="<?= htmlspecialchars($import['source_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open bestand</a>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Herkende regels</h2>
        <?php if (!$rows): ?>
            <div class="muted-box">Er zijn nog geen herkende regels voor deze import.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Ruwe regel</th>
                        <th>Wedstrijd</th>
                        <th>Voorspelling</th>
                        <th>Confidence</th>
                        <th>Opmerking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td data-label="Status"><span class="badge <?= htmlspecialchars(wkStatusBadgeClass($row['status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td data-label="Ruwe regel"><?= htmlspecialchars($row['raw_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Wedstrijd">
                                <?php if (!empty($row['match_id'])): ?>
                                    <strong><?= htmlspecialchars($row['home_team'] . ' - ' . $row['away_team'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                    <span class="small"><?= htmlspecialchars((string) $row['stage'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $row['match_date'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="small">Nog niet gematcht</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Voorspelling">
                                <?php if ($row['predicted_home_score'] !== null && $row['predicted_away_score'] !== null): ?>
                                    <strong><?= (int) $row['predicted_home_score'] ?> - <?= (int) $row['predicted_away_score'] ?></strong>
                                <?php else: ?>
                                    <span class="small">Niet herkend</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Confidence"><?= htmlspecialchars((string) $row['confidence'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Opmerking"><?= htmlspecialchars((string) ($row['notes'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Geëxtraheerde tekst</h2>
        <div class="muted-box" style="white-space: pre-wrap;"><?= htmlspecialchars((string) ($import['extracted_text'] ?: 'Nog geen tekst geëxtraheerd.'), ENT_QUOTES, 'UTF-8') ?></div>
    </section>

    <div class="mobile-tabbar">
        <a href="index.php">Home</a>
        <a href="predictions-overview.php">Voorsp.</a>
        <a class="active" href="imports-overview.php">Imports</a>
    </div>
</div>
<?= wkPageShellEnd() ?>

