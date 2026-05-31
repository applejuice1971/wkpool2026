<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkEnsureKoSchema($pdo);
$message = null;
$messageClass = 'flash';

$rounds = wkKoRounds();
$roundLimits = wkKoRoundLimits();
$teams = array_map(static fn(array $team): string => (string) ($team['name_de'] ?? $team['name_en'] ?? ''), wkQualifiedTeams($pdo));
$groupMatches = $pdo->query(
    "SELECT m.id, m.stage, m.match_date, m.home_score, m.away_score, m.status, ch.name_de AS home_country_name, ca.name_de AS away_country_name
     FROM matches m
     INNER JOIN countries ch ON ch.id = m.home_country_id
     INNER JOIN countries ca ON ca.id = m.away_country_id
     WHERE m.stage LIKE 'Group %'
     ORDER BY m.match_date ASC, m.id ASC"
)->fetchAll();
$groupMatchNumbers = wkGroupMatchNumberMap($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saveKoResults = isset($_POST['save_ko_results']);
    $saveSingleGroupMatch = isset($_POST['save_group_match_id']);
    $saveAllGroupResults = isset($_POST['save_group_results']);

    if ($saveKoResults) {
        foreach ($rounds as $roundKey => $roundLabel) {
            $selected = array_values(array_unique(array_filter(array_map('trim', (array) ($_POST['results'][$roundKey] ?? [])))));
            $limit = (int) ($roundLimits[$roundKey] ?? 0);
            if (count($selected) !== $limit) {
                $message = $roundLabel . ' moet precies ' . $limit . ' landen bevatten.';
                $messageClass = 'flash warn';
                break;
            }
            $delete = $pdo->prepare('DELETE FROM ko_predictions WHERE participant_id = 1 AND round_key = :round_key');
            $delete->execute([':round_key' => $roundKey]);
            $insert = $pdo->prepare("INSERT INTO ko_predictions (participant_id, round_key, team_name, review_status) VALUES (1, :round_key, :team_name, 'OK')");
            foreach ($selected as $teamName) {
                $insert->execute([
                    ':round_key' => $roundKey,
                    ':team_name' => $teamName,
                ]);
            }
        }
    }

    if ($message === null && ($saveSingleGroupMatch || $saveAllGroupResults) && isset($_POST['group_results'])) {
        $updateGroup = $pdo->prepare("UPDATE matches SET home_score = :home_score, away_score = :away_score, status = :status WHERE id = :id");
        $requestedMatchId = $saveSingleGroupMatch ? (int) $_POST['save_group_match_id'] : null;
        foreach ((array) $_POST['group_results'] as $matchId => $values) {
            $matchId = (int) $matchId;
            if ($requestedMatchId !== null && $matchId !== $requestedMatchId) {
                continue;
            }
            $homeScoreRaw = trim((string) ($values['home_score'] ?? ''));
            $awayScoreRaw = trim((string) ($values['away_score'] ?? ''));
            if ($homeScoreRaw === '' || $awayScoreRaw === '') {
                $updateGroup->execute([
                    ':home_score' => null,
                    ':away_score' => null,
                    ':status' => 'scheduled',
                    ':id' => $matchId,
                ]);
                continue;
            }
            if (!ctype_digit($homeScoreRaw) || !ctype_digit($awayScoreRaw)) {
                $message = 'Groepsscores moeten hele getallen zijn.';
                $messageClass = 'flash warn';
                break;
            }
            $updateGroup->execute([
                ':home_score' => (int) $homeScoreRaw,
                ':away_score' => (int) $awayScoreRaw,
                ':status' => 'finished',
                ':id' => $matchId,
            ]);
        }
    }

    if ($message === null) {
        wkRecalculatePredictionPoints($pdo);
        $savedSection = $saveKoResults ? 'ko' : 'group';
        header('Location: results.php?saved=' . $savedSection);
        exit;
    }
}

if (isset($_GET['saved'])) {
    $message = match ((string) $_GET['saved']) {
        'group' => 'Groepsuitslag opgeslagen en scores opnieuw berekend.',
        'ko' => 'KO-resultaten opgeslagen en scores opnieuw berekend.',
        default => 'Wijzigingen opgeslagen en scores opnieuw berekend.',
    };
}

