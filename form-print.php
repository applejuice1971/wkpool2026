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

function chunkMatches(array $matches, int $size = 36): array
{
    return array_chunk($matches, $size);
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

$pages = chunkMatches($matches, 36);
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
            padding: 8mm 7mm 7mm;
            margin-bottom: 18px;
        }
        .sheet-header {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 8px;
            margin-bottom: 6px;
            align-items: start;
        }
        .title h1 { margin: 0 0 1px; font-size: 24px; line-height: 1.0; }
        .title p, .hint, .page-footer { margin: 0; color: #475569; line-height: 1.3; }
        .meta-block {
            border: 1.5px solid #0f172a; border-radius: 10px; padding: 8px;
        }
        .meta-line {
            display: grid; grid-template-columns: 70px 1fr; gap: 6px; margin-bottom: 5px; font-size: 11px;
        }
        .line-box { min-height: 16px; border-bottom: 1px solid #334155; }
        .guide {
            display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin: 0 0 6px;
        }
        .guide-card {
            border: 1.2px solid #dbe4ee; border-radius: 10px; padding: 5px 7px; font-size: 10px; line-height: 1.2;
        }
        .guide-card strong { display: block; margin-bottom: 2px; }

        .matches-three-col {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 6px;
        }
        .matches-col {
            display: grid;
            gap: 4px;
        }
        .match-row {
            display: grid;
            grid-template-columns: 26px 26px minmax(0, 1fr) 44px 28px 28px;
            align-items: center;
            gap: 3px;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            padding: 4px;
            page-break-inside: avoid;
        }
        .match-no { font-weight: 800; font-size: 12px; }
        .match-stage { font-size: 9px; color: #475569; }
        .match-teams { min-width: 0; }
        .match-date {
            font-size: 8px;
            color: #64748b;
            text-align: right;
            line-height: 1.05;
            white-space: nowrap;
        }
        .match-teams strong {
            display: block;
            font-size: 12px;
            line-height: 1.08;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .score-box {
            height: 28px; border: 1.7px solid #0f172a; border-radius: 7px; display: flex; align-items: center; justify-content: center;
            font-size: 8px; color: #64748b; background: #fff;
        }

        .ko-grid3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 8px;
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
            .sheet-header, .guide, .matches-three-col, .ko-grid3 { grid-template-columns: 1fr; }
        }

        @media print {
            body { background: white; color: #111827; }
            .screen-wrap { max-width: none; margin: 0; padding: 0; }
            .panel, .nav, .selector, .screen-only { display: none !important; }
            .print-sheet { margin: 0; border-radius: 0; box-shadow: none; page-break-after: always; }
            .print-sheet:last-child { page-break-after: auto; }
            .matches-three-col {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 6px !important;
            }
            .matches-col {
                display: grid !important;
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
            <?php foreach ($pages as $pageIndex => $pageMatches): ?>
                <?php [$col1, $col2, $col3] = splitInThree($pageMatches); $pageBase = ($pageIndex * 36); $columnSizes = [count($col1), count($col2), count($col3)]; $columnOffsets = [0, $columnSizes[0], $columnSizes[0] + $columnSizes[1]]; ?>
                <section class="print-sheet<?= $pageIndex > 0 ? ' compact-followup' : '' ?>">
                    <?php if ($pageIndex === 0): ?>
                    <header class="sheet-header">
                        <div class="title">
                            <h1>WK Pool 2026</h1>
                            <p>Groepsfase voorspellingen — pagina <?= $pageIndex + 1 ?> van <?= count($pages) ?></p>
                        </div>
                        <div class="meta-block">
                            <div class="meta-line"><strong>Naam</strong><div class="line-box"><?= htmlspecialchars($participant['name'], ENT_QUOTES, 'UTF-8') ?></div></div>
                            <div class="meta-line"><strong>ID</strong><div class="line-box">P-<?= str_pad((string) $participant['id'], 3, '0', STR_PAD_LEFT) ?></div></div>
                            <div class="meta-line"><strong>Versie</strong><div class="line-box">Form v3</div></div>
                        </div>
                    </header>

                    <div class="guide">
                        <div class="guide-card"><strong>Invullen</strong>Gebruik alleen cijfers. Links thuisscore, rechts uitscore.</div>
                        <div class="guide-card"><strong>Compact</strong>Fase staat nu apart zodat er meer wedstrijden per pagina passen.</div>
                    </div>
                    <?php endif; ?>

                    <div class="matches-three-col">
                        <?php foreach ([$col1, $col2, $col3] as $columnIndex => $columnMatches): ?>
                            <div class="matches-col">
                                <?php foreach ($columnMatches as $matchIndex => $match): ?>
                                    <?php $absoluteIndex = $pageBase + $columnOffsets[$columnIndex] + $matchIndex + 1; ?>
                                    <div class="match-row">
                                        <div class="match-no">#<?= $absoluteIndex ?></div>
                                        <div class="match-stage"><?= htmlspecialchars(str_replace('Group ', 'G', $match['stage']), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="match-teams">
                                            <strong><?= htmlspecialchars(formatGroupTeamLabel($match['home_team']), ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(formatGroupTeamLabel($match['away_team']), ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="match-date"><?= htmlspecialchars(date('d-m H:i', strtotime($match['match_date'])), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="score-box">T</div>
                                        <div class="score-box">U</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <footer class="page-footer">
                        <span>Schrijf duidelijk met donkere pen.</span>
                        <span>P-<?= str_pad((string) $participant['id'], 3, '0', STR_PAD_LEFT) ?></span>
                    </footer>
                </section>
            <?php endforeach; ?>

            <section class="print-sheet">
                <header class="sheet-header">
                    <div class="title">
                        <h1>WK Pool 2026</h1>
                        <p>Knock-outmatrix — 3 kolommen naast elkaar</p>
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
