<?php
require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkEnsureKoSchema($pdo);

$data = [
    'Group A' => [
        'June 11: Mexico vs South Africa - Estadio Azteca, Mexico City - 3pm ET',
        'June 11: South Korea vs UEFA playoff D - Estadio Akron, Guadalajara - 10pm ET',
        'June 18: UEFA playoff D vs South Africa - Mercedes-Benz Stadium, Atlanta - 12pm ET',
        'June 18: Mexico vs South Korea - Estadio Akron, Guadalajara - 9pm ET',
        'June 24: UEFA playoff D vs Mexico - Estadio Azteca, Mexico City - 9pm ET',
        'June 24: South Africa vs South Korea - Estadio BBVA, Monterrey - 9pm ET',
    ],
    'Group B' => [
        'June 12: Canada vs UEFA playoff A - BMO Field, Toronto - 3pm ET',
        'June 13: Qatar vs Switzerland - Levi’s Stadium, San Francisco Bay Area - 3pm ET',
        'June 18: Switzerland vs UEFA playoff A - SoFi Stadium, Los Angeles - 3pm ET',
        'June 18: Canada vs Qatar - BC Place, Vancouver - 6pm ET',
        'June 24: Switzerland vs Canada - BC Place, Vancouver - 3pm ET',
        'June 24: UEFA playoff A vs Qatar - Lumen Field, Seattle - 3pm ET',
    ],
    'Group C' => [
        'June 13: Brazil vs Morocco - MetLife Stadium, New York/New Jersey - 6pm ET',
        'June 13: Haiti vs Scotland - Gillette Stadium, Boston - 9pm ET',
        'June 19: Scotland vs Morocco - Gillette Stadium, Boston - 6pm ET',
        'June 19: Brazil vs Haiti - Lincoln Financial Field, Philadelphia - 9pm ET',
        'June 24: Scotland vs Brazil - Hard Rock Stadium, Miami - 6pm ET',
        'June 24: Morocco vs Haiti - Mercedes-Benz Stadium, Atlanta - 6pm ET',
    ],
    'Group D' => [
        'June 12: USA vs Paraguay - SoFi Stadium, Los Angeles - 9pm ET',
        'June 13: Australia vs UEFA playoff C - BC Place, Vancouver - Midnight ET',
        'June 19: USA vs Australia - Lumen Field, Seattle - 3pm ET',
        'June 19: UEFA playoff C vs Paraguay - Levi’s Stadium, San Francisco Bay Area - Midnight ET',
        'June 25: UEFA playoff C vs USA - SoFi Stadium, Los Angeles - 10pm ET',
        'June 25: Paraguay vs Australia - Levi’s Stadium, San Francisco Bay Area - 10pm ET',
    ],
    'Group E' => [
        'June 14: Germany vs Curacao - NRG Stadium, Houston - 1pm ET',
        'June 14: Ivory Coast vs Ecuador - Lincoln Financial Field, Philadelphia - 7pm ET',
        'June 20: Germany vs Ivory Coast - BMO Field, Toronto - 4pm ET',
        'June 20: Ecuador vs Curacao - Arrowhead Stadium, Kansas City - 8pm ET',
        'June 25: Ecuador vs Germany - MetLife Stadium, New York/New Jersey - 4pm ET',
        'June 25: Curacao vs Ivory Coast - Lincoln Financial Field, Philadelphia - 4pm ET',
    ],
    'Group F' => [
        'June 14: Netherlands vs Japan - AT&T Stadium, Dallas - 4pm ET',
        'June 14: UEFA playoff B vs Tunisia - Estadio BBVA, Monterrey - 10pm ET',
        'June 20: Netherlands vs UEFA playoff B - NRG Stadium, Houston - 1pm ET',
        'June 20: Tunisia vs Japan - Estadio BBVA, Monterrey - Midnight ET',
        'June 25: Japan vs UEFA playoff B - AT&T Stadium, Dallas - 7pm ET',
        'June 25: Tunisia vs Netherlands - Arrowhead Stadium, Kansas City - 7pm ET',
    ],
    'Group G' => [
        'June 15: Iran vs New Zealand - SoFi Stadium, Los Angeles - 9pm ET',
        'June 15: Belgium vs Egypt - Lumen Field, Seattle - 3pm ET',
        'June 21: Belgium vs Iran - SoFi Stadium, Los Angeles - 3pm ET',
        'June 21: New Zealand vs Egypt - BC Place, Vancouver - 9pm ET',
        'June 26: Egypt vs Iran - Lumen Field, Seattle - 11pm ET',
        'June 26: New Zealand vs Belgium - BC Place, Vancouver - 11pm ET',
    ],
    'Group H' => [
        'June 15: Spain vs Cape Verde - Mercedes-Benz Stadium, Atlanta - 12pm ET',
        'June 15: Saudi Arabia vs Uruguay - Hard Rock Stadium, Miami - 6pm ET',
        'June 21: Spain vs Saudi Arabia - Mercedes-Benz Stadium, Atlanta - 12pm ET',
        'June 21: Uruguay vs Cape Verde - Hard Rock Stadium, Miami - 6pm ET',
        'June 26: Cape Verde vs Saudi Arabia - NRG Stadium, Houston - 8pm ET',
        'June 26: Uruguay vs Spain - Estadio Akron, Guadalajara - 8pm ET',
    ],
    'Group I' => [
        'June 16: France vs Senegal - MetLife Stadium, New York/New Jersey - 3pm ET',
        'June 16: Inter-confederation playoff 2 vs Norway - Gillette Stadium, Boston - 6pm ET',
        'June 22: France vs Inter-confederation playoff 2 - Lincoln Financial Field, Philadelphia - 5pm ET',
        'June 22: Norway vs Senegal - MetLife Stadium, New York/New Jersey - 8pm ET',
        'June 26: Norway vs France - Gillette Stadium, Boston - 3pm ET',
        'June 26: Senegal vs Inter-confederation playoff 2 - BMO Field, Toronto - 3pm ET',
    ],
    'Group J' => [
        'June 16: Argentina vs Algeria - Arrowhead Stadium - Kansas City - 9pm ET',
        'June 16: Austria vs Jordan - Levi’s Stadium, San Francisco Bay Area - Midnight ET',
        'June 22: Argentina vs Austria - AT&T Stadium, Dallas - 1pm ET',
        'June 22: Jordan vs Algeria - Levi’s Stadium, San Francisco Bay Area - 11pm ET',
        'June 27: Algeria vs Austria - Arrowhead Stadium, Kansas City - 10pm ET',
        'June 27: Jordan vs Argentina - AT&T Stadium, Dallas - 10pm ET',
    ],
    'Group K' => [
        'June 17: Portugal vs Inter-confederation playoff 1 - NRG Stadium, Houston - 1pm ET',
        'June 17: Uzbekistan vs Colombia - Estadio Azteca, Mexico City - 10pm ET',
        'June 23: Portugal vs Uzbekistan - NRG Stadium, Houston - 1pm ET',
        'June 23: Colombia vs Inter-confederation playoff 1 - Estadio Akron, Guadalajara - 10pm ET',
        'June 27: Colombia vs Portugal - Hard Rock Stadium, Miami - 7:30pm ET',
        'June 27: Inter-confederation playoff 1 vs Uzbekistan - Mercedes-Benz Stadium, Atlanta - 7:30pm ET',
    ],
    'Group L' => [
        'June 17: England vs Croatia - AT&T Stadium, Dallas - 4pm ET',
        'June 17: Ghana vs Panama - BMO Field, Toronto - 7pm ET',
        'June 23: England vs Ghana - Gillette Stadium, Boston - 4pm ET',
        'June 23: Panama vs Croatia - BMO Field, Toronto - 7pm ET',
        'June 27: Panama vs England - MetLife Stadium, New York/New Jersey - 5pm ET',
        'June 27: Croatia vs Ghana - Lincoln Financial Field, Philadelphia - 5pm ET',
    ],
    'Round of 32' => [
        'June 28: Runner up Group A vs Runner up Group B - SoFi Stadium, Los Angeles - 3pm ET',
        'June 29: Winner Group C vs Runner up Group F - NRG Stadium, Houston - 1pm ET',
        'June 29: Winner Group E vs 3rd Group A/B/C/D/F - Gillette Stadium, Boston - 4:30pm ET',
        'June 29: Winner Group F vs Runner up Group C - Estadio BBVA, Monterrey - 9pm ET',
        'June 30: Runner up Group E vs Runner up Group I - AT&T Stadium, Dallas - 1pm ET',
        'June 30: Winner Group I vs 3rd Group C/D/F/G/H - MetLife Stadium, New York/New Jersey - 5pm ET',
        'June 30: Winner Group A vs 3rd Group C/E/F/H/I - Estadio Azteca, Mexico City - 9pm ET',
        'July 1: Winner Group L vs 3rd Group E/H/I/J/K - Mercedes-Benz Stadium, Atlanta - 12pm ET',
        'July 1: Winner Group G vs 3rd Group A/E/H/I/J - Lumen Field, Seattle - 4pm ET',
        'July 1: Winner Group D vs 3rd Group B/E/F/I/J - Levi’s Stadium, San Francisco Bay Area - 8pm ET',
        'July 2: Winner Group H vs Runner up Group J - SoFi Stadium, Los Angeles - 3pm ET',
        'July 2: Runner up Group K vs Runner up Group L - BMO Field, Toronto - 7pm ET',
        'July 2: Winner Group B vs 3rd Group E/F/G/I/J - BC Place, Vancouver - 11pm ET',
        'July 3: Runner up Group D vs Runner up Group G - AT&T Stadium, Dallas - 2pm ET',
        'July 3: Winner Group J vs Runner up Group H - Hard Rock Stadium, Miami - 6pm ET',
        'July 3: Winner Group K vs 3rd Group D/E/I/J/L - Arrowhead Stadium, Kansas City - 9:30pm ET',
    ],
    'Round of 16' => [
        'July 4: Winner Match 73 vs Winner Match 75 - NRG Stadium, Houston - 1pm ET',
        'July 4: Winner Match 74 vs Winner Match 77 - Lincoln Financial Field, Philadelphia - 5pm ET',
        'July 5: Winner Match 76 vs Winner Match 78 - MetLife Stadium, New York/New Jersey - 4pm ET',
        'July 5: Winner Match 79 vs Winner Match 80 - Estadio Azteca, Mexico City - 8pm ET',
        'July 6: Winner Match 83 vs Winner Match 84 - AT&T Stadium, Dallas - 3pm ET',
        'July 6: Winner Match 81 vs Winner Match 82 - Lumen Field, Seattle - 8pm ET',
        'July 7: Winner Match 86 vs Winner Match 88 - Mercedes-Benz Stadium, Atlanta - 12pm ET',
        'July 7: Winner Match 85 vs Winner Match 87 - BC Place, Vancouver - 4pm ET',
    ],
    'Quarterfinal' => [
        'July 9: Winner Match 89 vs Winner Match 90 - Gillette Stadium, Boston - 4pm ET',
        'July 10: Winner Match 93 vs Winner Match 94 - SoFi Stadium, Los Angeles - 3pm ET',
        'July 11: Winner Match 91 vs Winner Match 92 - Hard Rock Stadium, Miami - 5pm ET',
        'July 11: Winner Match 95 vs Winner Match 96 - Arrowhead Stadium, Kansas City - 9pm ET',
    ],
    'Semifinal' => [
        'July 14: Winner Match 97 vs Winner Match 98 - AT&T Stadium, Dallas - 3pm ET',
        'July 15: Winner Match 99 vs Winner Match 100 - Mercedes-Benz Stadium, Atlanta - 3pm ET',
    ],
    'Third-place game' => [
        'July 18: Loser Match 101 vs Loser Match 102 - Hard Rock Stadium, Miami - 5pm ET',
    ],
    'Final' => [
        'July 19: Winner Match 101 vs Winner Match 102 - MetLife Stadium, New York/New Jersey - 3pm ET',
    ],
];

