<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
$message = null;
$messageClass = 'flash';
$filter = trim((string) ($_GET['filter'] ?? 'all'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $nameDe = trim((string) ($_POST['name_de'] ?? ''));
        $nameEn = trim((string) ($_POST['name_en'] ?? ''));
        $flagEmoji = trim((string) ($_POST['flag_emoji'] ?? ''));
        $isPlaceholder = !empty($_POST['is_placeholder']) ? 1 : 0;

        if ($id > 0 && $nameDe !== '') {
            $stmt = $pdo->prepare('UPDATE countries SET name_de = :name_de, name_en = :name_en, flag_emoji = :flag_emoji, is_placeholder = :is_placeholder WHERE id = :id');
            $stmt->execute([
                ':id' => $id,
                ':name_de' => $nameDe,
                ':name_en' => $nameEn !== '' ? $nameEn : null,
                ':flag_emoji' => $flagEmoji !== '' ? $flagEmoji : null,
                ':is_placeholder' => $isPlaceholder,
            ]);
            header('Location: countries.php?saved=1');
            exit;
        }

        $message = 'Naam (DE) is verplicht.';
        $messageClass = 'flash warn';
    }
}

if (isset($_GET['saved'])) {
    $message = 'Land opgeslagen.';
}

$where = '';
if ($filter === 'real') {
    $where = 'WHERE is_placeholder = 0';
} elseif ($filter === 'placeholder') {
    $where = 'WHERE is_placeholder = 1';
}

$countries = $pdo->query("SELECT id, name_de, name_en, flag_emoji, is_placeholder FROM countries {$where} ORDER BY is_placeholder ASC, name_de ASC")->fetchAll();
$totals = $pdo->query('SELECT COUNT(*) AS total, SUM(is_placeholder = 0) AS real_count, SUM(is_placeholder = 1) AS placeholder_count FROM countries')->fetch();
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · Landen', 'countries') ?>
<main class="container stack">
    <section class="panel">
        <style>
            .country-kpis {
                display:grid;
                grid-template-columns:repeat(3,minmax(0,1fr));
                gap:12px;
                margin:14px 0 18px;
            }
            .country-kpi {
                padding:14px 16px;
                border-radius:16px;
                background:rgba(255,255,255,0.04);
                border:1px solid rgba(120,255,180,0.14);
            }
            .country-kpi strong {
                display:block;
                font-size:1.4rem;
                color:#d9f99d;
            }
            .country-tools {
                display:flex;
                gap:10px;
                flex-wrap:wrap;
                justify-content:space-between;
                align-items:center;
                margin-bottom:14px;
            }
            .country-filter {
                display:flex;
                gap:8px;
                flex-wrap:wrap;
            }
            .country-filter a {
                padding:8px 12px;
                border-radius:999px;
                text-decoration:none;
                background:rgba(255,255,255,0.05);
                border:1px solid rgba(255,255,255,0.10);
                color:#e5e7eb;
                font-weight:700;
                font-size:0.9rem;
            }
            .country-filter a.active {
                background:rgba(57,255,20,0.18);
                border-color:rgba(120,255,180,0.24);
                color:#f7fee7;
            }
            .countries-table input[type="text"] {
                min-width:0;
            }
            .country-flag-input {
                text-align:center;
                max-width:76px;
            }
            .country-save-btn {
                white-space:nowrap;
            }
        </style>
        <div class="toolbar">
            <div>
                <h1>Landenbeheer</h1>
                <p class="small">Hier beheer je de Duitstalige landen- en placeholdernamen die aan wedstrijden gekoppeld zijn.</p>
            </div>
        </div>
        <div class="country-kpis">
            <div class="country-kpi"><span class="small">Totaal</span><strong><?= (int) ($totals['total'] ?? 0) ?></strong></div>
            <div class="country-kpi"><span class="small">Echte landen</span><strong><?= (int) ($totals['real_count'] ?? 0) ?></strong></div>
            <div class="country-kpi"><span class="small">Placeholders</span><strong><?= (int) ($totals['placeholder_count'] ?? 0) ?></strong></div>
        </div>
        <?php if ($message !== null): ?>
            <div class="<?= htmlspecialchars($messageClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="country-tools">
            <div class="country-filter">
                <a href="countries.php?filter=all" class="<?= $filter === 'all' || $filter === '' ? 'active' : '' ?>">Alles</a>
                <a href="countries.php?filter=real" class="<?= $filter === 'real' ? 'active' : '' ?>">Landen</a>
                <a href="countries.php?filter=placeholder" class="<?= $filter === 'placeholder' ? 'active' : '' ?>">Placeholders</a>
            </div>
            <div class="small">Compact overzicht, direct inline bewerken.</div>
        </div>
        <table class="countries-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Naam (DE)</th>
                    <th>Naam (extra)</th>
                    <th>Vlag</th>
                    <th>Placeholder</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($countries as $country): ?>
                    <tr>
                        <form method="post">
                            <td data-label="ID">
                                <?= (int) $country['id'] ?>
                                <input type="hidden" name="action" value="save">
                                <input type="hidden" name="id" value="<?= (int) $country['id'] ?>">
                            </td>
                            <td data-label="Naam (DE)"><input type="text" name="name_de" value="<?= htmlspecialchars((string) $country['name_de'], ENT_QUOTES, 'UTF-8') ?>" required></td>
                            <td data-label="Naam (extra)"><input type="text" name="name_en" value="<?= htmlspecialchars((string) ($country['name_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td>
                            <td data-label="Vlag"><input class="country-flag-input" type="text" name="flag_emoji" value="<?= htmlspecialchars((string) ($country['flag_emoji'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td>
                            <td data-label="Placeholder"><input type="checkbox" name="is_placeholder" value="1" <?= !empty($country['is_placeholder']) ? 'checked' : '' ?>></td>
                            <td data-label="Actie"><button type="submit" class="primary country-save-btn" style="width:auto;">Opslaan</button></td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?= wkPageShellEnd() ?>
