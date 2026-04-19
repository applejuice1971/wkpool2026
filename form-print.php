<?php
require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
$participantId = isset($_GET['participant_id']) ? (int) $_GET['participant_id'] : 0;

$participants = $pdo->query('SELECT id, name FROM participants ORDER BY name ASC')->fetchAll();
$participant = null;
$matches = [];

if ($participantId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, email FROM participants WHERE id = :id');
    $stmt->execute([':id' => $participantId]);
    $participant = $stmt->fetch();

    if ($participant) {
        $matches = $pdo->query("SELECT id, stage, match_date, home_team, away_team FROM matches WHERE stage LIKE 'Group %' ORDER BY match_date ASC, id ASC")->fetchAll();
    }
}

$qualifiedTeams = [
    'Algeria','Argentina','Australia','Austria','Belgium','Brazil','Canada','Cape Verde','Colombia','Croatia','Curacao','DR Congo','Ecuador','Egypt','England','France','Germany','Ghana','Haiti','Iran','Iraq','Ivory Coast','Jamaica','Japan','Jordan','Mexico','Morocco','Netherlands','New Zealand','Norway','Panama','Paraguay','Portugal','Qatar','Saudi Arabia','Scotland','Senegal','South Africa','South Korea','Spain','Sweden','Switzerland','Tunisia','United Arab Emirates','Uruguay','USA','Uzbekistan','Bosnia and Herzegovina',
];

$koColumns = [
    '1/16' => '1/16',
    '1/8' => '1/8',
    '1/4' => '1/4',
    '1/2' => '1/2',
    'Finale' => 'FIN',
    'Kampioen' => 'WK',
];

function formatKoTeamLabel(string $team): string
{
    $presets = [
        'Saudi Arabia' => 'Saudi A.',
        'South Africa' => 'South Afr.',
        'South Korea' => 'South Kor.',
        'New Zealand' => 'New Zeal.',
        'Ivory Coast' => 'Ivory C.',
        'Cape Verde' => 'Cape V.',
        'United Arab Emirates' => 'UAE',
        'Bosnia and Herzegovina' => 'Bosnia-H.',
    ];

    if (isset($presets[$team])) {
        return $presets[$team];
    }

    if (mb_strlen($team) <= 12) {
        return $team;
    }

    return mb_substr($team, 0, 12) . '…' . mb_substr($team, -1);
}

function splitInTwo(array $items): array
{
    $half = (int) ceil(count($items) / 2);
    return [
        array_slice($items, 0, $half),
        array_slice($items, $half),
    ];
}

function splitInThree(array $items): array
{
    $third = (int) ceil(count($items) / 3);
    return [
        array_slice($items, 0, $third),
        array_slice($items, $third, $third),
        array_slice($items, $third * 2),
    ];
}

function formatGroupTeamLabel(string $team): string
{
    $presets = [
        'Inter-confederation playoff 1' => 'Int. playoff 1',
        'Inter-confederation playoff 2' => 'Int. playoff 2',
        'UEFA playoff A' => 'UEFA PO A',
        'UEFA playoff B' => 'UEFA PO B',
        'UEFA playoff C' => 'UEFA PO C',
        'UEFA playoff D' => 'UEFA PO D',
        'Saudi Arabia' => 'Saudi-Arabië',
        'South Africa' => 'Zuid-Afrika',
        'South Korea' => 'Zuid-Korea',
        'New Zealand' => 'Nieuw-Zeeland',
        'Ivory Coast' => 'Ivoorkust',
        'Cape Verde' => 'Kaapverdië',
    ];

    return $presets[$team] ?? $team;
}

