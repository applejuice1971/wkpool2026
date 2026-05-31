<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkEnsureImportSchema($pdo);
wkEnsureKoSchema($pdo);
wkEnsurePredictionReviewSchema($pdo);

$message = null;
$messageClass = 'flash';

$reviewStatuses = wkReviewStatuses();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save_prediction') {
        $predictionId = (int) ($_POST['prediction_id'] ?? 0);
        $home = trim((string) ($_POST['predicted_home_score'] ?? ''));
        $away = trim((string) ($_POST['predicted_away_score'] ?? ''));
        $reviewStatus = (string) ($_POST['review_status'] ?? 'OK');
        if (!isset($reviewStatuses[$reviewStatus])) {
            $reviewStatus = 'OK';
        }
        if ($predictionId > 0 && ctype_digit($home) && ctype_digit($away)) {
            $stmt = $pdo->prepare('UPDATE predictions SET predicted_home_score = :home, predicted_away_score = :away, review_status = :review_status WHERE id = :id');
            $stmt->execute([
                ':home' => (int) $home,
                ':away' => (int) $away,
                ':review_status' => $reviewStatus,
                ':id' => $predictionId,
            ]);
            wkRecalculatePredictionPoints($pdo);
            header('Location: predictions-overview.php?saved=1');
            exit;
        }
        $message = 'Ongeldige wijziging.';
        $messageClass = 'flash warn';
    } elseif ($action === 'bulk_status') {
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['prediction_ids'] ?? []))));
        $bulkStatus = (string) ($_POST['bulk_review_status'] ?? '');
        if ($ids && isset($reviewStatuses[$bulkStatus])) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE predictions SET review_status = ? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$bulkStatus], $ids));
            header('Location: predictions-overview.php?saved=1');
            exit;
        }
        $message = 'Kies minimaal één voorspelling en een geldige status.';
        $messageClass = 'flash warn';
    }
}

if (isset($_GET['saved'])) {
    $message = 'Voorspelling opgeslagen.';
}

$summary = $pdo->query("SELECT COUNT(*) AS total_predictions, COUNT(DISTINCT participant_id) AS total_participants FROM predictions")->fetch();
$imports = $pdo->query("SELECT COUNT(*) AS total_imports, SUM(status = 'imported') AS imported_count, SUM(status = 'review_needed') AS review_count FROM prediction_imports")->fetch();