function parseTimePart(string $timePart): array
{
    $value = trim(str_replace(' ET', '', $timePart));
    if (strtolower($value) === 'midnight') {
        return [0, 0];
    }

    $format = str_contains($value, ':') ? 'g:ia' : 'ga';
    $date = DateTimeImmutable::createFromFormat($format, strtolower($value), new DateTimeZone('America/New_York'));
    if (!$date) {
        throw new RuntimeException('Kon tijd niet parsen: ' . $timePart);
    }

    return [(int) $date->format('H'), (int) $date->format('i')];
}

function placeholderToGerman(string $team): string
{
    $map = [
        'Inter-confederation playoff 1' => 'Interkont. Playoff 1',
        'Inter-confederation playoff 2' => 'Interkont. Playoff 2',
        'UEFA playoff A' => 'UEFA-Playoff A',
        'UEFA playoff B' => 'UEFA-Playoff B',
        'UEFA playoff C' => 'UEFA-Playoff C',
        'UEFA playoff D' => 'UEFA-Playoff D',
        'Winner Group A' => 'Sieger Gruppe A',
        'Winner Group B' => 'Sieger Gruppe B',
        'Winner Group C' => 'Sieger Gruppe C',
        'Winner Group D' => 'Sieger Gruppe D',
        'Winner Group E' => 'Sieger Gruppe E',
        'Winner Group F' => 'Sieger Gruppe F',
        'Winner Group G' => 'Sieger Gruppe G',
        'Winner Group H' => 'Sieger Gruppe H',
        'Winner Group I' => 'Sieger Gruppe I',
        'Winner Group J' => 'Sieger Gruppe J',
        'Winner Group K' => 'Sieger Gruppe K',
        'Winner Group L' => 'Sieger Gruppe L',
        'Runner up Group A' => 'Zweiter Gruppe A',
        'Runner up Group B' => 'Zweiter Gruppe B',
        'Runner up Group C' => 'Zweiter Gruppe C',
        'Runner up Group D' => 'Zweiter Gruppe D',
        'Runner up Group E' => 'Zweiter Gruppe E',
        'Runner up Group F' => 'Zweiter Gruppe F',
        'Runner up Group G' => 'Zweiter Gruppe G',
        'Runner up Group H' => 'Zweiter Gruppe H',
        'Runner up Group I' => 'Zweiter Gruppe I',
        'Runner up Group J' => 'Zweiter Gruppe J',
        'Runner up Group K' => 'Zweiter Gruppe K',
        'Runner up Group L' => 'Zweiter Gruppe L',
        '3rd Group A/B/C/D/F' => 'Dritter Gruppe A/B/C/D/F',
        '3rd Group A/E/H/I/J' => 'Dritter Gruppe A/E/H/I/J',
        '3rd Group B/E/F/I/J' => 'Dritter Gruppe B/E/F/I/J',
        '3rd Group C/D/F/G/H' => 'Dritter Gruppe C/D/F/G/H',
        '3rd Group C/E/F/H/I' => 'Dritter Gruppe C/E/F/H/I',
        '3rd Group D/E/I/J/L' => 'Dritter Gruppe D/E/I/J/L',
        '3rd Group E/F/G/I/J' => 'Dritter Gruppe E/F/G/I/J',
        '3rd Group E/H/I/J/K' => 'Dritter Gruppe E/H/I/J/K',
        'Winner Match 73' => 'Sieger Spiel 73',
        'Winner Match 74' => 'Sieger Spiel 74',
        'Winner Match 75' => 'Sieger Spiel 75',
        'Winner Match 76' => 'Sieger Spiel 76',
        'Winner Match 77' => 'Sieger Spiel 77',
        'Winner Match 78' => 'Sieger Spiel 78',
        'Winner Match 79' => 'Sieger Spiel 79',
        'Winner Match 80' => 'Sieger Spiel 80',
        'Winner Match 81' => 'Sieger Spiel 81',
        'Winner Match 82' => 'Sieger Spiel 82',
        'Winner Match 83' => 'Sieger Spiel 83',
        'Winner Match 84' => 'Sieger Spiel 84',
        'Winner Match 85' => 'Sieger Spiel 85',
        'Winner Match 86' => 'Sieger Spiel 86',
        'Winner Match 87' => 'Sieger Spiel 87',
        'Winner Match 88' => 'Sieger Spiel 88',
        'Winner Match 89' => 'Sieger Spiel 89',
        'Winner Match 90' => 'Sieger Spiel 90',
        'Winner Match 91' => 'Sieger Spiel 91',
        'Winner Match 92' => 'Sieger Spiel 92',
        'Winner Match 93' => 'Sieger Spiel 93',
        'Winner Match 94' => 'Sieger Spiel 94',
        'Winner Match 95' => 'Sieger Spiel 95',
        'Winner Match 96' => 'Sieger Spiel 96',
        'Winner Match 97' => 'Sieger Spiel 97',
        'Winner Match 98' => 'Sieger Spiel 98',
        'Winner Match 99' => 'Sieger Spiel 99',
        'Winner Match 100' => 'Sieger Spiel 100',
        'Winner Match 101' => 'Sieger Spiel 101',
        'Winner Match 102' => 'Sieger Spiel 102',
        'Loser Match 101' => 'Verlierer Spiel 101',
        'Loser Match 102' => 'Verlierer Spiel 102',
    ];

    return $map[$team] ?? $team;
}

