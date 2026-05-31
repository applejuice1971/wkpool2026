<?php

declare(strict_types=1);

function wkLoadEnv(string $path): array
{
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }

        $vars[$key] = trim($value, "\"'");
    }

    return $vars;
}

function wkGetPdo(): PDO
{
    $env = wkLoadEnv(__DIR__ . '/.env');
    if (($env['DB_CONNECTION'] ?? '') !== 'mysql') {
        throw new RuntimeException('DB_CONNECTION staat niet op mysql in .env');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '127.0.0.1',
        $env['DB_PORT'] ?? '3306',
        $env['DB_DATABASE'] ?? ''
    );

    return new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function wkReviewStatuses(): array
{
    return [
        '0%' => '0%',
        '50%' => '50%',
        '99%' => '99%',
        'OK' => 'OK',
    ];
}

function wkReviewStatusBadgeClass(string $status): string
{
    return match ($status) {
        'OK' => 'ok',
        '99%' => 'neutral',
        '50%' => 'warn',
        '0%' => 'bad',
        default => 'neutral',
    };
}

function wkEnsureImportSchema(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS prediction_imports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    participant_id INT UNSIGNED NULL,
    source_filename VARCHAR(255) NOT NULL,
    source_path VARCHAR(255) NOT NULL,
    source_type ENUM('pdf','jpg','jpeg','png') NOT NULL,
    status ENUM('received','parsed','imported','review_needed','failed') NOT NULL DEFAULT 'received',
    extracted_name VARCHAR(120) NULL,
    extracted_text MEDIUMTEXT NULL,
    notes TEXT NULL,
    imported_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_prediction_imports_status (status),
    KEY idx_prediction_imports_created_at (created_at),
    CONSTRAINT fk_prediction_imports_participant FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS prediction_import_rows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id INT UNSIGNED NOT NULL,
    match_id INT UNSIGNED NULL,
    raw_label VARCHAR(255) NOT NULL,
    predicted_home_score TINYINT UNSIGNED NULL,
    predicted_away_score TINYINT UNSIGNED NULL,
    confidence DECIMAL(5,2) NULL,
    status ENUM('parsed','matched','imported','review_needed') NOT NULL DEFAULT 'parsed',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_prediction_import_rows_import_id (import_id),
    KEY idx_prediction_import_rows_match_id (match_id),
    CONSTRAINT fk_prediction_import_rows_import FOREIGN KEY (import_id) REFERENCES prediction_imports(id) ON DELETE CASCADE,
    CONSTRAINT fk_prediction_import_rows_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS ko_prediction_import_rows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id INT UNSIGNED NOT NULL,
    round_key VARCHAR(32) NOT NULL,
    position INT UNSIGNED NOT NULL,
    raw_value VARCHAR(255) NULL,
    normalized_team_name VARCHAR(120) NULL,
    confidence DECIMAL(5,2) NULL,
    status ENUM('parsed','matched','imported','review_needed') NOT NULL DEFAULT 'parsed',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ko_prediction_import_row (import_id, round_key, position),
    KEY idx_ko_prediction_import_rows_import_id (import_id),
    KEY idx_ko_prediction_import_rows_round_key (round_key),
    CONSTRAINT fk_ko_prediction_import_rows_import FOREIGN KEY (import_id) REFERENCES prediction_imports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
}

function wkImportStoragePath(): string
{
    $dir = __DIR__ . '/uploads/prediction-imports';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return $dir;
}

function wkEnsurePredictionReviewSchema(PDO $pdo): void
{
    $pdo->exec("ALTER TABLE predictions ADD COLUMN IF NOT EXISTS review_status ENUM('0%','50%','99%','OK') NOT NULL DEFAULT 'OK' AFTER predicted_away_score");
}

function wkStatusBadgeClass(string $status): string
{
    return match ($status) {
        'imported' => 'ok',
        'review_needed' => 'warn',
        'failed' => 'bad',
        default => 'neutral',
    };
}

function wkQualifiedTeams(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, name_de, name_en, flag_emoji FROM countries WHERE is_placeholder = 0 ORDER BY name_de ASC");
    return $stmt->fetchAll();
}

function wkFlagEmoji(string $team): string
{
    $flags = [
        'Algeria' => '🇩🇿',
        'Argentina' => '🇦🇷',
        'Australia' => '🇦🇺',
        'Austria' => '🇦🇹',
        'Belgium' => '🇧🇪',
        'Bosnia and Herzegovina' => '🇧🇦',
        'Brazil' => '🇧🇷',
        'Canada' => '🇨🇦',
        'Cape Verde' => '🇨🇻',
        'Colombia' => '🇨🇴',
        'Croatia' => '🇭🇷',
        'Curacao' => '🇨🇼',
        'DR Congo' => '🇨🇩',
        'Ecuador' => '🇪🇨',
        'Egypt' => '🇪🇬',
        'England' => '🏴',
        'France' => '🇫🇷',
        'Germany' => '🇩🇪',
        'Ghana' => '🇬🇭',
        'Haiti' => '🇭🇹',
        'Iran' => '🇮🇷',
        'Iraq' => '🇮🇶',
        'Ivory Coast' => '🇨🇮',
        'Jamaica' => '🇯🇲',
        'Japan' => '🇯🇵',
        'Jordan' => '🇯🇴',
        'Mexico' => '🇲🇽',
        'Morocco' => '🇲🇦',
        'Netherlands' => '🇳🇱',
        'New Zealand' => '🇳🇿',
        'Norway' => '🇳🇴',
        'Panama' => '🇵🇦',
        'Paraguay' => '🇵🇾',
        'Portugal' => '🇵🇹',
        'Qatar' => '🇶🇦',
        'Saudi Arabia' => '🇸🇦',
        'Scotland' => '🏴',
        'Senegal' => '🇸🇳',
        'South Africa' => '🇿🇦',
        'South Korea' => '🇰🇷',
        'Spain' => '🇪🇸',
        'Sweden' => '🇸🇪',
        'Switzerland' => '🇨🇭',
        'Tunisia' => '🇹🇳',
        'United Arab Emirates' => '🇦🇪',
        'Uruguay' => '🇺🇾',
        'USA' => '🇺🇸',
        'Uzbekistan' => '🇺🇿',
    ];

    return $flags[$team] ?? '🏳️';
}

function wkMatchLabel(array $match): string
{
    $home = trim((string) ($match['home_country_name'] ?? ''));
    $away = trim((string) ($match['away_country_name'] ?? ''));
    return trim($home . ' - ' . $away);
}

function wkGroupMatchNumberMap(PDO $pdo): array
{
    $rows = $pdo->query("SELECT id FROM matches WHERE stage LIKE 'Group %' ORDER BY match_date ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
    $map = [];
    foreach ($rows as $idx => $id) {
        $map[(int) $id] = $idx + 1;
    }
    return $map;
}

function wkKoRounds(): array
{
    return [
        'last32' => '1/16 finale',
        'last16' => '1/8 finale',
        'quarterfinal' => 'Kwartfinale',
        'semifinal' => 'Halve finale',
        'final' => 'Finale',
        'third_place' => '3e plaats',
        'champion' => 'Wereldkampioen',
    ];
}

function wkEnsureKoSchema(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS ko_predictions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    participant_id INT UNSIGNED NOT NULL,
    round_key VARCHAR(32) NOT NULL,
    team_name VARCHAR(120) NOT NULL,
    review_status ENUM('0%','50%','99%','OK') NOT NULL DEFAULT 'OK',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ko_prediction (participant_id, round_key, team_name),
    KEY idx_ko_round (round_key),
    CONSTRAINT fk_ko_predictions_participant FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec("ALTER TABLE ko_predictions ADD COLUMN IF NOT EXISTS review_status ENUM('0%','50%','99%','OK') NOT NULL DEFAULT 'OK' AFTER team_name");
    $pdo->exec("UPDATE ko_predictions SET round_key = 'quarterfinal' WHERE round_key = 'quarters'");
    $pdo->exec("UPDATE ko_predictions SET round_key = 'semifinal' WHERE round_key = 'semis'");
}

function wkKoRoundLimits(): array
{
    return [
        'last32' => 32,
        'last16' => 16,
        'quarterfinal' => 8,
        'semifinal' => 4,
        'final' => 2,
        'third_place' => 1,
        'champion' => 1,
    ];
}

function wkKoRoundPoints(): array
{
    return [
        'last32' => 2,
        'last16' => 4,
        'quarterfinal' => 6,
        'semifinal' => 8,
        'final' => 10,
        'third_place' => 5,
        'champion' => 15,
    ];
}

function wkKoImportRoundLabels(): array
{
    return [
        'last32_left' => '1/16 links',
        'last32_right' => '1/16 rechts',
        'last16' => '1/8 finale',
        'quarterfinal' => 'Kwartfinale',
        'semifinal' => 'Halve finale',
        'final' => 'Finale',
        'third_place' => '3e plaats',
        'champion' => 'Wereldkampioen',
    ];
}

function wkKoImportRoundExpectedCounts(): array
{
    return [
        'last32_left' => 16,
        'last32_right' => 16,
        'last16' => 16,
        'quarterfinal' => 8,
        'semifinal' => 4,
        'final' => 2,
        'third_place' => 1,
        'champion' => 1,
    ];
}

function wkKoImportStorageRoundKey(string $roundKey): string
{
    return match ($roundKey) {
        'last32_left', 'last32_right' => 'last32',
        default => $roundKey,
    };
}

function wkNormalizeKoTeamName(PDO $pdo, string $rawValue): ?string
{
    static $lookup = null;
    if ($lookup === null) {
        $lookup = [];
        foreach (wkQualifiedTeams($pdo) as $team) {
            $name = trim((string) ($team['name_de'] ?? $team['name_en'] ?? ''));
            if ($name === '') {
                continue;
            }
            $lookup[mb_strtolower($name)] = $name;
            $lookup[mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name)] = $name;
        }

        $aliases = [
            'nederland' => 'Niederlande',
            'algerije' => 'Algerien',
            'mexico' => 'Mexiko',
            'zuid-afrika' => 'Südafrika',
            'zuid-korea' => 'Südkorea',
            'vs' => 'USA',
            'qatar' => 'Katar',
            'schotland' => 'Schottland',
            'nieuw-zeeland' => 'Neuseeland',
            'kroatie' => 'Kroatien',
            'duitsland' => 'Deutschland',
            'ivoorkust' => 'Elfenbeinküste',
            'kaapverdie' => 'Kap Verde',
            'zwitserland' => 'Schweiz',
            'canada' => 'Kanada',
            'oesbekistan' => 'Usbekistan',
            'engeland' => 'England',
            'jamaica' => 'Jamaika',
            'tunisie' => 'Tunesien',
            'tunesie' => 'Tunesien',
            'equador' => 'Ecuador',
            'kongo' => 'DR Kongo',
            'congo' => 'DR Kongo',
            'curacao' => 'Curaçao',
        ];
        foreach ($aliases as $alias => $canonical) {
            $lookup[$alias] = $canonical;
        }
    }

    $value = trim($rawValue);
    if ($value === '') {
        return null;
    }

    $key = mb_strtolower($value);
    $asciiKey = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value);

    if (isset($lookup[$key])) {
        return $lookup[$key];
    }
    if (isset($lookup[$asciiKey])) {
        return $lookup[$asciiKey];
    }

    $best = null;
    $bestDistance = 99;
    foreach ($lookup as $candidateKey => $candidateValue) {
        $distance = levenshtein($asciiKey, $candidateKey);
        if ($distance < $bestDistance) {
            $bestDistance = $distance;
            $best = $candidateValue;
        }
    }

    return $bestDistance <= 3 ? $best : null;
}

