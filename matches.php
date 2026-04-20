<?php
require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
$message = null;
$messageClass = 'flash';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $stage = trim($_POST['stage'] ?? 'Groepsfase');
        $matchDate = trim($_POST['match_date'] ?? '');
        $homeTeam = trim($_POST['home_team'] ?? '');
        $awayTeam = trim($_POST['away_team'] ?? '');

        if ($matchDate === '' || $homeTeam === '' || $awayTeam === '') {
            $message = 'Datum/tijd, thuisteam en uitteam zijn verplicht.';
            $messageClass = 'flash warn';
        } else {
            $stmt = $pdo->prepare('INSERT INTO matches (stage, match_date, home_team, away_team, status) VALUES (:stage, :match_date, :home_team, :away_team, :status)');
            $stmt->execute([
                ':stage' => $stage,
                ':match_date' => date('Y-m-d H:i:s', strtotime($matchDate)),
                ':home_team' => $homeTeam,
                ':away_team' => $awayTeam,
                ':status' => 'scheduled',
            ]);
            header('Location: matches.php?added=1');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM matches WHERE id = :id');
            $stmt->execute([':id' => $id]);
            header('Location: matches.php?deleted=1');
            exit;
        }
    }
}

if (isset($_GET['added'])) {
    $message = 'Wedstrijd toegevoegd.';
}
if (isset($_GET['deleted'])) {
    $message = 'Wedstrijd verwijderd.';
}

$matches = $pdo->query('SELECT id, stage, match_date, home_team, away_team, status FROM matches ORDER BY match_date ASC')->fetchAll();
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · Wedstrijdenbeheer', 'matches') ?>
    <main class="container stack">
        <section class="panel">
            <h1>Wedstrijdenbeheer</h1>
            <p>Voeg hier handmatig wedstrijden toe. Dit is meteen een goede basis voor latere import.</p>
            <?php if ($message !== null): ?>
                <div class="<?= htmlspecialchars($messageClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="grid-2">
                    <div>
                        <label for="stage">Fase</label>
                        <input id="stage" name="stage" type="text" value="Groepsfase">
                    </div>
                    <div>
                        <label for="match_date">Datum en tijd</label>
                        <input id="match_date" name="match_date" type="datetime-local" required>
                    </div>
                    <div>
                        <label for="home_team">Thuisteam</label>
                        <input id="home_team" name="home_team" type="text" placeholder="Bijv. Nederland" required>
                    </div>
                    <div>
                        <label for="away_team">Uitteam</label>
                        <input id="away_team" name="away_team" type="text" placeholder="Bijv. Brazilië" required>
                    </div>
                </div>
                <div>
                    <button type="submit" class="primary">Wedstrijd toevoegen</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2>Geplande wedstrijden</h2>
            <?php if ($matches === []): ?>
                <p>Nog geen wedstrijden toegevoegd.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Fase</th>
                            <th>Wedstrijd</th>
                            <th>Datum</th>
                            <th>Status</th>
                            <th>Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td data-label="Fase"><?= htmlspecialchars($match['stage'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Wedstrijd"><?= htmlspecialchars($match['home_team'] . ' - ' . $match['away_team'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Datum"><?= htmlspecialchars($match['match_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Status"><?= htmlspecialchars($match['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Actie">
                                    <form method="post" onsubmit="return confirm('Deze wedstrijd verwijderen?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $match['id'] ?>">
                                        <button type="submit" class="danger">Verwijderen</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
        <nav class="mobile-tabbar">
            <a href="index.php">Home</a>
            <a href="participants.php">Deelnemers</a>
            <a href="matches.php" class="active">Wedstrijden</a>
        </nav>
    </main>
<?= wkPageShellEnd() ?>