function resolveCountryIds(PDO $pdo): array
{
    $rows = $pdo->query('SELECT id, name_de, name_en FROM countries')->fetchAll();
    $lookup = [];

    $aliases = [
        'Algeria' => 'Algerien',
        'Argentina' => 'Argentinien',
        'Australia' => 'Australien',
        'Austria' => 'Österreich',
        'Belgium' => 'Belgien',
        'Bosnia and Herzegovina' => 'Bosnien und Herzegowina',
        'Brazil' => 'Brasilien',
        'Canada' => 'Kanada',
        'Cape Verde' => 'Kap Verde',
        'Colombia' => 'Kolumbien',
        'Croatia' => 'Kroatien',
        'Curacao' => 'Curaçao',
        'Curaçao' => 'Curaçao',
        'DR Congo' => 'DR Kongo',
        'Ecuador' => 'Ecuador',
        'Egypt' => 'Ägypten',
        'England' => 'England',
        'France' => 'Frankreich',
        'Germany' => 'Deutschland',
        'Ghana' => 'Ghana',
        'Haiti' => 'Haiti',
        'Iran' => 'Iran',
        'Iraq' => 'Irak',
        'Ivory Coast' => 'Elfenbeinküste',
        'Japan' => 'Japan',
        'Jordan' => 'Jordanien',
        'Mexico' => 'Mexiko',
        'Morocco' => 'Marokko',
        'Netherlands' => 'Niederlande',
        'New Zealand' => 'Neuseeland',
        'Norway' => 'Norwegen',
        'Panama' => 'Panama',
        'Paraguay' => 'Paraguay',
        'Portugal' => 'Portugal',
        'Qatar' => 'Katar',
        'Saudi Arabia' => 'Saudi-Arabien',
        'Scotland' => 'Schottland',
        'Senegal' => 'Senegal',
        'South Africa' => 'Südafrika',
        'South Korea' => 'Südkorea',
        'Spain' => 'Spanien',
        'Sweden' => 'Schweden',
        'Switzerland' => 'Schweiz',
        'Tunisia' => 'Tunesien',
        'USA' => 'USA',
        'United States' => 'USA',
        'United Arab Emirates' => 'Vereinigte Arabische Emirate',
        'Uruguay' => 'Uruguay',
        'Uzbekistan' => 'Usbekistan',
    ];

    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $nameDe = (string) $row['name_de'];
        $nameEn = (string) $row['name_en'];
        $payload = ['id' => $id, 'name_de' => $nameDe];

        $lookup[$nameEn] = $payload;
        $lookup[$nameDe] = $payload;

        foreach ($aliases as $english => $german) {
            if ($nameDe === $german || $nameEn === $english || $nameEn === $german) {
                $lookup[$english] = $payload;
                $lookup[$german] = $payload;
            }
        }
    }

    return $lookup;
}

