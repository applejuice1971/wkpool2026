<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkEnsureKoSchema($pdo);

$worldChampion = $pdo->query(
    "SELECT team_name, COUNT(*) AS total
     FROM ko_predictions
     WHERE round_key = 'champion'
     GROUP BY team_name
     ORDER BY total DESC, team_name ASC
     LIMIT 10"
)->fetchAll();

$mostPredictedWins = $pdo->query(
    "SELECT
        CASE
            WHEN pr.predicted_home_score > pr.predicted_away_score THEN ch.name_de
            WHEN pr.predicted_away_score > pr.predicted_home_score THEN ca.name_de
            ELSE 'Gleichstand'
        END AS outcome_label,
        COUNT(*) AS total
     FROM predictions pr
     JOIN matches m ON m.id = pr.match_id
     INNER JOIN countries ch ON ch.id = m.home_country_id
     INNER JOIN countries ca ON ca.id = m.away_country_id
     GROUP BY outcome_label
     ORDER BY total DESC
     LIMIT 12"
)->fetchAll();

$highScoring = $pdo->query(
    "SELECT CONCAT(ch.name_de, ' - ', ca.name_de) AS match_label,
            AVG(pr.predicted_home_score + pr.predicted_away_score) AS avg_goals
     FROM predictions pr
     JOIN matches m ON m.id = pr.match_id
     INNER JOIN countries ch ON ch.id = m.home_country_id
     INNER JOIN countries ca ON ca.id = m.away_country_id
     GROUP BY m.id, match_label
     ORDER BY avg_goals DESC
     LIMIT 8"
)->fetchAll();
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · Stats', 'stats') ?>
<main class="container stack">
    <section class="panel">
        <h1>Stats</h1>
        <p class="small">Snelle statistieken op basis van alle ingevulde voorspellingen.</p>
    </section>

    <section class="grid-2" style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px;">
        <article class="panel">
            <h2>Meest voorspelde wereldkampioen</h2>
            <?php if ($worldChampion === []): ?>
                <p>Nog geen kampioensvoorspellingen gevonden.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Land</th><th>Aantal</th></tr></thead>
                    <tbody>
                    <?php foreach ($worldChampion as $row): ?>
                        <tr><td data-label="Land"><?= htmlspecialchars($row['team_name'], ENT_QUOTES, 'UTF-8') ?></td><td data-label="Aantal"><?= (int) $row['total'] ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </article>

        <article class="panel">
            <h2>Meest voorspelde winnaars / uitkomsten</h2>
            <table>
                <thead><tr><th>Uitkomst</th><th>Aantal</th></tr></thead>
                <tbody>
                <?php foreach ($mostPredictedWins as $row): ?>
                    <tr><td data-label="Uitkomst"><?= htmlspecialchars($row['outcome_label'], ENT_QUOTES, 'UTF-8') ?></td><td data-label="Aantal"><?= (int) $row['total'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </article>

        <article class="panel" style="grid-column:1 / -1;">
            <h2>Wedstrijden met gemiddeld meeste doelpunten in de voorspellingen</h2>
            <table>
                <thead><tr><th>Wedstrijd</th><th>Gem. goals</th></tr></thead>
                <tbody>
                <?php foreach ($highScoring as $row): ?>
                    <tr><td data-label="Wedstrijd"><?= htmlspecialchars($row['match_label'], ENT_QUOTES, 'UTF-8') ?></td><td data-label="Gem. goals"><?= number_format((float) $row['avg_goals'], 2, ',', '.') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </article>
    </section>
</main>
<?= wkPageShellEnd() ?>
