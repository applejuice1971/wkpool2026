<?php
$title = 'WK Pool 2026';
$subtitle = 'Welkom bij jouw WK-poule voor 2026';

function loadEnv(string $path): array
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

        $value = trim($value, "\"'");
        $vars[$key] = $value;
    }

    return $vars;
}

$env = loadEnv(__DIR__ . '/.env');
$dbStatus = 'Niet verbonden';
$dbStatusClass = 'status-warning';
$dbError = null;
$pdo = null;
$stats = [
    'Deelnemers' => 0,
    'Ingevulde voorspellingen' => 0,
    'Verwerkte wedstrijden' => 0,
    'Database' => 'Niet verbonden',
];

if (($env['DB_CONNECTION'] ?? '') === 'mysql') {
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $database = $env['DB_DATABASE'] ?? '';
    $username = $env['DB_USERNAME'] ?? '';
    $password = $env['DB_PASSWORD'] ?? '';

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $dbStatus = 'Verbonden';
        $dbStatusClass = 'status-ok';
        $stats['Database'] = 'Verbonden';

        $stats['Deelnemers'] = (int) $pdo->query('SELECT COUNT(*) FROM participants')->fetchColumn();
        $stats['Ingevulde voorspellingen'] = (int) $pdo->query('SELECT COUNT(*) FROM predictions')->fetchColumn();
        $stats['Verwerkte wedstrijden'] = (int) $pdo->query("SELECT COUNT(*) FROM matches WHERE status = 'finished'")->fetchColumn();
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
        $stats['Database'] = 'Fout';
    }
} else {
    $dbError = 'DB_CONNECTION staat niet op mysql in .env';
    $stats['Database'] = 'Config check';
}

$features = [
    ['title' => '👥 Deelnemers', 'text' => 'Beheer snel alle spelers in je poule.'],
    ['title' => '🗓️ Wedstrijden', 'text' => 'Het volledige WK-schema staat al klaar in de database.'],
    ['title' => '🏆 Stand', 'text' => 'Bouw hierna voorspellingen en de ranking erbovenop.'],
];

$nextSteps = [
    'Deelnemers toevoegen',
    'Voorspellingen bouwen',
    'Punten en ranking tonen',
];
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --bg-1: #0b1020;
            --bg-2: #111a33;
            --panel: rgba(10, 16, 32, 0.78);
            --panel-border: rgba(255,255,255,0.08);
            --text: #f3f4f6;
            --muted: #cbd5e1;
            --accent: #22c55e;
            --accent-2: #16a34a;
            --accent-soft: rgba(34, 197, 94, 0.14);
            --warn: #f59e0b;
            --warn-soft: rgba(245, 158, 11, 0.16);
        }

        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(34,197,94,0.20), transparent 30%),
                radial-gradient(circle at right center, rgba(59,130,246,0.18), transparent 25%),
                linear-gradient(135deg, var(--bg-1), var(--bg-2));
        }

        .container {
            width: min(1120px, 100% - 32px);
            margin: 0 auto;
            padding: 40px 0 56px;
        }

        .hero {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            align-items: stretch;
        }

        .panel {
            background: var(--panel);
            backdrop-filter: blur(8px);
            border: 1px solid var(--panel-border);
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.28);
        }

        .hero-main { padding: 36px; }
        .hero-side {
            padding: 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent-soft);
            color: #bbf7d0;
            border: 1px solid rgba(34,197,94,0.22);
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.92rem;
            font-weight: 700;
            margin-bottom: 20px;
            max-width: 100%;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(2.2rem, 7vw, 4.4rem);
            line-height: 1.05;
            overflow-wrap: anywhere;
        }

        .subtitle {
            margin: 0;
            color: var(--muted);
            font-size: clamp(1rem, 2.4vw, 1.08rem);
            line-height: 1.7;
            max-width: 60ch;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .button {
            display: inline-block;
            padding: 13px 18px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            text-align: center;
        }

        .button-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: white;
            box-shadow: 0 12px 24px rgba(34,197,94,0.25);
        }

        .button-secondary {
            border: 1px solid var(--panel-border);
            color: var(--text);
            background: rgba(255,255,255,0.03);
        }

        .db-banner {
            margin-top: 22px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid var(--panel-border);
            background: rgba(255,255,255,0.03);
        }

        .db-banner strong { display: block; margin-bottom: 6px; }
        .db-banner p { margin: 0; color: var(--muted); line-height: 1.6; }
        .status-ok {
            color: #86efac;
            background: rgba(34, 197, 94, 0.10);
            border-color: rgba(34, 197, 94, 0.24);
        }
        .status-warning {
            color: #fcd34d;
            background: var(--warn-soft);
            border-color: rgba(245, 158, 11, 0.24);
        }

        .scoreboard-title {
            margin: 0 0 18px;
            font-size: 1.1rem;
        }

        .mini-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .mini-stat:last-child { border-bottom: 0; }
        .mini-stat span:first-child { color: var(--muted); }
        .mini-stat strong { font-size: 1.1rem; text-align: right; }

        .cards {
            margin-top: 20px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .card { padding: 22px; min-width: 0; }
        .card h2 { margin: 0 0 8px; font-size: 1.05rem; }
        .card p { margin: 0; color: var(--muted); line-height: 1.55; }

        .next {
            margin-top: 26px;
            padding: 28px;
        }

        .next h2 { margin: 0 0 12px; }
        .next ul {
            margin: 0;
            padding-left: 20px;
            color: var(--muted);
            line-height: 1.9;
        }

        .footer {
            margin-top: 18px;
            color: #94a3b8;
            font-size: 0.95rem;
            overflow-wrap: anywhere;
        }

        code {
            color: #bfdbfe;
            background: rgba(255,255,255,0.04);
            padding: 2px 6px;
            border-radius: 8px;
        }

        @media (min-width: 901px) {
            .hero {
                grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr);
                gap: 24px;
            }
            .cards {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
            }
        }

        @media (max-width: 900px) {
            .hero-main, .hero-side, .card, .next { padding: 22px; }
        }

        @media (max-width: 640px) {
            .container {
                width: min(100% - 12px, 1120px);
                padding-top: 10px;
                padding-bottom: 18px;
            }

            .panel { border-radius: 18px; }
            .hero { gap: 10px; }
            .hero-main, .hero-side, .card, .next { padding: 15px; }
            .tag {
                font-size: 0.8rem;
                padding: 6px 10px;
                margin-bottom: 10px;
            }
            h1 {
                font-size: clamp(1.8rem, 9vw, 2.25rem);
                line-height: 1.04;
                margin-bottom: 8px;
            }
            .subtitle {
                font-size: 0.95rem;
                line-height: 1.5;
            }
            .actions { flex-direction: column; gap: 8px; margin-top: 18px; }
            .button {
                width: 100%;
                min-height: 48px;
                padding: 14px 16px;
            }
            .cards {
                grid-template-columns: 1fr;
                gap: 10px;
                margin-top: 12px;
            }
            .card h2 {
                font-size: 1rem;
                margin-bottom: 6px;
            }
            .card p {
                font-size: 0.93rem;
                line-height: 1.45;
            }
            .mini-stat {
                align-items: flex-start;
                flex-direction: row;
                justify-content: space-between;
                gap: 8px;
                padding: 10px 0;
            }
            .mini-stat strong { text-align: right; }
            .next {
                margin-top: 12px;
            }
            .next ul { padding-left: 18px; line-height: 1.65; }
            .footer { font-size: 0.86rem; }
        }
    </style>
