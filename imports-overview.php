<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkEnsureImportSchema($pdo);

$imports = $pdo->query(<<<SQL
SELECT
    pi.id,
    pi.source_filename,
    pi.source_path,
    pi.source_type,
    pi.status,
    pi.extracted_name,
    pi.notes,
    pi.imported_at,
    pi.created_at,
    p.name AS participant_name,
    COUNT(pir.id) AS row_count,
    SUM(pir.status = 'review_needed') AS review_rows
FROM prediction_imports pi
LEFT JOIN participants p ON p.id = pi.participant_id
LEFT JOIN prediction_import_rows pir ON pir.import_id = pi.id
GROUP BY pi.id
ORDER BY pi.created_at DESC, pi.id DESC
SQL)->fetchAll();

$selectedImportId = isset($_GET['import_id']) ? (int) $_GET['import_id'] : (isset($imports[0]['id']) ? (int) $imports[0]['id'] : 0);
$selectedImport = null;
foreach ($imports as $candidateImport) {
    if ((int) $candidateImport['id'] === $selectedImportId) {
        $selectedImport = $candidateImport;
        break;
    }
}
if ($selectedImport === null && $imports) {
    $selectedImport = $imports[0];
    $selectedImportId = (int) $selectedImport['id'];
}
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · Ingelezen bestanden', 'imports') ?>
<div class="container stack">
        <section class="panel">
        <div class="toolbar">
            <div>
                <h1>Ingelezen bestanden</h1>
                <p class="small">Overzicht van ontvangen scans, pdf's en foto's van ingevulde formulieren.</p>
            </div>
        </div>

        <?php if (!$imports): ?>
            <div class="muted-box">Er zijn nog geen bestanden ingelezen.</div>
        <?php else: ?>
            <?php if ($selectedImport): ?>
                <div class="muted-box" style="margin-bottom:16px;">
                    <div class="toolbar" style="margin-bottom:10px;">
                        <div>
                            <strong>Geselecteerd bestand:</strong><br>
                            <span class="small"><?= htmlspecialchars($selectedImport['source_filename'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($selectedImport['participant_name'] ?: $selectedImport['extracted_name'] ?: 'Nog onbekend'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <a class="primary" href="import-detail.php?id=<?= (int) $selectedImport['id'] ?>">Bestand bekijken</a>
                        <a class="secondary" href="ko-import-ocr-helper.php?import_id=<?= (int) $selectedImport['id'] ?>">KO OCR helper</a>
                        <a class="secondary" href="ko-import-review.php?import_id=<?= (int) $selectedImport['id'] ?>">KO review</a>
                        <?php if ((int) $selectedImport['row_count'] > 0 && in_array($selectedImport['status'], ['parsed', 'review_needed'], true) && !empty($selectedImport['participant_name'])): ?>
                            <a class="secondary" href="import_predictions_from_rows.php?import_id=<?= (int) $selectedImport['id'] ?>">Groepsfase in database zetten</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>Bestand</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Deelnemer</th>
                        <th>Rijen</th>
                        <th>Ontvangen</th>
                        <th>Opmerkingen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($imports as $import): ?>
                        <?php $isSelected = (int) $import['id'] === $selectedImportId; ?>
                        <tr<?= $isSelected ? ' style="background:rgba(255,255,255,0.08);"' : '' ?>>
                            <td data-label="Gekozen"><a href="imports-overview.php?import_id=<?= (int) $import['id'] ?>" style="text-decoration:none;"><?= $isSelected ? '✅' : '⬜' ?></a></td>
                            <td data-label="Bestand">
                                <strong><?= htmlspecialchars($import['source_filename'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                <span class="small"><?= htmlspecialchars($import['source_path'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td data-label="Type"><?= htmlspecialchars(strtoupper($import['source_type']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Status"><span class="badge <?= htmlspecialchars(wkStatusBadgeClass($import['status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($import['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td data-label="Deelnemer"><?= htmlspecialchars((string) ($import['participant_name'] ?: $import['extracted_name'] ?: 'Nog onbekend'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Rijen"><?= (int) $import['row_count'] ?><?php if ((int) $import['review_rows'] > 0): ?><br><span class="small"><?= (int) $import['review_rows'] ?> controle nodig</span><?php endif; ?></td>
                            <td data-label="Ontvangen"><?= htmlspecialchars((string) $import['created_at'], ENT_QUOTES, 'UTF-8') ?><?php if ($import['imported_at']): ?><br><span class="small">Geïmporteerd: <?= htmlspecialchars((string) $import['imported_at'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></td>
                            <td data-label="Opmerkingen"><?= htmlspecialchars((string) ($import['notes'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <div class="mobile-tabbar">
        <a href="index.php">Home</a>
        <a href="predictions-overview.php">Voorsp.</a>
        <a class="active" href="imports-overview.php">Imports</a>
    </div>
</div>
<?= wkPageShellEnd() ?>

