<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkEnsureImportSchema($pdo);

$summary = $pdo->query("SELECT COUNT(*) AS total_predictions, COUNT(DISTINCT participant_id) AS total_participants FROM predictions")->fetch();
$imports = $pdo->query("SELECT COUNT(*) AS total_imports, SUM(status = 'imported') AS imported_count, SUM(status = 'review_needed') AS review_count FROM prediction_imports")->fetch();

$rows = $pdo->query(<<<SQL
SELECT
    p.name AS participant_name,
    m.stage,
    m.match_date,
    m.home_team,
    m.away_team,
    pr.predicted_home_score,
    pr.predicted_away_score,
    pr.points,
    pr.updated_at
FROM predictions pr
INNER JOIN participants p ON p.id = pr.participant_id
INNER JOIN matches m ON m.id = pr.match_id
ORDER BY p.name ASC, m.match_date ASC, m.id ASC
SQL)->fetchAll();
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · Voorspellingen overzicht', 'home') ?>
<div class="container stack">
        <section class="panel">
        <div class="toolbar">
            <div>
                <h1>Voorspellingen overzicht</h1>
                <p class="small">Hier zie je alle voorspellingen die nu in de database staan.</p>
            </div>
            <div class="small">
                <strong><?= (int) ($summary['total_predictions'] ?? 0) ?></strong> voorspellingen, 
                <strong><?= (int) ($summary['total_participants'] ?? 0) ?></strong> deelnemers, 
                <strong><?= (int) ($imports['total_imports'] ?? 0) ?></strong> imports
            </div>
        </div>

        <?php if (!$rows): ?>
            <div class="muted-box">Er staan nog geen voorspellingen in de database.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Deelnemer</th>
                        <th>Wedstrijd</th>
                        <th>Fase</th>
                        <th>Voorspelling</th>
                        <th>Punten</th>
                        <th>Bijgewerkt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td data-label="Deelnemer"><?= htmlspecialchars($row['participant_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Wedstrijd"><?= htmlspecialchars($row['home_team'] . ' - ' . $row['away_team'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Fase"><?= htmlspecialchars($row['stage'], ENT_QUOTES, 'UTF-8') ?><br><span class="small"><?= htmlspecialchars((string) $row['match_date'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td data-label="Voorspelling"><strong><?= (int) $row['predicted_home_score'] ?> - <?= (int) $row['predicted_away_score'] ?></strong></td>
                            <td data-label="Punten"><?= (int) $row['points'] ?></td>
                            <td data-label="Bijgewerkt"><?= htmlspecialchars((string) $row['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <div class="mobile-tabbar">
        <a href="index.php">Home</a>
        <a class="active" href="predictions-overview.php">Voorsp.</a>
        <a href="imports-overview.php">Imports</a>
    </div>
</div>
<?= wkPageShellEnd() ?>

