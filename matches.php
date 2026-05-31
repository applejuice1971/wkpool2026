<?php
require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
$message = null;
$messageClass = 'flash';
$countryOptions = $pdo->query("SELECT id, name_de, is_placeholder FROM countries ORDER BY is_placeholder ASC, name_de ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $stage = trim($_POST['stage'] ?? 'Groepsfase');
        $matchDate = trim($_POST['match_date'] ?? '');
        $homeCountryId = (int) ($_POST['home_country_id'] ?? 0);
        $awayCountryId = (int) ($_POST['away_country_id'] ?? 0);

        if ($matchDate === '' || $homeCountryId <= 0 || $awayCountryId <= 0) {
            $message = 'Datum/tijd, thuisland en uitland zijn verplicht.';
            $messageClass = 'flash warn';
        } else {
            $stmt = $pdo->prepare('INSERT INTO matches (stage, match_date, home_country_id, away_country_id, status) SELECT :stage, :match_date, ch.id, ca.id, :status FROM countries ch CROSS JOIN countries ca WHERE ch.id = :home_country_id AND ca.id = :away_country_id');
            $stmt->execute([
                ':stage' => $stage,
                ':match_date' => date('Y-m-d H:i:s', strtotime($matchDate)),
                ':home_country_id' => $homeCountryId,
                ':away_country_id' => $awayCountryId,
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

$matches = $pdo->query("SELECT m.id, m.stage, m.match_date, m.status, ch.name_de AS home_country_name, ca.name_de AS away_country_name FROM matches m INNER JOIN countries ch ON ch.id = m.home_country_id INNER JOIN countries ca ON ca.id = m.away_country_id ORDER BY m.match_date ASC")->fetchAll();
$groupMatchNumbers = wkGroupMatchNumberMap($pdo);
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
                        <label for="home_country_id">Thuisland</label>
                        <select id="home_country_id" name="home_country_id" required>
                            <option value="">Kies thuisland</option>
                            <?php foreach ($countryOptions as $country): ?>
                                <option value="<?= (int) $country['id'] ?>"><?= htmlspecialchars((string) $country['name_de'], ENT_QUOTES, 'UTF-8') ?><?= !empty($country['is_placeholder']) ? ' (placeholder)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="away_country_id">Uitland</label>
                        <select id="away_country_id" name="away_country_id" required>
                            <option value="">Kies uitland</option>
                            <?php foreach ($countryOptions as $country): ?>
                                <option value="<?= (int) $country['id'] ?>"><?= htmlspecialchars((string) $country['name_de'], ENT_QUOTES, 'UTF-8') ?><?= !empty($country['is_placeholder']) ? ' (placeholder)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
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
                            <th>#</th>
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
                                <td data-label="#"><?php if (str_starts_with((string) $match['stage'], 'Group ')): ?>#<?= $groupMatchNumbers[(int) $match['id']] ?? (int) $match['id'] ?><?php else: ?>#<?= (int) $match['id'] ?><?php endif; ?></td>
                                <td data-label="Fase"><?= htmlspecialchars($match['stage'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Wedstrijd"><?= htmlspecialchars(wkMatchLabel($match), ENT_QUOTES, 'UTF-8') ?></td>
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