$currentResults = [];
$stmt = $pdo->prepare('SELECT round_key, team_name FROM ko_predictions WHERE participant_id = 1 ORDER BY id ASC');
$stmt->execute();
foreach ($stmt->fetchAll() as $row) {
    $currentResults[$row['round_key']][] = (string) $row['team_name'];
}
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · Resultaten', 'results') ?>
<main class="container stack">
    <section class="panel stack">
        <div class="toolbar">
            <div>
                <h1>Resultaten</h1>
                <p class="small">Beheer hier zowel de groepsuitslagen als per KO-ronde de landen die doorgaan.</p>
            </div>
        </div>

        <?php if ($message !== null): ?>
            <div class="<?= htmlspecialchars($messageClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" class="stack">
            <section class="panel" style="padding:16px; background:rgba(255,255,255,0.03);">
                <h2>Groepswedstrijden</h2>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Wedstrijd</th>
                                <th>Datum</th>
                                <th>Uitslag</th>
                                <th>Actie</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groupMatches as $match): ?>
                                <?php $displayNumber = $groupMatchNumbers[(int) $match['id']] ?? (int) $match['id']; ?>
                                <tr>
                                    <td>#<?= $displayNumber ?></td>
                                    <td><strong><?= htmlspecialchars(wkMatchLabel($match), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><?= htmlspecialchars(date('d-m-Y H:i', strtotime((string) $match['match_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:nowrap; white-space:nowrap;">
                                            <select name="group_results[<?= (int) $match['id'] ?>][home_score]" style="width:72px;">
                                                <option value=""></option>
                                                <?php for ($i = 0; $i <= 9; $i++): ?>
                                                    <option value="<?= $i ?>" <?= $match['home_score'] !== null && (int) $match['home_score'] === $i ? 'selected' : '' ?>><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <span>-</span>
                                            <select name="group_results[<?= (int) $match['id'] ?>][away_score]" style="width:72px;">
                                                <option value=""></option>
                                                <?php for ($i = 0; $i <= 9; $i++): ?>
                                                    <option value="<?= $i ?>" <?= $match['away_score'] !== null && (int) $match['away_score'] === $i ? 'selected' : '' ?>><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="submit" name="save_group_match_id" value="<?= (int) $match['id'] ?>" class="primary" style="width:auto;">Save</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" style="padding:16px; background:rgba(255,255,255,0.03);">
                <h2>KO-resultaten</h2>
                <div class="ko-results-grid">
                <?php foreach ($rounds as $roundKey => $roundLabel): ?>
                    <?php $limit = (int) ($roundLimits[$roundKey] ?? 0); $values = $currentResults[$roundKey] ?? []; ?>
                    <section class="ko-results-card">
                        <div class="section-title ko-round-title" style="margin:0 0 10px;"><?= htmlspecialchars($roundLabel, ENT_QUOTES, 'UTF-8') ?> <span class="small">(<?= count($values) ?>/<?= $limit ?>)</span></div>
                        <div class="ko-results-list">
                            <?php for ($i = 0; $i < $limit; $i++): ?>
                                <select name="results[<?= htmlspecialchars($roundKey, ENT_QUOTES, 'UTF-8') ?>][]">
                                    <option value="">Kies land</option>
                                    <?php foreach ($teams as $teamName): ?>
                                        <option value="<?= htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8') ?>" <?= (($values[$i] ?? '') === $teamName) ? 'selected' : '' ?>><?= htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endfor; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
            <div>
                <button type="submit" name="save_ko_results" value="1" class="primary" style="width:auto;">KO-resultaten opslaan</button>
            </div>
        </form>
    </section>
</main>
<style>
.ko-results-grid {
    display:grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap:14px;
}
.ko-results-card {
    padding:12px;
    border:1px solid rgba(255,255,255,0.08);
    border-radius:16px;
    background:rgba(255,255,255,0.03);
}
.ko-results-list {
    display:grid;
    gap:8px;
}
.ko-round-title {
    border-left-color:#d9ea00;
    background: linear-gradient(90deg, rgba(245,255,26,0.34), rgba(233,255,58,0.10));
}
@media (max-width: 980px) {
    .ko-results-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 720px) {
    .ko-results-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<?= wkPageShellEnd() ?>