[$koTeamsCol1, $koTeamsCol2, $koTeamsCol3] = splitInThree($qualifiedTeams);
?><!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WK Pool 2026 · Printformulier</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: linear-gradient(135deg, #0b1020, #172036);
            color: #e5e7eb;
            font-family: Inter, Arial, sans-serif;
        }
        .screen-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }
        .panel {
            background: rgba(15, 23, 42, 0.92);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.28);
            padding: 22px;
            margin-bottom: 18px;
        }
        .nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .nav a, .nav button, .selector button {
            display: inline-block;
            border: 0;
            border-radius: 12px;
            padding: 12px 16px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }
        .primary { background: #22c55e; color: #06230f; }
        .secondary { background: rgba(255,255,255,0.05); color: #e5e7eb; border: 1px solid rgba(255,255,255,0.10); }
        .selector { display: grid; gap: 14px; }
        .selector label, .help { color: #cbd5e1; }
        .selector select {
            width: 100%; padding: 14px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.10);
            background: rgba(255,255,255,0.04); color: #e5e7eb; font-size: 16px;
        }

        .print-sheet {
            background: white;
            color: #111827;
            border-radius: 18px;
            padding: 4mm 4mm 4mm;
            margin-bottom: 18px;
        }
        .sheet-header {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 6px;
            margin-bottom: 5px;
            align-items: start;
        }
        .title h1 { margin: 0 0 1px; font-size: 21px; line-height: 1.0; }
        .title p, .hint, .page-footer { margin: 0; color: #475569; line-height: 1.3; }
        .meta-block {
            border: 1.5px solid #0f172a; border-radius: 10px; padding: 6px;
        }
        .meta-line {
            display: grid; grid-template-columns: 58px 1fr; gap: 6px; margin-bottom: 3px; font-size: 10px;
        }
        .line-box { min-height: 14px; border-bottom: 1px solid #334155; }
        .guide {
            display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin: 0 0 8px;
        }
        .guide-card {
            border: 1.2px solid #dbe4ee;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 12px;
            line-height: 1.45;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }
        .guide-card strong {
            display: block;
            margin-bottom: 4px;
            color: #0f172a;
            font-size: 14px;
        }
        .intro-rules {
            border: 1.2px solid #cbd5e1;
            border-radius: 14px;
            padding: 12px 14px;
            margin: 0 0 12px;
            background: linear-gradient(135deg, #f8fafc 0%, #eef6ff 100%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.75);
        }
        .intro-rules-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .intro-rules-grid > div {
            background: rgba(255,255,255,0.82);
            border: 1px solid #dbe4ee;
            border-radius: 12px;
            padding: 10px 12px;
        }
        .intro-rules h2 {
            margin: 0 0 8px;
            font-size: 18px;
            color: #0f172a;
        }
        .intro-rules p,
        .intro-rules li {
            margin: 0;
            color: #334155;
            font-size: 14px;
            line-height: 1.6;
        }
        .intro-rules ul {
            margin: 8px 0 0 18px;
            padding: 0;
        }
        .intro-rules li + li {
            margin-top: 4px;
        }

        .matches-two-col {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .matches-list {
            display: grid;
            gap: 4px;
        }
        .match-row {
            display: grid;
            grid-template-columns: 30px 30px 46px minmax(0, 1fr) 50px 50px;
            align-items: center;
            gap: 4px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            padding: 3px 4px;
            page-break-inside: avoid;
            min-height: 30px;
        }
        .match-no {
            font-weight: 800;
            font-size: 14px;
        }
        .match-stage {
            font-size: 13px;
            color: #334155;
            font-weight: 800;
        }
        .match-date {
            font-size: 13px;
            color: #334155;
            line-height: 1;
            font-weight: 700;
        }
        .match-teams {
            min-width: 0;
        }
        .match-teams strong {
            display: block;
            font-size: 13px;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .score-box {
            height: 21px;
            border: 1.5px solid #0f172a;
            border-radius: 5px;
            display: block;
            font-size: 0;
            color: transparent;
            background: #fff;
            min-width: 50px;
        }

        .ko-grid3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 6px;
            margin-top: 6px;
        }
        .ko-block {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }
        .ko-head,
        .ko-row {
            display: grid;
            grid-template-columns: minmax(92px, 1fr) repeat(6, 20px);
            column-gap: 4px;
            row-gap: 0;
            align-items: center;
            justify-items: stretch;
        }
        .ko-head {
            background: #eef2f7;
            border-bottom: 1px solid #cbd5e1;
            min-height: 28px;
            padding: 2px 4px;
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
        }
        .ko-head .ko-land {
            font-size: 12px;
            line-height: 1;
        }
        .ko-row {
            min-height: 32px;
            padding: 2px 4px;
            border-bottom: 1px solid #e2e8f0;
        }
        .ko-row > :nth-child(n+2),
        .ko-head > :nth-child(n+2) {
            width: 20px;
            min-width: 20px;
            max-width: 20px;
        }
        .ko-row:last-child {
            border-bottom: 0;
        }
        .ko-land {
            font-weight: 800;
            font-size: 14px;
            line-height: 1;
            color: #111827;
            max-width: 100%;
            width: 100%;
            justify-self: start;
            text-align: left;
            padding-right: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ko-col-label {
            text-align: center;
            font-size: 9px;
            letter-spacing: -0.01em;
            width: 20px;
            justify-self: center;
        }
        .ko-box-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            justify-self: center;
        }
        .ko-mark {
            width: 14px;
            height: 14px;
            border: 2px solid #0f172a;
            border-radius: 3px;
        }
        .ko-note {
            font-size: 12px; color: #475569; margin-top: 8px;
        }
        .page-footer {
            display: flex; justify-content: space-between; gap: 12px; margin-top: 8px; font-size: 10px;
        }

        @media (max-width: 720px) {
            .screen-wrap { padding: 14px 10px 24px; }
            .panel { padding: 16px; border-radius: 18px; }
            .nav { display: grid; grid-template-columns: 1fr; }
            .nav a, .nav button, .selector button { width: 100%; text-align: center; }
            .print-sheet { padding: 14px; border-radius: 14px; }
            .sheet-header, .guide, .ko-grid3, .matches-two-col, .intro-rules-grid { grid-template-columns: 1fr; }
            .match-row {
                grid-template-columns: 1fr;
                align-items: start;
            }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm;
            }
            body { background: white; color: #111827; }
            .screen-wrap { max-width: none; margin: 0; padding: 0; }
            .panel, .nav, .selector, .screen-only { display: none !important; }
            .print-sheet { margin: 0; border-radius: 0; box-shadow: none; page-break-after: always; padding: 0; }
            .print-sheet:last-child { page-break-after: auto; }
            .title h1 {
                font-size: 18px;
            }
            .guide {
                margin-bottom: 7px;
            }
            .guide-card {
                font-size: 11px;
                padding: 8px 10px;
                border-color: #cbd5e1;
            }
            .guide-card strong {
                font-size: 13px;
            }
            .intro-rules {
                padding: 8px 10px;
                margin-bottom: 9px;
                border-color: #cbd5e1;
                background: #f8fafc;
            }
            .intro-rules-grid {
                gap: 8px;
            }
            .intro-rules-grid > div {
                padding: 8px 10px;
                border-color: #dbe4ee;
                background: #fff;
            }
            .intro-rules h2 {
                font-size: 16px;
                margin-bottom: 5px;
            }
            .intro-rules p,
            .intro-rules li {
                font-size: 12px;
                line-height: 1.5;
            }
            .matches-two-col {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 6px !important;
            }
            .matches-list {
                display: grid !important;
                gap: 3px !important;
            }
            .match-row {
                grid-template-columns: 26px 26px 40px minmax(0, 1fr) 42px 42px;
                gap: 3px;
                padding: 2px 3px;
                min-height: 25px;
            }
            .match-no,
            .match-stage,
            .match-date {
                font-size: 11px;
            }
            .match-teams strong {
                font-size: 11px;
            }
            .score-box {
                height: 18px;
                min-width: 42px;
            }
            .ko-grid3 {
                gap: 4px;
            }
            .ko-head,
            .ko-row {
                grid-template-columns: minmax(76px, 1fr) repeat(6, 16px);
                column-gap: 3px;
                padding: 2px 3px;
            }
            .ko-row > :nth-child(n+2),
            .ko-head > :nth-child(n+2) {
                width: 16px;
                min-width: 16px;
                max-width: 16px;
            }
            .ko-land {
                font-size: 11px;
            }
            .ko-col-label {
                font-size: 8px;
                width: 16px;
            }
            .ko-box-wrap {
                width: 16px;
                height: 16px;
            }
            .ko-mark {
                width: 11px;
                height: 11px;
                border-width: 1.6px;
            }
            .ko-note,
            .page-footer {
                font-size: 9px;
                margin-top: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="screen-wrap">
        <div class="panel screen-only">
            <nav class="nav">
                <a href="index.php" class="secondary">← Home</a>
                <a href="participants.php" class="secondary">Deelnemers</a>
                <a href="matches.php" class="secondary">Wedstrijden</a>
                <?php if ($participant): ?><button class="primary" onclick="window.print()">Print formulier</button><?php endif; ?>
            </nav>
            <div class="selector">
                <h1>Printbaar invulformulier</h1>
                <p class="help">Groepsfase in 3 kolommen en knock-outfase als matrixbord met landen links en rondes bovenaan.</p>
                <form method="get">
                    <div>
                        <label for="participant_id">Kies deelnemer</label>
                        <select id="participant_id" name="participant_id" required>
                            <option value="">Selecteer deelnemer…</option>
                            <?php foreach ($participants as $item): ?>
                                <option value="<?= (int) $item['id'] ?>" <?= $participantId === (int) $item['id'] ? 'selected' : '' ?>><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><button type="submit" class="primary">Formulier genereren</button></div>
                </form>
            </div>
        </div>

        <?php if (!$participant): ?>
            <div class="panel screen-only"><p>Kies hierboven een deelnemer om het formulier te genereren.</p></div>
        <?php else: ?>
            <section class="print-sheet">
                <header class="sheet-header">
                    <div class="title">
                        <h1>WK Pool 2026</h1>
                        <p>Groepsfase voorspellingen</p>
                    </div>
                    <div class="meta-block">
                        <div class="meta-line"><strong>Naam</strong><div class="line-box"><?= htmlspecialchars($participant['name'], ENT_QUOTES, 'UTF-8') ?></div></div>
                        <div class="meta-line"><strong>ID</strong><div class="line-box">P-<?= str_pad((string) $participant['id'], 3, '0', STR_PAD_LEFT) ?></div></div>
                        <div class="meta-line"><strong>Versie</strong><div class="line-box">Form scan v1</div></div>
                    </div>
                </header>

                <div class="intro-rules">
                    <div class="intro-rules-grid">
                        <div>
                            <h2>Spelregels (NL)</h2>
                            <p>Leuk dat je meedoet aan de WK Pool 2026. Vul je voorspellingen duidelijk in en lever het formulier op tijd in.</p>
                            <ul>
                                <li>Inleggeld: <strong>€3 per persoon</strong></li>
                                <li>Prijzenpot: <strong>1e 50%</strong>, <strong>2e 30%</strong>, <strong>3e 20%</strong></li>
                                <li>Groepsfase: 3 punten voor de juiste tendens, plus 1 punt per exact goed voorspeld doelsaldo per team</li>
                                <li>Knock-outfase: alleen bonuspunten per correct voorspelde ronde</li>
                                <li>Na inleveren blijft je formulier definitief</li>
                            </ul>
                        </div>
                        <div>
                            <h2>Spielregeln (DE)</h2>
                            <p>Schön, dass du bei der WM Pool 2026 mitmachst. Bitte trage deine Tipps gut lesbar ein und gib das Formular rechtzeitig ab.</p>
                            <ul>
                                <li>Einsatz: <strong>3 € pro Person</strong></li>
                                <li>Preistopf: <strong>1. Platz 50%</strong>, <strong>2. Platz 30%</strong>, <strong>3. Platz 20%</strong></li>
                                <li>Gruppenphase: 3 Punkte für die richtige Tendenz, plus 1 Punkt pro exakt richtig getipptem Torwert je Team</li>
                                <li>K.-o.-Phase: nur Bonuspunkte pro korrekt vorhergesagter Runde</li>
                                <li>Nach der Abgabe bleibt dein Formular verbindlich</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="guide">
                    <div class="guide-card"><strong>Invullen</strong>Gebruik alleen cijfers. Schrijf één cijfer per vakje, links thuisscore en rechts uitscore.</div>
                    <div class="guide-card"><strong>Ausfüllen</strong>Verwende nur Zahlen. Schreibe pro Kästchen genau eine Zahl, links Heimtore und rechts Auswärtstore.</div>
                    <div class="guide-card"><strong>Scanvriendelijk</strong>Eén wedstrijd per regel. Laat datum en scorevakjes vrij van extra tekst of markeringen.</div>
                    <div class="guide-card"><strong>Scanfreundlich</strong>Nur ein Spiel pro Zeile. Lass Datums- und Ergebnisfelder frei von zusätzlichem Text oder Markierungen.</div>
                </div>

                <div class="matches-two-col">
                    <?php [$allLeftMatches, $allRightMatches] = splitInTwo($matches); ?>
                    <?php foreach ([$allLeftMatches, $allRightMatches] as $columnIndex => $columnMatches): ?>
                        <div class="matches-list">
                            <?php foreach ($columnMatches as $matchIndex => $match): ?>
                                <?php $absoluteIndex = $columnIndex === 0 ? ($matchIndex + 1) : (count($allLeftMatches) + $matchIndex + 1); ?>
                                <div class="match-row">
                                    <div class="match-no"><?= $absoluteIndex ?></div>
                                    <div class="match-stage"><?= htmlspecialchars(str_replace('Group ', 'G', $match['stage']), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="match-date"><?= htmlspecialchars(date('d-m', strtotime($match['match_date'])), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="match-teams">
                                        <strong><?= htmlspecialchars(formatGroupTeamLabel($match['home_team']), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(formatGroupTeamLabel($match['away_team']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="score-box"></div>
                                    <div class="score-box"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <footer class="page-footer">
                    <span>Schrijf duidelijk met donkere pen. Wedstrijdnummer en teamnamen niet overschrijven.</span>
                    <span>P-<?= str_pad((string) $participant['id'], 3, '0', STR_PAD_LEFT) ?></span>
                </footer>
            </section>

            <section class="print-sheet">
                <header class="sheet-header">
                    <div class="title">
                        <h1>WK Pool 2026</h1>
                        <p>Knock-outmatrix compact</p>
                    </div>
                    <div class="meta-block">
                        <div class="meta-line"><strong>Naam</strong><div class="line-box"><?= htmlspecialchars($participant['name'], ENT_QUOTES, 'UTF-8') ?></div></div>
                        <div class="meta-line"><strong>ID</strong><div class="line-box">P-<?= str_pad((string) $participant['id'], 3, '0', STR_PAD_LEFT) ?></div></div>
                        <div class="meta-line"><strong>Doel</strong><div class="line-box">Aankruisen per ronde</div></div>
                    </div>
                </header>

                <p class="hint">Per regel links een land met KO-voorspellingen en rechts nog een land met dezelfde kolommen.</p>

                <div class="ko-grid3">
                    <?php foreach ([$koTeamsCol1, $koTeamsCol2, $koTeamsCol3] as $koBlockTeams): ?>
                        <div class="ko-block">
                            <div class="ko-head">
                                <div class="ko-land">Land</div>
                                <?php foreach ($koColumns as $label): ?>
                                    <div class="ko-col-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endforeach; ?>
                            </div>
                            <?php foreach ($koBlockTeams as $team): ?>
                                <div class="ko-row">
                                    <div class="ko-land"><?= htmlspecialchars(formatKoTeamLabel($team), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php foreach ($koColumns as $label): ?>
                                        <div class="ko-box-wrap"><div class="ko-mark"></div></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p class="ko-note">Voor snelle controle kun je dit later eenvoudig vergelijken met OCR/handmatige verificatie per rij en per kolom.</p>

                <footer class="page-footer">
                    <span>Kruis alleen aan, geen tekst in de matrixvakken.</span>
                    <span>WK Pool 2026</span>
                </footer>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>
</html>
