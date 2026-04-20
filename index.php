<?php
require __DIR__ . '/lib.php';

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
<?= wkPageShellStart('WK Pool 2026', 'home') ?>
    <style>
        .hero {
            display: grid;
            grid-template-columns: minmax(0, 7fr) minmax(280px, 3fr);
            gap: 24px;
            align-items: stretch;
        }
        .panel {
            background: rgba(10, 16, 32, 0.78);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.28);
        }
        .hero-main { padding: 36px; }
        .hero-side,
        .menu-panel { padding: 28px; }
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(34, 197, 94, 0.14);
            color: #bbf7d0;
            border: 1px solid rgba(34,197,94,0.22);
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.92rem;
            font-weight: 700;
            margin-bottom: 20px;
            max-width: 100%;
        }
        .hero-brand {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .hero-brand img {
            width: 170px;
            max-width: 32%;
            height: auto;
            border-radius: 18px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.28);
            object-fit: cover;
            flex-shrink: 0;
        }
        .hero-copy { min-width: 0; }
        h1 {
            margin: 0 0 12px;
            font-size: clamp(2.2rem, 7vw, 4.4rem);
            line-height: 1.05;
            overflow-wrap: anywhere;
        }
        .subtitle {
            margin: 0;
            color: #cbd5e1;
            font-size: clamp(1rem, 2.4vw, 1.08rem);
            line-height: 1.7;
            max-width: 60ch;
        }
        .dashboard-row {
            display: block;
            margin-top: 20px;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 0;
        }
        .button {
            display: inline-block;
            padding: 13px 18px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            text-align: center;
        }
        .button-primary,
        .button-secondary {
            background: rgba(56, 189, 248, 0.18);
            border: 1px solid rgba(56, 189, 248, 0.32);
            color: #e0f2fe;
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
        .mini-stat span:first-child { color: #cbd5e1; }
        .mini-stat strong { font-size: 1.1rem; text-align: right; }
        .mini-stat.status-row strong.status-ok-text { color: #86efac; }
        .mini-stat.status-row strong.status-error-text { color: #fca5a5; }
        .cards {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }
        .card { padding: 22px; min-width: 0; }
        .card h2 { margin: 0 0 8px; font-size: 1.05rem; }
        .card p { margin: 0; color: #cbd5e1; line-height: 1.55; }
        .next {
            margin-top: 26px;
            padding: 28px;
        }
        .next h2 { margin: 0 0 12px; }
        .next ul {
            margin: 0;
            padding-left: 20px;
            color: #cbd5e1;
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
        @media (max-width: 900px) {
            .hero {
                grid-template-columns: 1fr;
                gap: 14px;
            }
            .hero-main, .hero-side, .menu-panel, .card, .next { padding: 22px; }
            .cards { grid-template-columns: 1fr; gap: 10px; }
        }
        @media (max-width: 640px) {
            .panel { border-radius: 18px; }
            .hero { gap: 10px; }
            .hero-main, .hero-side, .menu-panel, .card, .next { padding: 15px; }
            .hero-brand {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }
            .hero-brand img {
                width: 140px;
                max-width: 100%;
            }
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
            .actions { flex-direction: column; gap: 8px; }
            .button {
                width: 100%;
                min-height: 48px;
                padding: 14px 16px;
            }
            .mini-stat {
                align-items: flex-start;
                gap: 8px;
                padding: 10px 0;
            }
            .next { margin-top: 12px; }
            .next ul { padding-left: 18px; line-height: 1.65; }
            .footer { font-size: 0.86rem; }
        }
    </style>
                <section class="hero">
            <div class="panel hero-main">
                <div class="hero-brand">
                    <img src="assets/wk2026-logo.jpg" alt="WK 2026 logo">
                    <div class="hero-copy">
                        <div class="tag">⚽ Welkomsscherm</div>
                        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="subtitle"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?>. Dit is de startpagina van jouw poolsite. Vanaf hier kunnen we deelnemers, wedstrijden, voorspellingen en de live ranglijst stap voor stap toevoegen.</p>
                    </div>
                </div>
            </div>

            <aside class="panel hero-side">
                <div>
                    <h2 class="scoreboard-title">Snelle status</h2>
                    <?php foreach ($stats as $label => $value): ?>
                        <?php
                            $rowClass = $label === 'Database' ? 'mini-stat status-row' : 'mini-stat';
                            $valueClass = '';
                            if ($label === 'Database') {
                                $valueClass = $value === 'Verbonden' ? 'status-ok-text' : 'status-error-text';
                            }
                        ?>
                        <div class="<?= htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8') ?>">
                            <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                            <strong class="<?= htmlspecialchars($valueClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
        </section>

        <section class="dashboard-row">
            <div class="panel menu-panel">
                <div class="actions">
                    <a class="button button-primary" href="participants.php">Deelnemers beheren</a>
                    <a class="button button-secondary" href="matches.php">Wedstrijden beheren</a>
                    <a class="button button-secondary" href="form-print.php">Printformulier</a>
                    <a class="button button-secondary" href="predictions-overview.php">Voorspellingen</a>
                    <a class="button button-secondary" href="imports-overview.php">Imports</a>
                    <a class="button button-secondary" href="rules.php">Regels</a>
                    <a class="button button-secondary" href="#volgende-stappen">Verder bouwen</a>
                </div>
            </div>
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
<?= wkPageShellEnd() ?>
