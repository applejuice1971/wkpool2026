<?php
require __DIR__ . '/lib.php';
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WK Pool 2026 · Regels</title>
    <?= wkBaseStyles('#f59e0b') ?>
</head>
<body>
    <main class="container stack">
        <nav class="nav">
            <a href="index.php" class="secondary">← Home</a>
            <a href="participants.php" class="secondary">Deelnemers</a>
            <a href="matches.php" class="secondary">Wedstrijden</a>
            <a href="form-print.php" class="secondary">Printformulier</a>
            <a href="rules.php" class="primary">Regels</a>
        </nav>

        <section class="panel">
            <h1>Regels WK Pool 2026</h1>
            <p>Hier staat het puntensysteem en de speluitleg voor de pool.</p>
        </section>

        <section class="panel">
            <h2>Groepswedstrijden</h2>
            <ul>
                <li><strong>Tendens goed:</strong> 3 punten</li>
                <li><strong>Correct aantal doelpunten per land:</strong> 1 punt per land</li>
                <li><strong>Maximum per wedstrijd:</strong> 5 punten</li>
            </ul>
            <p class="small">Voorbeeld: juiste winnaar of gelijkspel = 3 punten. Daarnaast krijg je 1 punt voor elk team waarvan het exacte aantal goals correct is voorspeld.</p>
        </section>

        <section class="panel">
            <h2>Knock-outfase</h2>
            <p>Voor losse uitslagen in de knock-outfase worden geen aparte punten toegekend. De punten in de knock-outfase worden verdiend via het juist voorspellen van welke landen iedere ronde halen.</p>
            <ul>
                <li><strong>Achtste finale:</strong> 2 punten</li>
                <li><strong>Kwartfinale:</strong> 4 punten</li>
                <li><strong>Halve finale:</strong> 6 punten</li>
                <li><strong>Finale:</strong> 8 punten</li>
                <li><strong>Wereldkampioen:</strong> 10 punten</li>
            </ul>
        </section>

        <section class="panel">
            <h2>Administratief</h2>
            <ul>
                <li>Voorspellingen moeten binnen zijn <strong>voor de start van het toernooi</strong>.</li>
                <li>Eenmaal ingeleverd is het formulier <strong>definitief</strong>.</li>
            </ul>
        </section>
    </main>
</body>
</html>