function wkParseKoReviewTextarea(string $text): array
{
    $sections = [];
    $currentKey = null;
    $headerMap = [
        '16e finalisten links' => 'last32_left',
        '16e finalisten rechts' => 'last32_right',
        '1/16 links' => 'last32_left',
        '1/16 rechts' => 'last32_right',
        '8e finalisten' => 'last16',
        '1/8 finale' => 'last16',
        'kwartfinalisten' => 'quarterfinal',
        'kwartfinale' => 'quarterfinal',
        'halve finalisten' => 'semifinal',
        'halve finale' => 'semifinal',
        'finalisten' => 'final',
        'finale' => 'final',
        '3e plaats' => 'third_place',
        'wereldkampioen' => 'champion',
    ];

    foreach (preg_split('/\R/u', $text) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $normalizedHeader = mb_strtolower(trim(preg_replace('/\s*:+\s*$/', '', $line)));
        if (isset($headerMap[$normalizedHeader])) {
            $currentKey = $headerMap[$normalizedHeader];
            $sections[$currentKey] ??= [];
            continue;
        }

        if ($currentKey === null) {
            continue;
        }

        $value = preg_replace('/^\s*(\d+|[-*•])\s*[.)-]?\s*/u', '', $line);
        $value = trim((string) $value);
        if ($value !== '') {
            $sections[$currentKey][] = $value;
        }
    }

    return $sections;
}

