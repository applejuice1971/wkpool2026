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
            <table>
                <thead>
                    <tr>
                        <th>Bestand</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Deelnemer</th>
                        <th>Rijen</th>
                        <th>Ontvangen</th>
                        <th>Opmerkingen</th>
                        <th>Actie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($imports as $import): ?>
                        <tr>
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
                            <td data-label="Actie">
                                <a class="secondary" href="import-detail.php?id=<?= (int) $import['id'] ?>">Bekijk</a>
                                <?php if ((int) $import['row_count'] > 0 && in_array($import['status'], ['parsed', 'review_needed'], true) && !empty($import['participant_name'])): ?>
                                    <a class="secondary" href="import_predictions_from_rows.php?import_id=<?= (int) $import['id'] ?>">Zet in database</a>
                                <?php else: ?>
                                    <span class="small">Nog niet klaar</span>
                                <?php endif; ?>
                            </td>
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

