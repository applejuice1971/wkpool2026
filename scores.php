<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkRecalculatePredictionPoints($pdo);
$koTotals = wkKoScoreTotals($pdo);

$scoreRows = $pdo->query(
    "SELECT p.id, p.name, COALESCE(SUM(pr.points), 0) AS group_points
     FROM participants p
     LEFT JOIN predictions pr ON pr.participant_id = p.id
     GROUP BY p.id, p.name
     ORDER BY p.name ASC"
)->fetchAll();

foreach ($scoreRows as &$row) {
    $row['ko_points'] = $koTotals[(int) $row['id']] ?? 0;
    $row['total_points'] = (int) $row['group_points'] + (int) $row['ko_points'];
}
unset($row);

usort($scoreRows, static function (array $a, array $b): int {
    return [$b['total_points'], $b['group_points'], $a['name']] <=> [$a['total_points'], $a['group_points'], $b['name']];
});

$finishedMatches = $pdo->query("SELECT COUNT(*) FROM matches WHERE status = 'finished'")->fetchColumn();

$lines = [];
$rank = 1;
foreach ($scoreRows as $row) {
    $lines[] = $rank . '. ' . $row['name'] . ' - ' . (int) $row['total_points'] . ' pt (groep ' . (int) $row['group_points'] . ', KO ' . (int) $row['ko_points'] . ')';
    $rank++;
}
$copyText = "WK Pool 2026 stand\n" .
    'Gespeelde groepswedstrijden: ' . (int) $finishedMatches . "\n\n" .
    implode("\n", $lines);
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · Scores', 'scores') ?>
<main class="container stack">
    <section class="panel">
        <div class="toolbar">
            <div>
                <h1>Scores</h1>
                <p class="small">Puntentelling op basis van de ingevulde uitslagen. Nu berekend over <strong><?= (int) $finishedMatches ?></strong> gespeelde wedstrijden.</p>
            </div>
            <button type="button" class="secondary" style="width:auto;" onclick="copyScoreText()">Kopieer tekst</button>
        </div>

        <div class="grid-2" style="align-items:start;">
            <div class="panel" style="padding:18px; background:rgba(255,255,255,0.03);">
                <h2>Ranglijst</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Deelnemer</th>
                            <th>Groep</th>
                            <th>KO</th>
                            <th>Totaal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scoreRows as $index => $row): ?>
                            <tr>
                                <td data-label="#"><?= $index + 1 ?></td>
                                <td data-label="Deelnemer"><strong><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td data-label="Groep"><?= (int) $row['group_points'] ?></td>
                                <td data-label="KO"><?= (int) $row['ko_points'] ?></td>
                                <td data-label="Totaal"><?= (int) $row['total_points'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div>
                <h2>Kopieertekst</h2>
                <p class="small">Handig om direct in WhatsApp of een andere messenger te plakken.</p>
                <textarea id="score-copy" readonly style="width:100%; min-height:420px; border-radius:16px; padding:14px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.04); color:#f3f4f6;"><?= htmlspecialchars($copyText, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
    </section>
</main>
<script>
function copyScoreText() {
    const el = document.getElementById('score-copy');
    el.select();
    el.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(el.value).then(() => {
        alert('Scoretekst gekopieerd');
    }).catch(() => {
        document.execCommand('copy');
        alert('Scoretekst gekopieerd');
    });
}
</script>
<?= wkPageShellEnd() ?>
