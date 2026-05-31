<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkEnsureKoSchema($pdo);

$participantId = isset($_GET['participant_id']) ? (int) $_GET['participant_id'] : 0;
$message = null;
$messageClass = 'flash';

$participants = $pdo->query('SELECT id, name FROM participants ORDER BY name ASC')->fetchAll();
$rounds = wkKoRounds();
$roundLimits = wkKoRoundLimits();
$reviewStatuses = wkReviewStatuses();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participantId = (int) ($_POST['participant_id'] ?? 0);
    if ($participantId > 0) {
        $preparedSelections = [];
        foreach ($rounds as $roundKey => $roundLabel) {
            $values = $_POST['ko'][$roundKey] ?? [];
            $entries = [];
            foreach ((array) $values as $entry) {
                $teamName = trim((string) ($entry['team_name'] ?? ''));
                $reviewStatus = (string) ($entry['review_status'] ?? 'OK');
                if ($teamName === '') {
                    continue;
                }
                if (!isset($reviewStatuses[$reviewStatus])) {
                    $reviewStatus = 'OK';
                }
                $entries[] = [
                    'team_name' => $teamName,
                    'review_status' => $reviewStatus,
                ];
            }
            $limit = $roundLimits[$roundKey] ?? 0;
            if (count($entries) !== $limit) {
                $message = $roundLabel . ' moet precies ' . $limit . ' landen bevatten.';
                $messageClass = 'flash warn';
                break;
            }
            $preparedSelections[$roundKey] = $entries;
        }

        if ($message === null) {
            foreach ($rounds as $roundKey => $roundLabel) {
                $delete = $pdo->prepare('DELETE FROM ko_predictions WHERE participant_id = :participant_id AND round_key = :round_key');
                $delete->execute([':participant_id' => $participantId, ':round_key' => $roundKey]);
                $insert = $pdo->prepare('INSERT INTO ko_predictions (participant_id, round_key, team_name, review_status) VALUES (:participant_id, :round_key, :team_name, :review_status)');
                foreach ($preparedSelections[$roundKey] as $entry) {
                    $insert->execute([
                        ':participant_id' => $participantId,
                        ':round_key' => $roundKey,
                        ':team_name' => $entry['team_name'],
                        ':review_status' => $entry['review_status'],
                    ]);
                }
            }
            header('Location: ko-predictions.php?participant_id=' . $participantId . '&saved=1');
            exit;
        }
    }
}

if (isset($_GET['saved'])) {
    $message = 'KO-voorspellingen opgeslagen.';
}

$selectedParticipant = null;
$currentSelections = [];
if ($participantId > 0) {
    $stmt = $pdo->prepare('SELECT id, name FROM participants WHERE id = :id');
    $stmt->execute([':id' => $participantId]);
    $selectedParticipant = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT round_key, team_name, review_status FROM ko_predictions WHERE participant_id = :participant_id ORDER BY id ASC');
    $stmt->execute([':participant_id' => $participantId]);
    foreach ($stmt->fetchAll() as $row) {
        $currentSelections[$row['round_key']][] = $row;
    }
}
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · KO-voorspellingen', 'ko') ?>
<main class="container stack">
    <section class="panel">
        <h1>KO-voorspellingen</h1>
        <p class="small">Per ronde invullen, meer zoals het printformulier. Elke voorspelling krijgt ook een reviewstatus: 0%, 50%, 99% of OK.</p>
        <?php if ($message !== null): ?>
            <div class="<?= htmlspecialchars($messageClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="get" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
            <div style="min-width:280px;">
                <label for="participant_id">Deelnemer</label>
                <select id="participant_id" name="participant_id">
                    <option value="0">Kies een deelnemer</option>
                    <?php foreach ($participants as $participant): ?>
                        <option value="<?= (int) $participant['id'] ?>" <?= $participantId === (int) $participant['id'] ? 'selected' : '' ?>><?= htmlspecialchars($participant['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="primary" style="width:auto;">Openen</button>
        </form>
    </section>

    <?php if ($selectedParticipant): ?>
        <section class="panel stack">
            <h2><?= htmlspecialchars($selectedParticipant['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <form method="post" class="stack">
                <input type="hidden" name="participant_id" value="<?= (int) $selectedParticipant['id'] ?>">
                <div class="ko-review-grid">
                    <?php foreach ($rounds as $roundKey => $roundLabel): ?>
                        <?php $entries = $currentSelections[$roundKey] ?? []; $limit = (int) ($roundLimits[$roundKey] ?? 0); ?>
                        <section class="ko-review-card">
                            <div class="section-title ko-round-title" style="margin:0 0 10px;"><?= htmlspecialchars($roundLabel, ENT_QUOTES, 'UTF-8') ?> <span class="small">(<?= count($entries) ?>/<?= $limit ?>)</span></div>
                            <div class="ko-review-list">
                                <?php for ($i = 0; $i < $limit; $i++): ?>
                                    <?php $entry = $entries[$i] ?? ['team_name' => '', 'review_status' => 'OK']; ?>
                                    <div class="ko-review-row">
                                        <input name="ko[<?= htmlspecialchars($roundKey, ENT_QUOTES, 'UTF-8') ?>][<?= $i ?>][team_name]" value="<?= htmlspecialchars((string) ($entry['team_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Landnaam">
                                        <select name="ko[<?= htmlspecialchars($roundKey, ENT_QUOTES, 'UTF-8') ?>][<?= $i ?>][review_status]">
                                            <?php foreach ($reviewStatuses as $statusValue => $statusLabel): ?>
                                                <option value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($entry['review_status'] ?? 'OK')) === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
                <div>
                    <button type="submit" class="primary" style="width:auto;">KO-voorspellingen opslaan</button>
                </div>
            </form>
        </section>
    <?php endif; ?>
</main>
<style>
.ko-review-grid {
    display:grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap:14px;
}
.ko-review-card {
    padding:12px;
    border:1px solid rgba(255,255,255,0.08);
    border-radius:16px;
    background:rgba(255,255,255,0.03);
}
.ko-review-list {
    display:grid;
    gap:8px;
}
.ko-review-row {
    display:grid;
    grid-template-columns: minmax(0, 1fr) 88px;
    gap:8px;
}
.ko-round-title {
    border-left-color:#d9ea00;
    background: linear-gradient(90deg, rgba(245,255,26,0.34), rgba(233,255,58,0.10));
}
@media (max-width: 980px) {
    .ko-review-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 720px) {
    .ko-review-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<?= wkPageShellEnd() ?>
