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
}

function wkImportStoragePath(): string
{
    $dir = __DIR__ . '/uploads/prediction-imports';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return $dir;
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

function wkPageShellStart(string $title, string $active = 'home', string $accent = '#22c55e'): string
{
    $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $items = [
        'home' => ['label' => 'Home', 'href' => 'index.php', 'icon' => '🏠'],
        'participants' => ['label' => 'Deelnemers', 'href' => 'participants.php', 'icon' => '👥'],
        'matches' => ['label' => 'Wedstrijden', 'href' => 'matches.php', 'icon' => '🗓️'],
        'print' => ['label' => 'Printformulier', 'href' => 'form-print.php', 'icon' => '🖨️'],
        'rules' => ['label' => 'Regels', 'href' => 'rules.php', 'icon' => '📋'],
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
            background: linear-gradient(135deg, var(--bg-1), var(--bg-2));
        }
        .app-shell {
            width: min(1280px, 100% - 24px);
            margin: 0 auto;
            padding: 24px 0 40px;
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            gap: 20px;
        }
        .side-nav {
            position: sticky;
            top: 24px;
            align-self: start;
            background: rgba(7, 11, 22, 0.88);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.28);
            padding: 18px 14px;
            display: grid;
            gap: 10px;
            backdrop-filter: blur(10px);
        }
        .side-nav-brand {
            display: grid;
            gap: 4px;
            padding: 8px 10px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 4px;
        }
        .side-nav-brand strong {
            font-size: 1.02rem;
        }
        .side-nav-brand span {
            color: var(--muted);
            font-size: 0.92rem;
        }
        .side-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 52px;
            padding: 12px 14px;
            border-radius: 16px;
            text-decoration: none;
            color: var(--text);
            border: 1px solid transparent;
            background: rgba(255,255,255,0.03);
            transition: background 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
        }
        .side-nav-link:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.08);
            transform: translateX(2px);
        }
        .side-nav-link.active {
            background: var(--accent-soft);
            border-color: rgba(255,255,255,0.12);
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
        }
        .content-shell {
            min-width: 0;
        }
        .container {
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 22px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.28);
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
        form {
            display: grid;
            gap: 14px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        input, select {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--panel-border);
            background: rgba(255,255,255,0.04);
            color: var(--text);
            padding: 12px 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            vertical-align: top;
        }
        .stack {
            display: grid;
            gap: 18px;
        }
        .flash {
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(34, 197, 94, 0.12);
            color: #bbf7d0;
        }
        .warn {
            background: rgba(245, 158, 11, 0.12);
            color: #fde68a;
        }
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
        @media (max-width: 980px) {
            .app-shell {
                width: min(100% - 16px, 1280px);
                grid-template-columns: 84px minmax(0, 1fr);
                gap: 14px;
                padding: 14px 0 88px;
            }
            .side-nav {
                padding: 14px 10px;
            }
            .side-nav-brand span,
            .side-nav-text {
                display: none;
            }
            .side-nav-brand {
                justify-items: center;
                text-align: center;
                padding-inline: 0;
            }
            .side-nav-link {
                justify-content: center;
                padding: 12px;
            }
            .side-nav-icon {
                width: auto;
            }
        }
        @media (max-width: 720px) {
            .app-shell {
                grid-template-columns: 72px minmax(0, 1fr);
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
            .mobile-tabbar {
                position: fixed;
                left: 88px;
                right: 10px;
                bottom: 10px;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                padding: 8px;
                background: rgba(8, 12, 24, 0.96);
                border: 1px solid rgba(255,255,255,0.08);
                border-radius: 18px;
                backdrop-filter: blur(12px);
                box-shadow: 0 12px 30px rgba(0,0,0,0.35);
                z-index: 50;
            }
            .mobile-tabbar a {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 46px;
                padding: 10px 8px;
                border-radius: 12px;
                text-decoration: none;
                text-align: center;
                font-weight: 700;
                font-size: 0.92rem;
                color: var(--text);
                background: rgba(255,255,255,0.04);
                border: 1px solid rgba(255,255,255,0.06);
            }
            .mobile-tabbar a.active {
                background: var(--accent);
                color: #07140c;
            }
        }
        @media (max-width: 560px) {
            .app-shell {
                grid-template-columns: 64px minmax(0, 1fr);
                width: min(100% - 12px, 1280px);
            }
            .side-nav {
                padding: 10px 8px;
                border-radius: 20px;
            }
            .mobile-tabbar {
                left: 78px;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="side-nav">
            <div class="side-nav-brand">
                <strong>WK Pool</strong>
                <span>2026 dashboard</span>
            </div>
{$nav}
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
