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

function wkBaseStyles(string $accent = '#22c55e'): string
{
    return <<<CSS
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
        .container {
            width: min(1100px, 100% - 24px);
            margin: 0 auto;
            padding: 24px 0 40px;
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
        @media (max-width: 720px) {
            .container {
                width: min(100% - 16px, 1100px);
                padding: 14px 0 88px;
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
                left: 10px;
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
    </style>
    CSS;
}