function parseLine(string $line, string $stage, int $index): array
{
    if (!preg_match('/^(June|July)\s+(\d+):\s+(.+?)\s+vs\s+(.+?)\s+-\s+(.+)\s+-\s+(.+?) ET$/', $line, $m)) {
        throw new RuntimeException('Kon regel niet parsen: ' . $line);
    }

    [, $monthName, $day, $home, $away, $location, $timePart] = $m;
    $month = $monthName === 'June' ? 6 : 7;
    [$hour, $minute] = parseTimePart($timePart);

    $dt = new DateTimeImmutable(sprintf('2026-%02d-%02d %02d:%02d:00', $month, (int) $day, $hour, $minute), new DateTimeZone('America/New_York'));
    $dtBerlin = $dt->setTimezone(new DateTimeZone('Europe/Berlin'));

    return [
        'external_id' => sprintf('%s-%03d', $stage, $index),
        'stage' => $stage,
        'match_date' => $dtBerlin->format('Y-m-d H:i:s'),
        'home_source' => $home,
        'away_source' => $away,
    ];
}

$countryLookup = resolveCountryIds($pdo);

$pdo->beginTransaction();
$pdo->exec('DELETE FROM predictions');
$pdo->exec('DELETE FROM matches');

$stmt = $pdo->prepare('INSERT INTO matches (external_id, stage, match_date, home_country_id, away_country_id, status) VALUES (:external_id, :stage, :match_date, :home_country_id, :away_country_id, :status)');

$count = 0;
foreach ($data as $stage => $lines) {
    foreach ($lines as $index => $line) {
        $row = parseLine($line, $stage, $index + 1);
        $home = $countryLookup[$row['home_source']] ?? $countryLookup[placeholderToGerman($row['home_source'])] ?? null;
        $away = $countryLookup[$row['away_source']] ?? $countryLookup[placeholderToGerman($row['away_source'])] ?? null;
        if ($home === null || $away === null) {
            throw new RuntimeException('Land of placeholder niet gevonden in countries: ' . $row['home_source'] . ' / ' . $row['away_source']);
        }
        $stmt->execute([
            ':external_id' => $row['external_id'],
            ':stage' => $row['stage'],
            ':match_date' => $row['match_date'],
            ':home_country_id' => $home['id'],
            ':away_country_id' => $away['id'],
            ':status' => 'scheduled',
        ]);
        $count++;
    }
}

$pdo->commit();
echo "Imported {$count} matches with countries.\n";