</head>
<body>
    <main class="container">
        <section class="hero">
            <div class="panel hero-main">
                <div class="tag">⚽ Welkomsscherm</div>
                <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="subtitle"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?>. Dit is de startpagina van jouw poolsite. Vanaf hier kunnen we deelnemers, wedstrijden, voorspellingen en de live ranglijst stap voor stap toevoegen.</p>

                <div class="actions">
                    <a class="button button-primary" href="participants.php">Deelnemers beheren</a>
                    <a class="button button-secondary" href="matches.php">Wedstrijden beheren</a>
                    <a class="button button-secondary" href="form-print.php">Printformulier</a>
                    <a class="button button-secondary" href="rules.php">Regels</a>
                    <a class="button button-secondary" href="#volgende-stappen">Verder bouwen</a>
                </div>

                <div class="db-banner <?= htmlspecialchars($dbStatusClass, ENT_QUOTES, 'UTF-8') ?>">
                    <strong>Database status: <?= htmlspecialchars($dbStatus, ENT_QUOTES, 'UTF-8') ?></strong>
                    <p>
                        <?= $dbError === null
                            ? 'De MySQL-verbinding is succesvol opgezet via de waarden uit .env en de basis-tabellen zijn klaar.'
                            : 'De koppeling gebruikt .env, maar geeft nu nog een fout: ' . htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </div>

            <aside class="panel hero-side">
                <div>
                    <h2 class="scoreboard-title">Snelle status</h2>
                    <?php foreach ($stats as $label => $value): ?>
                        <div class="mini-stat">
                            <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="footer">Tip: de database-instellingen worden nu gelezen uit <code>.env</code>. De volgende stap is echte schermen bouwen voor deelnemers, wedstrijden en voorspellingen.</p>
            </aside>
        </section>

        <section class="cards">
            <?php foreach ($features as $feature): ?>
                <article class="panel card">
                    <h2><?= htmlspecialchars($feature['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= htmlspecialchars($feature['text'], ENT_QUOTES, 'UTF-8') ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="panel next" id="volgende-stappen">
            <h2>Volgende stappen</h2>
            <ul>
                <?php foreach ($nextSteps as $step): ?>
                    <li><?= htmlspecialchars($step, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="footer">Lokale host: <code>php -S 0.0.0.0:8086 -t /home/pi/.openclaw/workspace/sites/wkpool-backup/unpacked/wkpool2026</code></p>
        </section>
    </main>
</body>
</html>
