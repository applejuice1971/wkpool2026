<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$pdo = wkGetPdo();
wkEnsureImportSchema($pdo);

$importId = isset($_GET['import_id']) ? (int) $_GET['import_id'] : 0;
if ($importId <= 0) {
    http_response_code(400);
    exit('Ongeldig import_id');
}

$importStmt = $pdo->prepare('SELECT * FROM prediction_imports WHERE id = ? LIMIT 1');
$importStmt->execute([$importId]);
$import = $importStmt->fetch();
if (!$import) {
    http_response_code(404);
    exit('Import niet gevonden');
}

if (empty($import['participant_id'])) {
    http_response_code(400);
    exit('Deze import heeft nog geen gekoppelde deelnemer.');
}

$rowsStmt = $pdo->prepare("SELECT * FROM prediction_import_rows WHERE import_id = ? AND match_id IS NOT NULL AND predicted_home_score IS NOT NULL AND predicted_away_score IS NOT NULL");
$rowsStmt->execute([$importId]);
$rows = $rowsStmt->fetchAll();

$pdo->beginTransaction();
try {
    $upsert = $pdo->prepare(
        'INSERT INTO predictions (participant_id, match_id, predicted_home_score, predicted_away_score, points) '
        . 'VALUES (?, ?, ?, ?, 0) '
        . 'ON DUPLICATE KEY UPDATE predicted_home_score = VALUES(predicted_home_score), predicted_away_score = VALUES(predicted_away_score), updated_at = CURRENT_TIMESTAMP'
    );

    $rowUpdate = $pdo->prepare("UPDATE prediction_import_rows SET status = 'imported' WHERE id = ?");

    foreach ($rows as $row) {
        $upsert->execute([
            (int) $import['participant_id'],
            (int) $row['match_id'],
            (int) $row['predicted_home_score'],
            (int) $row['predicted_away_score'],
        ]);
        $rowUpdate->execute([(int) $row['id']]);
    }

    $importUpdate = $pdo->prepare("UPDATE prediction_imports SET status = 'imported', imported_at = NOW() WHERE id = ?");
    $importUpdate->execute([$importId]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    exit('Import mislukt: ' . $e->getMessage());
}

header('Location: imports-overview.php');
exit;