$nameFilter = trim((string) ($_GET['name'] ?? ''));
$matchFilter = trim((string) ($_GET['match'] ?? ''));
$stageFilter = trim((string) ($_GET['stage'] ?? ''));
$dateFilter = trim((string) ($_GET['date'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'participant_name');
$dir = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

$nameOptions = $pdo->query("SELECT DISTINCT name FROM participants ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$stageOptions = $pdo->query("SELECT DISTINCT stage FROM matches ORDER BY stage ASC")->fetchAll(PDO::FETCH_COLUMN);
$dateOptions = $pdo->query("SELECT DISTINCT DATE(match_date) FROM matches ORDER BY DATE(match_date) ASC")->fetchAll(PDO::FETCH_COLUMN);
$matchOptions = $pdo->query("SELECT m.id, ch.name_de AS home_country_name, ca.name_de AS away_country_name FROM matches m INNER JOIN countries ch ON ch.id = m.home_country_id INNER JOIN countries ca ON ca.id = m.away_country_id WHERE m.stage LIKE 'Group %' ORDER BY m.match_date ASC, m.id ASC")->fetchAll();
$groupMatchNumbers = [];
foreach ($matchOptions as $idx => $option) {
    $groupMatchNumbers[(int) $option['id']] = $idx + 1;
}
$participantOptions = $pdo->query("SELECT id, name FROM participants ORDER BY name ASC")->fetchAll();

$sql = <<<SQL
SELECT
    pr.id,
    m.id AS match_id,
    p.name AS participant_name,
    m.stage,
    m.match_date,
    ch.name_de AS home_country_name,
    ca.name_de AS away_country_name,
    pr.predicted_home_score,
    pr.predicted_away_score,
    pr.review_status,
    pr.points,
    pr.updated_at
FROM predictions pr
INNER JOIN participants p ON p.id = pr.participant_id
INNER JOIN matches m ON m.id = pr.match_id
INNER JOIN countries ch ON ch.id = m.home_country_id
INNER JOIN countries ca ON ca.id = m.away_country_id
WHERE 1=1
SQL;
$params = [];

if ($nameFilter !== '') {
    $sql .= " AND p.name LIKE :name";
    $params[':name'] = '%' . $nameFilter . '%';
}
if ($matchFilter !== '') {
    $sql .= " AND m.id = :matchExact";
    $params[':matchExact'] = (int) $matchFilter;
}
if ($stageFilter !== '') {
    $sql .= " AND m.stage = :stage";
    $params[':stage'] = $stageFilter;
}
if ($dateFilter !== '') {
    $sql .= " AND DATE(m.match_date) = :matchDate";
    $params[':matchDate'] = $dateFilter;
}

$sortMap = [
    'match_number' => 'm.id',
    'participant_name' => 'p.name',
    'match_label' => 'ch.name_de',
    'stage' => 'm.stage',
    'match_date' => 'm.match_date',
    'prediction' => 'pr.predicted_home_score',
    'points' => 'pr.points',
];
$orderBy = $sortMap[$sort] ?? 'p.name';
$sql .= " ORDER BY {$orderBy} " . strtoupper($dir) . ", m.match_date ASC, m.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

function sortLink(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    return 'predictions-overview.php?' . http_build_query($params);
}
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · Voorspellingen overzicht', 'predictions') ?>
<div class="container stack">
        <section class="panel">
        <style>
            .predictions-compact table {
                font-size: 0.88rem;
            }
            .predictions-compact th,
            .predictions-compact td {
                padding: 7px 8px;
                line-height: 1.2;
            }
            .predictions-compact .small {
                font-size: 0.8rem;
            }
            .predictions-compact th a {
                color: inherit;
                text-decoration: none;
            }
            .predictions-compact .filter-row input,
            .predictions-compact .filter-row select {
                width: 100%;
                padding: 6px 8px;
                min-width: 0;
                background: #dbeafe;
                color: #0f172a;
                border: 1px solid #93c5fd;
            }
            .predictions-compact .filter-row select option {
                background: #dbeafe;
                color: #0f172a;
            }
        </style>
        <div class="toolbar">
            <div>
                <h1>Voorspellingen overzicht</h1>
                <p class="small">Werk hier de OCR-statussen af en bewerk losse voorspellingen per regel.</p>
            </div>
            <div class="small">
                <strong><?= (int) ($summary['total_predictions'] ?? 0) ?></strong> voorspellingen, 
                <strong><?= (int) ($summary['total_participants'] ?? 0) ?></strong> deelnemers, 
                <strong><?= (int) ($imports['total_imports'] ?? 0) ?></strong> imports
            </div>
        </div>

        <?php if ($message !== null): ?>
            <div class="<?= htmlspecialchars($messageClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form id="bulk-status-form" method="post" class="muted-box" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap; margin-bottom:16px;">
            <input type="hidden" name="action" value="bulk_status">
            <div>
                <label for="bulk_review_status">Bulk status</label>
                <select id="bulk_review_status" name="bulk_review_status" style="width:110px;">
                    <option value="">Kies status</option>
                    <?php foreach ($reviewStatuses as $statusValue => $statusLabel): ?>
                        <option value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="primary" style="width:auto;">Pas toe op selectie</button>
            <span class="small">Selecteer regels hieronder met de vinkjes.</span>
        </form>

        <?php if (!$rows): ?>
            <div class="muted-box">Er staan nog geen voorspellingen in de database.</div>
        <?php else: ?>
            <table class="predictions-compact">
                <thead>
                    <tr>
                        <th></th>
                        <th>Actie</th>
                        <th><a href="<?= htmlspecialchars(sortLink(['sort' => 'match_label', 'dir' => $sort === 'match_label' && $dir === 'asc' ? 'desc' : 'asc']) , ENT_QUOTES, 'UTF-8') ?>">Wedstrijd</a></th>
                        <th><a href="<?= htmlspecialchars(sortLink(['sort' => 'participant_name', 'dir' => $sort === 'participant_name' && $dir === 'asc' ? 'desc' : 'asc']) , ENT_QUOTES, 'UTF-8') ?>">Deelnemer</a></th>
                        <th><a href="<?= htmlspecialchars(sortLink(['sort' => 'stage', 'dir' => $sort === 'stage' && $dir === 'asc' ? 'desc' : 'asc']) , ENT_QUOTES, 'UTF-8') ?>">Groep/fase</a></th>
                        <th><a href="<?= htmlspecialchars(sortLink(['sort' => 'match_date', 'dir' => $sort === 'match_date' && $dir === 'asc' ? 'desc' : 'asc']) , ENT_QUOTES, 'UTF-8') ?>">Datum</a></th>
                        <th><a href="<?= htmlspecialchars(sortLink(['sort' => 'prediction', 'dir' => $sort === 'prediction' && $dir === 'asc' ? 'desc' : 'asc']) , ENT_QUOTES, 'UTF-8') ?>">Voorspelling</a></th>
                        <th>Status</th>
                        <th><a href="<?= htmlspecialchars(sortLink(['sort' => 'points', 'dir' => $sort === 'points' && $dir === 'asc' ? 'desc' : 'asc']) , ENT_QUOTES, 'UTF-8') ?>">Punten</a></th>
                    </tr>
                    <tr class="filter-row">
                        <th></th>
                        <th></th>
                        <th>
                            <select name="match" form="prediction-filters">
                                <option value="">Alle wedstrijden</option>
                                <?php foreach ($matchOptions as $option): ?>
                                    <?php $displayNumber = $groupMatchNumbers[(int) $option['id']] ?? (int) $option['id']; ?>
                                    <option value="<?= (int) $option['id'] ?>" <?= $matchFilter === (string) $option['id'] ? 'selected' : '' ?>>#<?= $displayNumber ?> <?= htmlspecialchars(($option['home_country_name'] ?? '') . ' - ' . ($option['away_country_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </th>
                        <th>
                            <select name="name" form="prediction-filters">
                                <option value="">Alle namen</option>
                                <?php foreach ($nameOptions as $option): ?>
                                    <option value="<?= htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8') ?>" <?= $nameFilter === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </th>
                        <th>
                            <select name="stage" form="prediction-filters">
                                <option value="">Alle groepen/fases</option>
                                <?php foreach ($stageOptions as $option): ?>
                                    <option value="<?= htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8') ?>" <?= $stageFilter === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </th>
                        <th>
                            <select name="date" form="prediction-filters">
                                <option value="">Alle datums</option>
                                <?php foreach ($dateOptions as $option): ?>
                                    <option value="<?= htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8') ?>" <?= $dateFilter === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </th>
                        <th></th>
                        <th colspan="2">
                            <form id="prediction-filters" method="get" style="display:flex; gap:8px; align-items:center;">
                                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="dir" value="<?= htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="primary">Filter</button>
                                <a href="predictions-overview.php" class="secondary">Reset</a>
                            </form>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php $displayNumber = $groupMatchNumbers[(int) $row['match_id']] ?? (int) $row['match_id']; ?>
                            <td data-label="Selectie"><input type="checkbox" name="prediction_ids[]" value="<?= (int) $row['id'] ?>" form="bulk-status-form"></td>
                            <td data-label="Actie">
                                <details>
                                    <summary><a href="#" onclick="return false;" class="secondary" style="display:inline-block;">Edit</a></summary>
                                    <form method="post" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap; margin-top:8px;">
                                        <input type="hidden" name="action" value="save_prediction">
                                        <input type="hidden" name="prediction_id" value="<?= (int) $row['id'] ?>">
                                        <div>
                                            <label>Thuis</label>
                                            <select name="predicted_home_score" style="width:72px;">
                                                <?php for ($i = 0; $i <= 9; $i++): ?><option value="<?= $i ?>" <?= (int) $row['predicted_home_score'] === $i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label>Uit</label>
                                            <select name="predicted_away_score" style="width:72px;">
                                                <?php for ($i = 0; $i <= 9; $i++): ?><option value="<?= $i ?>" <?= (int) $row['predicted_away_score'] === $i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label>Status</label>
                                            <select name="review_status" style="width:90px;">
                                                <?php foreach ($reviewStatuses as $statusValue => $statusLabel): ?>
                                                    <option value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) $row['review_status']) === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="primary" style="width:auto;">Bewaar</button>
                                    </form>
                                </details>
                            </td>
                            <td data-label="Wedstrijd">#<?= $displayNumber ?> <?= htmlspecialchars(wkMatchLabel($row), ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Deelnemer"><?= htmlspecialchars($row['participant_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Groep/fase"><?= htmlspecialchars($row['stage'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Datum"><?= htmlspecialchars(date('d-m-Y H:i', strtotime((string) $row['match_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Voorspelling"><strong><?= (int) $row['predicted_home_score'] ?> - <?= (int) $row['predicted_away_score'] ?></strong></td>
                            <td data-label="Status"><span class="badge <?= htmlspecialchars(wkReviewStatusBadgeClass((string) $row['review_status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $row['review_status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td data-label="Punten"><?= (int) $row['points'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <div class="mobile-tabbar">
        <a href="index.php">Home</a>
        <a class="active" href="predictions-overview.php">Voorsp.</a>
        <a href="imports-overview.php">Imports</a>
    </div>
</div>
<?= wkPageShellEnd() ?>