function wkResultOutcome(?int $homeScore, ?int $awayScore): ?string
{
    if ($homeScore === null || $awayScore === null) {
        return null;
    }

    if ($homeScore > $awayScore) {
        return 'home';
    }
    if ($awayScore > $homeScore) {
        return 'away';
    }

    return 'draw';
}

function wkPointsForPrediction(?int $homeScore, ?int $awayScore, int $predictedHome, int $predictedAway): int
{
    if ($homeScore === null || $awayScore === null) {
        return 0;
    }

    $actualOutcome = wkResultOutcome($homeScore, $awayScore);
    $predictedOutcome = wkResultOutcome($predictedHome, $predictedAway);
    $points = 0;

    if ($actualOutcome === $predictedOutcome) {
        $points += 3;
    }

    if ($homeScore === $predictedHome) {
        $points += 1;
    }

    if ($awayScore === $predictedAway) {
        $points += 1;
    }

    return min($points, 5);
}

function wkRecalculatePredictionPoints(PDO $pdo): void
{
    $rows = $pdo->query(
        "SELECT pr.id, pr.predicted_home_score, pr.predicted_away_score, m.home_score, m.away_score
         FROM predictions pr
         INNER JOIN matches m ON m.id = pr.match_id"
    )->fetchAll();

    $stmt = $pdo->prepare('UPDATE predictions SET points = :points WHERE id = :id');

    foreach ($rows as $row) {
        $points = wkPointsForPrediction(
            $row['home_score'] !== null ? (int) $row['home_score'] : null,
            $row['away_score'] !== null ? (int) $row['away_score'] : null,
            (int) $row['predicted_home_score'],
            (int) $row['predicted_away_score']
        );

        $stmt->execute([
            ':points' => $points,
            ':id' => (int) $row['id'],
        ]);
    }
}

