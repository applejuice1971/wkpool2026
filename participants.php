<?php
require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
$message = null;
$messageClass = 'flash';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '') {
            $message = 'Naam is verplicht.';
            $messageClass = 'flash warn';
        } else {
            $stmt = $pdo->prepare('INSERT INTO participants (name, email) VALUES (:name, :email)');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email !== '' ? $email : null,
            ]);
            header('Location: participants.php?added=1');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM participants WHERE id = :id');
            $stmt->execute([':id' => $id]);
            header('Location: participants.php?deleted=1');
            exit;
        }
    }
}

if (isset($_GET['added'])) {
    $message = 'Deelnemer toegevoegd.';
}
if (isset($_GET['deleted'])) {
    $message = 'Deelnemer verwijderd.';
}

$participants = $pdo->query('SELECT id, name, email, created_at FROM participants ORDER BY created_at DESC')->fetchAll();
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · Deelnemersbeheer', 'participants') ?>
    <main class="container stack">
        <section class="panel">
            <h1>Deelnemersbeheer</h1>
            <p>Voeg hier deelnemers toe voor de WK-pool.</p>
            <?php if ($message !== null): ?>
                <div class="<?= htmlspecialchars($messageClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="grid-2">
                    <div>
                        <label for="name">Naam</label>
                        <input id="name" name="name" type="text" placeholder="Bijv. Maurits" required>
                    </div>
                    <div>
                        <label for="email">E-mail (optioneel)</label>
                        <input id="email" name="email" type="email" placeholder="naam@voorbeeld.nl">
                    </div>
                </div>
                <div>
                    <button type="submit" class="primary">Deelnemer toevoegen</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2>Huidige deelnemers</h2>
            <?php if ($participants === []): ?>
                <p>Nog geen deelnemers toegevoegd.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Naam</th>
                            <th>E-mail</th>
                            <th>Aangemaakt</th>
                            <th>Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $participant): ?>
                            <tr>
                                <td data-label="Naam"><?= htmlspecialchars($participant['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="E-mail"><?= htmlspecialchars((string) ($participant['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Aangemaakt" class="small"><?= htmlspecialchars($participant['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Actie">
                                    <form method="post" onsubmit="return confirm('Deze deelnemer verwijderen?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $participant['id'] ?>">
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
            <a href="participants.php" class="active">Deelnemers</a>
            <a href="matches.php">Wedstrijden</a>
        </nav>
    </main>
<?= wkPageShellEnd() ?>

