<?php
require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
$participantId = isset($_GET['participant_id']) ? (int) $_GET['participant_id'] : 0;
$lang = strtolower((string) ($_GET['lang'] ?? 'de'));
if (!in_array($lang, ['nl', 'de'], true)) {
    $lang = 'de';
}

$participants = $pdo->query('SELECT id, name FROM participants ORDER BY name ASC')->fetchAll();
$participant = null;
$matches = $pdo->query("SELECT m.id, m.stage, m.match_date, ch.name_de AS home_country_name, ca.name_de AS away_country_name FROM matches m INNER JOIN countries ch ON ch.id = m.home_country_id INNER JOIN countries ca ON ca.id = m.away_country_id WHERE m.stage LIKE 'Group %' ORDER BY m.match_date ASC, m.id ASC")->fetchAll();
$groupOverview = [];
foreach ($matches as $match) {
    $stage = (string) $match['stage'];
    $groupOverview[$stage] ??= [];
    foreach (['home_country_name', 'away_country_name'] as $countryKey) {
        $countryName = (string) $match[$countryKey];
        if (!in_array($countryName, $groupOverview[$stage], true)) {
            $groupOverview[$stage][] = $countryName;
        }
    }
}

if ($participantId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, email FROM participants WHERE id = :id');
    $stmt->execute([':id' => $participantId]);
    $participant = $stmt->fetch();
}

$qualifiedTeams = wkQualifiedTeams($pdo);

$koColumns = $lang === 'nl'
    ? [
        'Zestiende finale' => 'zestiende finale',
        'Achtste finale' => 'achtste finale',
        'Kwartfinale' => 'kwartfinale',
        'Halve finale' => 'halve finale',
        'Finale' => 'grote finale',
        '3e plaats' => '3e plaats',
        'Wereldkampioen' => 'wereldkampioen',
    ]
    : [
        'Sechzehntelfinale' => '1/16',
        'Achtelfinale' => '1/8',
        'Viertelfinale' => '1/4',
        'Halbfinale' => '1/2',
        'Finale' => 'FIN',
        '3. Platz' => '3P',
        'Weltmeister' => 'WM',
    ];

function localizeCountryName(string $team, string $lang): string
{
    if ($lang !== 'nl') {
        return $team;
    }

    $map = [
        'Deutschland' => 'Duitsland',
        'Kolumbien' => 'Colombia',
        'Algerien' => 'Algerije',
        'Niederlande' => 'Nederland',
        'Jordanien' => 'Jordanië',
        'Schottland' => 'Schotland',
        'Saudi-Arabien' => 'Saoedi-Arabië',
        'Südafrika' => 'Zuid-Afrika',
        'Südkorea' => 'Zuid-Korea',
        'Neuseeland' => 'Nieuw-Zeeland',
        'Elfenbeinküste' => 'Ivoorkust',
        'Kap Verde' => 'Kaapverdië',
        'Vereinigte Arabische Emirate' => 'Verenigde Arabische Emiraten',
        'Bosnien und Herzegowina' => 'Bosnië en Herzegovina',
        'Schweiz' => 'Zwitserland',
        'Schweden' => 'Zweden',
        'Spanien' => 'Spanje',
        'Belgien' => 'België',
        'Uruguay' => 'Uruguay',
        'Ägypten' => 'Egypte',
        'Argentinien' => 'Argentinië',
        'Österreich' => 'Oostenrijk',
        'Frankreich' => 'Frankrijk',
        'Jamaika' => 'Jamaica',
        'Norwegen' => 'Noorwegen',
        'Senegal' => 'Senegal',
        'Mexiko' => 'Mexico',
        'Kanada' => 'Canada',
        'Australien' => 'Australië',
        'Katar' => 'Qatar',
        'Brasilien' => 'Brazilië',
        'Marokko' => 'Marokko',
        'USA' => 'VS',
        'Paraguay' => 'Paraguay',
        'Iran' => 'Iran',
        'Polen' => 'Polen',
        'Portugal' => 'Portugal',
        'Japan' => 'Japan',
        'Kroatien' => 'Kroatië',
        'Dänemark' => 'Denemarken',
        'Nigeria' => 'Nigeria',
        'Tunesien' => 'Tunesië',
        'Kamerun' => 'Kameroen',
        'Serbien' => 'Servië',
        'Tschechien' => 'Tsjechië',
        'Rumänien' => 'Roemenië',
        'Türkei' => 'Turkije',
        'Ukraine' => 'Oekraïne',
        'Chile' => 'Chili',
        'Peru' => 'Peru',
        'Ecuador' => 'Ecuador',
        'Costa Rica' => 'Costa Rica',
        'Honduras' => 'Honduras',
        'Inter-confederation playoff 1' => 'Intercontinentale play-off 1',
        'Inter-confederation playoff 2' => 'Intercontinentale play-off 2',
    ];

    return $map[$team] ?? $team;
}