function wkKoScoreTotals(PDO $pdo): array
{
    wkEnsureKoSchema($pdo);

    $actualByRound = [];
    foreach (wkKoRounds() as $roundKey => $label) {
        $stmt = $pdo->prepare('SELECT team_name FROM ko_predictions WHERE participant_id = 1 AND round_key = :round_key ORDER BY team_name');
        $stmt->execute([':round_key' => $roundKey]);
        $actualByRound[$roundKey] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $pointsByRound = wkKoRoundPoints();
    $scores = [];
    $rows = $pdo->query('SELECT participant_id, round_key, team_name FROM ko_predictions ORDER BY participant_id, round_key, team_name')->fetchAll();
    foreach ($rows as $row) {
        $participantId = (int) $row['participant_id'];
        $roundKey = (string) $row['round_key'];
        $teamName = (string) $row['team_name'];
        if (in_array($teamName, $actualByRound[$roundKey] ?? [], true)) {
            $scores[$participantId] = ($scores[$participantId] ?? 0) + ($pointsByRound[$roundKey] ?? 0);
        }
    }

    return $scores;
}

function wkPageShellStart(string $title, string $active = 'home', string $accent = '#22c55e'): string
{
    $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $items = [
        'home' => ['label' => 'Home', 'href' => 'index.php', 'icon' => '🏠'],
        'participants' => ['label' => 'Deelnemers', 'href' => 'participants.php', 'icon' => '👥'],
        'imports' => ['label' => 'Imports', 'href' => 'imports-overview.php', 'icon' => '📥'],
        'predictions' => ['label' => 'Voorspellingen', 'href' => 'predictions-overview.php', 'icon' => '📊'],
        'ko' => ['label' => 'KO voorsp.', 'href' => 'ko-predictions.php', 'icon' => '🥅'],
        'results' => ['label' => 'Resultaten', 'href' => 'results.php', 'icon' => '✅'],
        'scores' => ['label' => 'Scores', 'href' => 'scores.php', 'icon' => '🏆'],
        'stats' => ['label' => 'Stats', 'href' => 'stats.php', 'icon' => '📈'],
        'countries' => ['label' => 'Landen', 'href' => 'countries.php', 'icon' => '🌍'],
        'rules' => ['label' => 'Regels', 'href' => 'rules.php', 'icon' => '📋'],
        'matches' => ['label' => 'Wedstrijden', 'href' => 'matches.php', 'icon' => '🗓️'],
        'print-ko-proposal' => ['label' => 'Printformulier', 'href' => 'form-print-ko-proposal.php', 'icon' => '🖨️'],
    ];

    $nav = '';
    foreach ($items as $key => $item) {
        $isActive = $key === $active;
        $classes = 'side-nav-link' . ($isActive ? ' active' : '');
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8');
        $icon = htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8');
        $nav .= <<<HTML
            <a href="{$href}" class="{$classes}">
                <span class="side-nav-icon">{$icon}</span>
                <span class="side-nav-text">{$label}</span>
            </a>
        HTML;
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titleEsc}</title>
    <link rel="icon" type="image/jpeg" href="assets/wk2026-logo-new.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="assets/wk2026-logo-new.jpg">
    <style>
        :root {
            --bg-1: #0b1020;
            --bg-2: #111a33;
            --panel: rgba(10, 16, 32, 0.78);
            --panel-border: rgba(255,255,255,0.08);
            --text: #f3f4f6;
            --muted: #cbd5e1;
            --accent: {$accent};
            --accent-soft: rgba(34, 197, 94, 0.14);
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, Arial, sans-serif;
            color: var(--text);
            background:
                linear-gradient(rgba(6, 12, 24, 0.70), rgba(6, 12, 24, 0.78)),
                url('assets/football-field-bg.png') center center / cover fixed no-repeat,
                linear-gradient(135deg, var(--bg-1), var(--bg-2));
            background-color: var(--bg-1);
        }
        .app-shell {
            width: min(1220px, 100% - 24px);
            margin: 0 auto;
            padding: 24px 0 40px;
            display: grid;
            grid-template-columns: var(--nav-width, 200px) minmax(0, 1fr);
            gap: 20px;
            transition: grid-template-columns 0.2s ease;
            position: relative;
            z-index: 1;
        }
        .app-shell::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                linear-gradient(rgba(4, 10, 20, 0.18), rgba(4, 10, 20, 0.36)),
                url('assets/football-field-bg.png') center center / cover no-repeat;
            filter: saturate(1.95) contrast(1.2) brightness(1.08) hue-rotate(-14deg);
            z-index: -2;
        }
        .app-shell::after {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 50% -5%, rgba(255,255,255,0.38), rgba(255,255,255,0.00) 24%),
                radial-gradient(circle at 16% 10%, rgba(57, 255, 20, 0.38), rgba(57, 255, 20, 0.00) 36%),
                radial-gradient(circle at 84% 10%, rgba(0, 229, 255, 0.28), rgba(0, 229, 255, 0.00) 34%),
                radial-gradient(circle at 50% 100%, rgba(166, 255, 0, 0.24), rgba(166, 255, 0, 0.00) 32%),
                linear-gradient(180deg, rgba(6, 12, 24, 0.08), rgba(6, 12, 24, 0.24) 62%, rgba(6, 12, 24, 0.46) 100%);
            z-index: -1;
            pointer-events: none;
        }
        .side-nav {
            position: sticky;
            top: 24px;
            align-self: start;
            background: rgba(7, 11, 22, 0.56);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.28), 0 0 26px rgba(34, 197, 94, 0.10);
            padding: 18px 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: calc(100vh - 48px);
            max-height: calc(100vh - 48px);
            overflow-y: auto;
            backdrop-filter: blur(10px);
        }
        .side-nav-brand {
            display: grid;
            gap: 4px;
            padding: 8px 10px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 4px;
        }
        .side-nav-brand strong { font-size: 1.02rem; }
        .side-nav-brand span { color: var(--muted); font-size: 0.92rem; }
        .side-nav-link,
        .side-nav-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            padding: 9px 12px;
            border-radius: 14px;
            text-decoration: none;
            color: var(--text);
            border: 1px solid transparent;
            background: rgba(255,255,255,0.03);
        }
        .side-nav-toggle {
            width: 100%;
            cursor: pointer;
            font: inherit;
            text-align: left;
        }
        .side-nav-link:hover,
        .side-nav-toggle:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.08);
        }
        .side-nav-link.active {
            background: linear-gradient(90deg, rgba(57, 255, 20, 0.24), rgba(0, 229, 255, 0.14));
            border-color: rgba(120,255,180,0.20);
            box-shadow: 0 0 18px rgba(57, 255, 20, 0.14);
        }
        .side-nav-icon {
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .side-nav-text {
            font-weight: 700;
            white-space: nowrap;
            font-size: 0.94rem;
        }
        .content-shell { min-width: 0; }
        .container {
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .panel {
            background: linear-gradient(180deg, rgba(10, 16, 32, 0.50), rgba(10, 16, 32, 0.64));
            border: 1px solid rgba(120,255,180,0.16);
            border-radius: 22px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.28), inset 0 1px 0 rgba(255,255,255,0.06), 0 0 34px rgba(57, 255, 20, 0.12);
            padding: 22px;
        }
        h1,h2,h3 { margin-top: 0; }
        p, label, td, th, li { color: var(--muted); }
        a { color: #bbf7d0; }
        .nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .nav a, button {
            display: inline-block;
            border: 0;
            border-radius: 12px;
            padding: 11px 16px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }
        .primary { background: var(--accent); color: #07140c; }
        .secondary { background: rgba(255,255,255,0.05); color: var(--text); border: 1px solid var(--panel-border); }
        .danger { background: var(--danger); color: white; }
        form { display: grid; gap: 14px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        input, select {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--panel-border);
            background: rgba(255,255,255,0.04);
            color: var(--text);
            padding: 12px 14px;
        }
        select,
        select option,
        select optgroup {
            color: #0f172a;
            background: #ffffff;
        }
        select {
            color: var(--text);
            background: rgba(255,255,255,0.04);
        }
        select:focus {
            outline: 2px solid rgba(255,255,255,0.14);
            outline-offset: 2px;
        }
        select option:checked,
        select option:hover {
            background: #dbeafe;
            color: #0f172a;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            vertical-align: top;
        }
        .stack { display: grid; gap: 18px; }
        .flash {
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(34, 197, 94, 0.12);
            color: #bbf7d0;
        }
        .warn { background: rgba(245, 158, 11, 0.12); color: #fde68a; }
        .small { font-size: .92rem; }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .badge.ok { background: rgba(34, 197, 94, 0.14); color: #bbf7d0; }
        .badge.warn { background: rgba(245, 158, 11, 0.14); color: #fde68a; }
        .badge.bad { background: rgba(239, 68, 68, 0.14); color: #fecaca; }
        .badge.neutral { background: rgba(148, 163, 184, 0.14); color: #cbd5e1; }
        .toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .muted-box {
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
        }
        .side-nav-spacer {
            flex: 1 1 auto;
            min-height: 12px;
        }
        .side-nav-bottom {
            display: none;
        }
        @media (max-width: 980px) {
            .app-shell {
                width: min(100% - 16px, 1280px);
                grid-template-columns: minmax(0, 1fr);
                gap: 12px;
                padding: 12px 0 88px;
            }
            .side-nav,
            .side-nav.is-collapsed {
                position: static;
                min-height: auto;
                max-height: none;
                width: 100%;
                overflow: visible;
                padding: 12px;
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 8px;
                border-radius: 18px;
            }
            .side-nav-brand,
            .side-nav.is-collapsed .side-nav-brand {
                grid-column: 1 / -1;
                padding: 4px 6px 10px;
                margin-bottom: 0;
                justify-items: start;
                text-align: left;
            }
            .side-nav-brand span,
            .side-nav.is-collapsed .side-nav-brand span {
                display: block;
            }
            .side-nav-link,
            .side-nav-bottom {
                margin: 0;
                position: static;
                background: rgba(255,255,255,0.04);
            }
            .side-nav-link {
                justify-content: flex-start;
                min-height: 48px;
                padding: 10px 12px;
                gap: 10px;
            }
            .side-nav-text {
                display: inline;
                font-size: 0.9rem;
            }
            .side-nav-icon {
                width: 20px;
                font-size: 1.05rem;
            }
            .side-nav-spacer {
                display: none;
            }
        }
        @media (max-width: 720px) {
            .app-shell {
                width: min(100% - 12px, 1280px);
            }
            .side-nav,
            .side-nav.is-collapsed {
                grid-template-columns: 1fr;
                padding: 10px;
                gap: 8px;
                width: 100%;
            }
            .panel {
                padding: 16px;
                border-radius: 18px;
            }
            .nav {
                display: none;
            }
            button {
                width: 100%;
                text-align: center;
            }
            h1 {
                font-size: 1.65rem;
                line-height: 1.15;
            }
            h2 {
                font-size: 1.2rem;
            }
            .grid-2 { grid-template-columns: 1fr; }
            label {
                display: inline-block;
                margin-bottom: 6px;
            }
            input, select {
                padding: 14px;
                font-size: 16px;
            }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr {
                border-bottom: 1px solid rgba(255,255,255,0.08);
                padding: 12px 0;
            }
            td {
                border-bottom: 0;
                padding: 6px 0;
            }
            td::before {
                content: attr(data-label) ": ";
                color: var(--text);
                font-weight: 700;
            }
            .side-nav-link,
            .side-nav-bottom {
                width: 100%;
            }
            .side-nav-link {
                justify-content: flex-start;
                padding: 12px 14px;
                gap: 12px;
            }
            .side-nav-text {
                display: inline;
                font-size: 0.94rem;
            }
            .mobile-tabbar {
                display: none;
            }
        }
        @media (max-width: 560px) {
            .app-shell {
                grid-template-columns: minmax(0, 1fr) !important;
                width: calc(100% - 12px);
                gap: 10px;
            }
            .content-shell {
                width: 100%;
            }
            .side-nav {
                grid-template-columns: 1fr;
                width: 100%;
                min-width: 0;
                padding: 8px;
                border-radius: 16px;
            }
            .side-nav-link {
                min-height: 44px;
                width: 100%;
                padding: 10px 12px;
                border-radius: 12px;
            }
            .side-nav-brand strong {
                font-size: 0.96rem;
            }
            .side-nav-brand span {
                font-size: 0.82rem;
            }
        }
    </style>

</head>
<body>
    <div class="app-shell">
        <aside class="side-nav" id="side-nav">
            <div class="side-nav-brand">
                <strong>WK Pool</strong>
                <span>2026 dashboard</span>
            </div>
{$nav}
            <div class="side-nav-spacer"></div>
        </aside>
        <div class="content-shell">
            <main class="container stack">
HTML;
}

function wkPageShellEnd(): string
{
    return <<<HTML
            </main>
        </div>
    </div>
</body>
</html>
HTML;
}

function wkBaseStyles(string $accent = '#22c55e'): string
{
    return '';
}