function formatKoTeamLabel(string $team, string $lang): string
{
    $team = localizeCountryName($team, $lang);

    $presets = $lang === 'nl'
        ? [
            'Saoedi-Arabië' => 'Saudi A.',
            'Zuid-Afrika' => 'Zuid-Afr.',
            'Zuid-Korea' => 'Zuid-Kor.',
            'Nieuw-Zeeland' => 'Nw-Zeel.',
            'Ivoorkust' => 'Ivoork.',
            'Kaapverdië' => 'Kaapverd.',
            'Verenigde Arabische Emiraten' => 'VAE',
            'Bosnië en Herzegovina' => 'Bosn.-Her.',
        ]
        : [
            'Saudi-Arabien' => 'Saudi A.',
            'Südafrika' => 'Südafr.',
            'Südkorea' => 'Südkor.',
            'Neuseeland' => 'Neuseel.',
            'Elfenbeinküste' => 'Elfenbk.',
            'Kap Verde' => 'Kap Verde',
            'Vereinigte Arabische Emirate' => 'VAE',
            'Bosnien und Herzegowina' => 'Bosn.-Her.',
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

function formatGroupTeamLabel(string $team, string $lang): string
{
    $team = localizeCountryName($team, $lang);

    $presets = $lang === 'nl'
        ? [
            'Intercontinentale play-off 1' => 'Intercont. PO 1',
            'Intercontinentale play-off 2' => 'Intercont. PO 2',
            'UEFA playoff A' => 'UEFA PO A',
            'UEFA playoff B' => 'UEFA PO B',
            'UEFA playoff C' => 'UEFA PO C',
            'UEFA playoff D' => 'UEFA PO D',
        ]
        : [
            'Inter-confederation playoff 1' => 'Interkont. PO 1',
            'Inter-confederation playoff 2' => 'Interkont. PO 2',
            'UEFA playoff A' => 'UEFA PO A',
            'UEFA playoff B' => 'UEFA PO B',
            'UEFA playoff C' => 'UEFA PO C',
            'UEFA playoff D' => 'UEFA PO D',
        ];

    return $presets[$team] ?? $team;
}

function formatStyledTeamLabel(string $team, string $lang): string
{
    $label = formatGroupTeamLabel($team, $lang);
    $color = null;

    if (in_array($label, ['Nederland', 'Niederlande'], true)) {
        $color = '#f97316';
    } elseif (in_array($label, ['Duitsland', 'Deutschland'], true)) {
        $color = '#dc2626';
    }

    if ($color === null) {
        return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    }

    return '<span style="color: ' . $color . ';">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}

[$koTeamsCol1, $koTeamsCol2, $koTeamsCol3] = splitInThree($qualifiedTeams);
$groupOverviewItems = array_map(
    static fn (string $stage, array $teams): array => ['stage' => $stage, 'teams' => $teams],
    array_keys($groupOverview),
    array_values($groupOverview)
);

usort($groupOverviewItems, static function (array $a, array $b): int {
    return strcmp((string) $a['stage'], (string) $b['stage']);
});

$text = $lang === 'nl'
    ? [
        'nav_home' => '← Home',
        'nav_participants' => 'Deelnemers',
        'nav_matches' => 'Wedstrijden',
        'print' => 'Print formulier',
        'title' => 'Printbaar invulformulier',
        'subtitle' => 'Groepsfase in 3 kolommen en knock-outfase als matrixbord met landen links en rondes bovenaan.',
        'group_predictions' => 'Groepswedstrijden',
        'name' => 'Naam',
        'email' => 'E-mail',
        'version' => '',
        'rules_title' => 'Spelregels',
        'rules_intro' => 'Leuk dat je meedoet aan de WK Pool 2026!',
        'rules' => [
            'Inleggeld: <strong>€5 per persoon</strong>',
            'Prijzenpot: <strong>1e 50%</strong>, <strong>2e 30%</strong>, <strong>3e 20%</strong>',
            'Inleveren uiterlijk 10 juni, hetzij op papier of digitaal (duidelijke foto\'s) via WhatsApp +31 6 1311 3231 of e-mail maurits@luttikhuis.de.',
        ],
        'guide_1_title' => 'Invullen',
        'guide_1_text' => 'Alleen cijfers invullen, links thuisteam en rechts uitteam.',
        'guide_2_title' => '',
        'guide_2_text' => '',
        'footer_group' => '',
        'ko_title' => 'KO Fase',
        'group_overview_title' => 'Groepsindeling',
        'group_overview_hint' => 'Ter info voor je KO-voorspelling.',
        'group_points_title' => 'Puntentelling groepswedstrijden',
        'group_points_text' => '3 punten voor de juiste tendens, plus 1 punt per exact goed voorspeld aantal goals per team, dus max. 5 punten per wedstrijd.',
        'ko_points_title' => 'Puntentelling KO Fase',
        'goal' => '',
        'goal_value' => 'Aankruisen per ronde',
        'hint' => 'Vul per ronde de landen in die overblijven, in willekeurige volgorde. Gebruik het KO schema als inspiratie. 2, 4, 6, 8, 10, 5 en 15 punten per juist voorspeld land van zestiende finale t/m wereldkampioen.',
        'country' => 'Land',
        'ko_note' => '',
        'footer_ko' => 'Kruis alleen aan, geen tekst in de matrixvakken',
    ]
    : [
        'nav_home' => '← Start',
        'nav_participants' => 'Teilnehmer',
        'nav_matches' => 'Spiele',
        'print' => 'Formular drucken',
        'title' => 'Ausfüllbares Druckformular',
        'subtitle' => 'Gruppenphase in 3 Spalten und K.-o.-Phase als Matrix mit Ländern links und Runden oben.',
        'group_predictions' => 'Tipps für die Gruppenphase',
        'name' => 'Name',
        'email' => 'E-Mail',
        'version' => '',
        'rules_title' => 'Spielregeln',
        'rules_intro' => 'Schön, dass du beim WM Tippspiel 2026 mitmachst. Bitte trage deine Tipps gut lesbar ein und gib das Formular rechtzeitig ab.',
        'rules' => [
            'Einsatz: <strong>5 € pro Person</strong>',
            'Preistopf: <strong>1. Platz 50%</strong>, <strong>2. Platz 30%</strong>, <strong>3. Platz 20%</strong>',
            'Abgabe spätestens bis 10. Juni, entweder auf Papier oder digital (deutliche Fotos) per WhatsApp +31 6 1311 3231 oder per E-Mail an maurits@luttikhuis.de.',
        ],
        'guide_1_title' => 'Ausfüllen',
        'guide_1_text' => 'Nur Zahlen eintragen, links Heimteam und rechts Auswärtsteam.',
        'guide_2_title' => '',
        'guide_2_text' => '',
        'footer_group' => '',
        'ko_title' => 'K.-o.-Phase',
        'group_overview_title' => 'Gruppeneinteilung',
        'group_overview_hint' => 'Zur Orientierung für deine K.-o.-Tipps.',
        'ko_schema_title' => 'K.-o.-Schema zur Orientierung',
        'ko_rounds_title' => 'K.-o.-Phase pro Runde',
        'ko_points_label' => 'Punktevergabe K.-o.-Phase',
        'group_points_title' => 'Punkte Gruppenphase',
        'group_points_text' => '3 Punkte für die richtige Tendenz, plus 1 Punkt pro exakt richtig getippter Torzahl je Team, also max. 5 Punkte pro Spiel.',
        'goal' => 'Ziel',
        'goal_value' => 'Ankreuzen pro Runde',
        'hint' => 'Trage pro Runde die Länder ein, die übrig bleiben, in beliebiger Reihenfolge. Nutze den K.-o.-Spielplan als Inspiration. 2, 4, 6, 8, 10, 5 und 15 Punkte pro richtig getipptem Land vom Sechzehntelfinale bis zum Weltmeister.',
        'country' => 'Land',
        'ko_note' => '',
        'footer_ko' => 'Nur ankreuzen, keinen Text in die Matrixfelder schreiben.',
    ];
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart($lang === 'nl' ? 'WK Pool 2026 · KO voorstel printformulier' : 'WM Tippspiel 2026 · Druckformular', 'print-ko-proposal') ?>
    <style>
        .screen-wrap {
            max-width: none;
            margin: 0;
            padding: 0;
        }
        .panel {
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.28);
            padding: 22px;
            margin-bottom: 18px;
        }
        html, body {
            background: #ffffff !important;
        }
        .app-shell,
        .content-shell,
        .container,
        .screen-wrap {
            background: transparent !important;
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            overflow: visible !important;
        }
        .app-shell::before,
        .app-shell::after {
            display: none !important;
            background: none !important;
        }
        @media print {
            html, body, .app-shell, .content-shell, .container, .screen-wrap {
                background: #ffffff !important;
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                overflow: visible !important;
            }
            .panel {
                background: #ffffff !important;
                box-shadow: none !important;
            }
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
        .lang-chip.active {
            background: #22c55e;
            color: #06230f;
            border-color: rgba(34,197,94,0.55);
        }
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
            padding: 3mm 3mm 3mm;
            margin-bottom: 14px;
            border: 1px solid #d9e2ec;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        }
        .sheet-header {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 4px;
            margin-bottom: 6px;
            align-items: start;
            padding-bottom: 6px;
            border-bottom: 2px solid #dbe4ee;
        }
        .title-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .title-brand img {
            width: 92px;
            height: 92px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }
        .title h1 { margin: 0 0 1px; font-size: 30px; line-height: 1.0; }
        .section-title {
            margin: 0;
            color: #0f172a;
            line-height: 1.1;
            font-size: 22px;
            font-weight: 800;
            padding: 5px 9px;
            border-left: 6px solid #7c3aed;
            background: linear-gradient(90deg, rgba(124,58,237,0.22), rgba(124,58,237,0.02));
            border-radius: 10px;
        }
        .ko-section-title {
            border-left-color: #2563eb;
            background: linear-gradient(90deg, rgba(37,99,235,0.18), rgba(37,99,235,0.02));
        }
        .ko-round-title {
            border-left-color: #d9ea00;
            background: linear-gradient(90deg, rgba(245,255,26,0.34), rgba(233,255,58,0.10));
        }
        .subsection-title {
            margin: 0 0 6px;
            color: #0f172a;
            line-height: 1.15;
            font-size: 15px;
            font-weight: 800;
            padding: 5px 8px;
            border-left: 6px solid #7c3aed;
            background: linear-gradient(90deg, rgba(124,58,237,0.18), rgba(124,58,237,0.02));
            border-radius: 10px;
        }
        .subsection-title.ko-points {
            border-left-color: #7c3aed;
            background: linear-gradient(90deg, rgba(124,58,237,0.18), rgba(124,58,237,0.02));
            color: #111827;
        }
        .hint, .page-footer { margin: 0; color: #475569; line-height: 1.2; font-size: 15px; }
        .meta-block {
            border: 1.5px solid #0f172a; border-radius: 10px; padding: 7px;
        }
        .meta-line {
            display: grid; grid-template-columns: 84px 1fr; gap: 6px; margin-bottom: 5px; font-size: 14px;
            align-items: end;
        }
        .meta-line strong {
            font-size: 14px;
        }
        .line-box { min-height: 20px; border-bottom: 1.2px solid #334155; }
        .intro-rules {
            border: 2px solid #d9ea00;
            border-radius: 12px;
            padding: 10px 12px;
            margin: 0 0 8px;
            background: #f0ff2e !important;
            border-left: 12px solid #d9ea00;
            box-shadow: none;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .intro-rules.ko-info {
            background: linear-gradient(90deg, rgba(245,255,26,0.34), rgba(233,255,58,0.12));
            border-left-color: #d9ea00;
        }
        .intro-rules-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
        }
        .intro-rules-grid > div {
            background: transparent;
            border: 0;
            border-radius: 10px;
            padding: 0;
        }
        .intro-rules h2 {
            margin: 0 0 3px;
            font-size: 18px;
            color: #0f172a;
        }
        .intro-rules p,
        .intro-rules li {
            margin: 0;
            color: #334155;
            font-size: 15px;
            line-height: 1.3;
        }
        .intro-rules ul {
            margin: 4px 0 0 14px;
            padding: 0;
        }
        .intro-rules li + li {
            margin-top: 1px;
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
            grid-template-columns: 30px 44px 32px minmax(0, 1fr) 50px 50px;
            align-items: center;
            gap: 4px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 4px 5px;
            page-break-inside: avoid;
            min-height: 30px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
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
            font-size: 11px;
            color: #334155;
            line-height: 1;
            font-weight: 800;
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
            border-radius: 7px;
            display: block;
            font-size: 0;
            color: transparent;
            background: #fff;
            min-width: 50px;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        .group-overview-wrap {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 3px 4px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            margin: 0 0 4px;
        }
        .ko-info-schema {
            margin: 10px 0 12px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            padding: 10px;
        }
        .ko-info-schema-title {
            margin: 0 0 8px;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }
        .ko-info-schema img {
            display: block;
            width: 100%;
            height: auto;
        }
        .ko-page2-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 6px;
            margin-top: 8px;
            align-items: start;
        }
        .ko-page2-col {
            min-width: 0;
        }
        .ko-page2-line {
            height: 30px;
            border: 1px solid #111827;
            border-top: 0;
            background: #fff;
        }
        .ko-page2-line:first-of-type {
            border-top: 1px solid #111827;
        }
        .ko-page2-subtitle {
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 12px;
            margin-bottom: 6px;
            padding: 3px 4px;
            border-left: 4px solid #d9ea00;
            background: linear-gradient(90deg, rgba(245,255,26,0.34), rgba(233,255,58,0.10));
            border-radius: 8px;
        }
        .group-overview-meta {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            align-items: baseline;
            margin: 0 0 2px;
        }
        .group-overview-meta strong {
            font-size: 10px;
            color: #0f172a;
        }
        .group-overview-meta span {
            font-size: 7px;
            color: #64748b;
        }
        .group-overview-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 4px;
        }
        .group-overview-card {
            min-width: 0;
        }
        .group-overview-group {
            font-size: 18px;
            font-weight: 800;
            color: #7c3aed;
            margin: 0 0 3px;
        }
        .group-overview-teams {
            font-size: 15.5px;
            line-height: 1.2;
            color: #1e293b;
        }
        .ko-grid3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 6px;
            margin-top: 6px;
        }
        .ko-block {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        }
        .ko-head,
        .ko-row {
            display: grid;
            grid-template-columns: minmax(130px, 1fr) repeat(7, 17px);
            column-gap: 3px;
            row-gap: 0;
            align-items: center;
            justify-items: stretch;
        }
        .ko-head {
            background: linear-gradient(180deg, #eef2ff 0%, #dbeafe 100%);
            border-bottom: 1px solid #cbd5e1;
            min-height: 88px;
            padding: 2px 4px;
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            align-items: end;
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
            width: 17px;
            min-width: 17px;
            max-width: 17px;
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
            padding-right: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ko-col-label {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            text-align: left;
            font-size: 10px;
            line-height: 1;
            letter-spacing: -0.01em;
            width: 17px;
            height: 88px;
            justify-self: center;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            white-space: nowrap;
        }
        .ko-box-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 17px;
            height: 17px;
            justify-self: center;
        }
        .ko-mark {
            width: 12px;
            height: 12px;
            border: 1.7px solid #0f172a;
            border-radius: 3px;
        }
        .ko-note {
            font-size: 12px; color: #475569; margin-top: 8px;
        }
        .page-footer {
            display: flex; justify-content: space-between; gap: 12px; margin-top: 8px; font-size: 10px;
        }

        @media (max-width: 720px) {
            .screen-wrap { padding: 0; }
            .panel { padding: 16px; border-radius: 18px; }
            .nav { display: grid; grid-template-columns: 1fr; }
            .nav a, .nav button, .selector button { width: 100%; text-align: center; }
            .print-sheet { padding: 14px; border-radius: 14px; }
            .sheet-header, .guide, .ko-grid3, .matches-two-col, .intro-rules-grid, .group-overview-grid { grid-template-columns: 1fr; }
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
            .app-shell,
            .content-shell,
            .screen-wrap,
            .container { width: 100% !important; max-width: none !important; min-width: 0 !important; margin: 0; padding: 0; display: block; overflow: visible !important; }
            .side-nav,
            .panel, .nav, .selector, .screen-only { display: none !important; }
            .print-sheet { margin: 0; border-radius: 0; box-shadow: none; padding: 0; }
            .print-sheet + .print-sheet { break-before: auto; page-break-before: auto; }
            .ko-fields-grid,
            .ko-fields-grid-bottom,
            .ko-section-block { break-inside: avoid; page-break-inside: avoid; }
            .title-brand img {
                width: 78px;
                height: 78px;
            }
            .title h1 {
                font-size: 27px;
            }
            .section-title {
                font-size: 18px;
            }
            .guide {
                margin-bottom: 5px;
            }
            .guide-card {
                font-size: 8px;
                padding: 5px 6px;
                border-color: #cbd5e1;
            }
            .intro-rules {
                padding: 6px 7px;
                margin-bottom: 6px;
                border-color: #d9ea00;
                background: #f0ff2e !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .intro-rules-grid {
                gap: 5px;
            }
            .intro-rules-grid > div {
                padding: 6px 7px;
                border-color: #dbe4ee;
                background: #fff;
            }
            .intro-rules h2 {
                font-size: 18px;
                margin-bottom: 3px;
            }
            .intro-rules p,
            .intro-rules li {
                font-size: 13.5px;
                line-height: 1.3;
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
                grid-template-columns: 26px 38px 28px minmax(0, 1fr) 42px 42px;
                gap: 3px;
                padding: 2px 3px;
                min-height: 25px;
            }
            .match-no,
            .match-stage {
                font-size: 11px;
            }
            .match-date {
                font-size: 9px;
            }
            .match-teams strong {
                font-size: 11px;
            }
            .score-box {
                height: 18px;
                min-width: 42px;
            }
            .group-overview-wrap {
                padding: 2px 3px;
                margin-bottom: 4px;
                border-radius: 6px;
            }
            .group-overview-meta {
                margin-bottom: 1px;
            }
            .group-overview-meta strong {
                font-size: 8px;
            }
            .group-overview-meta span {
                font-size: 6px;
            }
            .group-overview-grid {
                gap: 3px;
            }
            .group-overview-group {
                font-size: 16px;
                margin-bottom: 2px;
            }
            .group-overview-teams {
                font-size: 14px;
                line-height: 1.15;
            }
            .ko-grid3 {
                gap: 4px;
            }
            .ko-head,
            .ko-row {
                grid-template-columns: minmax(108px, 1fr) repeat(7, 14px);
                column-gap: 2px;
                padding: 2px 3px;
            }
            .ko-head {
                min-height: 96px;
            }
            .ko-row > :nth-child(n+2),
            .ko-head > :nth-child(n+2) {
                width: 14px;
                min-width: 14px;
                max-width: 14px;
            }
            .ko-land {
                font-size: 11px;
            }
            .ko-col-label {
                font-size: 9px;
                width: 14px;
                height: 82px;
                text-align: left;
                justify-content: flex-start;
                align-items: flex-start;
            }
            .ko-box-wrap {
                width: 14px;
                height: 14px;
            }
            .ko-mark {
                width: 10px;
                height: 10px;
                border-width: 1.4px;
            }
            .ko-note,
            .page-footer {
                font-size: 9px;
                margin-top: 6px;
            }
            .hint {
                font-size: 13.5px;
            }
        }

</style>
<div class="screen-wrap">
        <div class="panel screen-only">
            <nav class="nav">
                <a href="index.php" class="secondary"><?= htmlspecialchars($text['nav_home'], ENT_QUOTES, 'UTF-8') ?></a>
                <a href="participants.php" class="secondary"><?= htmlspecialchars($text['nav_participants'], ENT_QUOTES, 'UTF-8') ?></a>
                <a href="matches.php" class="secondary"><?= htmlspecialchars($text['nav_matches'], ENT_QUOTES, 'UTF-8') ?></a>
                <a href="form-print.php?lang=nl" class="secondary lang-chip<?= $lang === 'nl' ? ' active' : '' ?>">NL</a>
                <a href="form-print.php?lang=de" class="secondary lang-chip<?= $lang === 'de' ? ' active' : '' ?>">DE</a>
                <button class="primary" onclick="window.print()"><?= htmlspecialchars($text['print'], ENT_QUOTES, 'UTF-8') ?></button>
            </nav>
            <div class="selector">
                <h1><?= htmlspecialchars($text['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="help"><?= htmlspecialchars($text['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

            <section class="print-sheet">
                <header class="sheet-header">
                    <div class="title">
                        <div class="title-brand">
                            <img src="assets/wk2026-logo.jpg" alt="WK Pool logo">
                            <div>
                                <h1><?= htmlspecialchars($lang === 'nl' ? 'WK Pool 2026' : 'WM Tippspiel 2026', ENT_QUOTES, 'UTF-8') ?></h1>

                            </div>
                        </div>
                    </div>
                    <div class="meta-block">
                        <div class="meta-line"><strong><?= htmlspecialchars($text['name'], ENT_QUOTES, 'UTF-8') ?></strong><div class="line-box"></div></div>
                        <div class="meta-line"><strong><?= htmlspecialchars($text['email'], ENT_QUOTES, 'UTF-8') ?></strong><div class="line-box"></div></div>
                        <div class="meta-line"><strong>WhatsApp</strong><div class="line-box"></div></div>
                        <?php if ($text['version'] !== ''): ?>
                            <div class="meta-line"><strong><?= htmlspecialchars($text['version'], ENT_QUOTES, 'UTF-8') ?></strong><div class="line-box">Form scan v2</div></div>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="intro-rules">
                    <div class="intro-rules-grid">
                        <div>
                            <h2><?= htmlspecialchars($text['rules_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p><?= htmlspecialchars($text['rules_intro'], ENT_QUOTES, 'UTF-8') ?></p>
                            <ul>
                                <?php foreach ($text['rules'] as $rule): ?>
                                    <li><?= $rule ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="subsection-title" style="margin-bottom: 8px;">
                    <strong style="display:block; margin-bottom:4px;"><?= htmlspecialchars($text['group_points_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span style="font-size:15px; font-weight:500;"><?= htmlspecialchars($text['group_points_text'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="matches-two-col">
                    <?php [$allLeftMatches, $allRightMatches] = splitInTwo($matches); ?>
                    <?php foreach ([$allLeftMatches, $allRightMatches] as $columnIndex => $columnMatches): ?>
                        <div class="matches-list">
                            <?php foreach ($columnMatches as $matchIndex => $match): ?>
                                <?php $absoluteIndex = $columnIndex === 0 ? ($matchIndex + 1) : (count($allLeftMatches) + $matchIndex + 1); ?>
                                <div class="match-row">
                                    <div class="match-no"><?= $absoluteIndex ?></div>
                                    <div class="match-stage"><?= htmlspecialchars(date('d-m', strtotime((string) $match['match_date'])), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="match-date"><?= htmlspecialchars(date('H:i', strtotime((string) $match['match_date'])), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="match-teams">
                                        <strong><?= formatStyledTeamLabel((string) $match['home_country_name'], $lang) ?> - <?= formatStyledTeamLabel((string) $match['away_country_name'], $lang) ?></strong>
                                    </div>
                                    <div class="score-box"></div>
                                    <div class="score-box"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="subsection-title ko-points" style="margin: 10px 0 6px;">
                    <strong style="display:block; margin-bottom:2px;"><?= htmlspecialchars($lang === 'nl' ? $text['ko_points_title'] : $text['ko_points_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span style="font-size:12px; font-weight:500;"><?= htmlspecialchars($text['hint'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="ko-page2-grid">
                    <div class="ko-page2-col">
                        <div class="section-title ko-round-title" style="font-size: 11px; margin: 0 0 3px; border-left-width: 4px; padding: 3px 4px;"><?= htmlspecialchars($lang === 'nl' ? '16e finalisten (2 pt)' : '1/16-Finalisten (2 pt)', ENT_QUOTES, 'UTF-8') ?></div>
                        <?php for ($i = 0; $i < 16; $i++): ?><div class="ko-page2-line"></div><?php endfor; ?>
                    </div>
                    <div class="ko-page2-col">
                        <div class="section-title ko-round-title" style="font-size: 11px; margin: 0 0 3px; border-left-width: 4px; padding: 3px 4px;"><?= htmlspecialchars($lang === 'nl' ? '16e finalisten (2 pt)' : '1/16-Finalisten (2 pt)', ENT_QUOTES, 'UTF-8') ?></div>
                        <?php for ($i = 0; $i < 16; $i++): ?><div class="ko-page2-line"></div><?php endfor; ?>
                    </div>
                    <div class="ko-page2-col">
                        <div class="section-title ko-round-title" style="font-size: 11px; margin: 0 0 3px; border-left-width: 4px; padding: 3px 4px;"><?= htmlspecialchars($lang === 'nl' ? '8e finalisten (4 pt)' : 'Achtelfinalisten (4 pt)', ENT_QUOTES, 'UTF-8') ?></div>
                        <?php for ($i = 0; $i < 16; $i++): ?><div class="ko-page2-line"></div><?php endfor; ?>
                    </div>
                    <div class="ko-page2-col">
                        <div class="section-title ko-round-title" style="font-size: 11px; margin: 0 0 3px; border-left-width: 4px; padding: 3px 4px;"><?= htmlspecialchars($lang === 'nl' ? 'Kwartfinalisten (6 pt)' : 'Viertelfinalisten (6 pt)', ENT_QUOTES, 'UTF-8') ?></div>
                        <?php for ($i = 0; $i < 8; $i++): ?><div class="ko-page2-line"></div><?php endfor; ?>
                    </div>
                    <div class="ko-page2-col">
                        <div class="section-title ko-round-title" style="font-size: 11px; margin: 0 0 3px; border-left-width: 4px; padding: 3px 4px;"><?= htmlspecialchars($lang === 'nl' ? 'Halve finalisten (8 pt)' : 'Halbfinalisten (8 pt)', ENT_QUOTES, 'UTF-8') ?></div>
                        <?php for ($i = 0; $i < 4; $i++): ?><div class="ko-page2-line"></div><?php endfor; ?>
                        <span class="ko-page2-subtitle"><?= htmlspecialchars($lang === 'nl' ? 'Finalisten (10 pt)' : 'Finalisten (10 pt)', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php for ($i = 0; $i < 2; $i++): ?><div class="ko-page2-line"></div><?php endfor; ?>
                        <span class="ko-page2-subtitle"><?= htmlspecialchars($lang === 'nl' ? '3e plaats (5 pt)' : '3. Platz (5 pt)', ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="ko-page2-line"></div>
                        <span class="ko-page2-subtitle"><?= htmlspecialchars($lang === 'nl' ? 'Wereldkampioen (15 pt)' : 'Weltmeister (15 pt)', ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="ko-page2-line"></div>
                    </div>
                </div>

                <footer class="page-footer">
                    <span><?= htmlspecialchars($text['footer_group'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span><?= htmlspecialchars($lang === 'nl' ? 'WK Pool 2026' : 'WM Tippspiel 2026', ENT_QUOTES, 'UTF-8') ?></span>
                </footer>
            </section>

            <?php
                $koRoundsProposal = $lang === 'nl'
                    ? [
                        ['title' => '16e finalisten', 'count' => 16],
                        ['title' => '16e finalisten', 'count' => 16],
                        ['title' => '8e finalisten', 'count' => 16],
                        ['title' => 'Kwartfinalisten', 'count' => 8],
                        ['title' => 'Halve finalisten', 'count' => 4],
                    ]
                    : [
                        ['title' => 'Sechzehntelfinalisten', 'count' => 16],
                        ['title' => 'Sechzehntelfinalisten', 'count' => 16],
                        ['title' => 'Achtelfinalisten', 'count' => 16],
                        ['title' => 'Viertelfinalisten', 'count' => 8],
                        ['title' => 'Halbfinalisten', 'count' => 4],
                    ];

                $koFinalBlocks = $lang === 'nl'
                    ? [
                        ['title' => 'Finalisten', 'count' => 2],
                        ['title' => '3e plaats', 'count' => 1],
                        ['title' => 'Wereldkampioen', 'count' => 1],
                    ]
                    : [
                        ['title' => 'Finalisten', 'count' => 2],
                        ['title' => '3. Platz', 'count' => 1],
                        ['title' => 'Weltmeister', 'count' => 1],
                    ];
            ?>
            <section class="print-sheet ko-section-block">
                <header class="sheet-header">
                    <div class="title">
                        <div class="title-brand">
                            <img src="assets/wk2026-logo.jpg" alt="WK Pool logo">
                            <div>
                                <h1><?= htmlspecialchars($lang === 'nl' ? 'WK Pool 2026 - achtergrondinformatie' : 'WM Tippspiel 2026 - Hintergrundinformationen', ENT_QUOTES, 'UTF-8') ?></h1>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="group-overview-wrap" style="margin-top: 0; margin-bottom: 10px;">
                    <div class="group-overview-meta">
                        <strong><?= htmlspecialchars($text['group_overview_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($text['group_overview_hint'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="group-overview-grid">
                        <?php foreach ($groupOverviewItems as $groupItem): ?>
                            <div class="group-overview-card">
                                <div class="group-overview-group"><?= htmlspecialchars($lang === 'nl' ? str_replace('Group', 'Groep', (string) $groupItem['stage']) : str_replace('Group', 'Gruppe', (string) $groupItem['stage']), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="group-overview-teams"><?= implode('<br>', array_map(static fn (string $team): string => formatStyledTeamLabel($team, $lang), $groupItem['teams'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="ko-info-schema">
                    <div class="ko-info-schema-title"><?= htmlspecialchars($lang === 'nl' ? 'KO-schema ter info' : $text['ko_schema_title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <img src="assets/<?= $lang === 'nl' ? 'ko-schema-nl.svg' : 'ko-schema-de.svg' ?>" alt="<?= htmlspecialchars($lang === 'nl' ? 'KO-schema WK 2026 Nederlandstalig' : 'K.-o.-Schema WM 2026 Deutsch', ENT_QUOTES, 'UTF-8') ?>" />
                </div>

                <footer class="page-footer" style="margin-top: 4px; font-size: 11px;">
                    <span><?= htmlspecialchars($lang === 'nl' ? 'WK Pool 2026' : 'WM Tippspiel 2026', ENT_QUOTES, 'UTF-8') ?></span>
                </footer>
            </section>
            </div>
        </div>
<?= wkPageShellEnd() ?>
