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

$roundLabels = wkKoImportRoundLabels();
$prefillTemplate = implode("\n\n", array_map(
    static fn(string $key, string $label): string => $label . ":\n",
    array_keys($roundLabels),
    array_values($roundLabels)
));

$sourcePath = trim((string) ($import['source_path'] ?? ''));
$absolutePath = $sourcePath !== '' && str_starts_with($sourcePath, '/') ? $sourcePath : __DIR__ . '/' . ltrim($sourcePath, '/');
?>
<?php header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); ?>
<?= wkPageShellStart('WK Pool 2026 · KO OCR helper', 'imports') ?>
<div class="container stack">
    <section class="panel stack">
        <div class="toolbar">
            <div>
                <h1>KO OCR helper</h1>
                <p class="small">Tussenstap voor foto naar KO-review. Gebruik dit als werkblad om vision/OCR-uitvoer snel in het juiste rondeformaat te krijgen.</p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="secondary" href="import-detail.php?id=<?= (int) $importId ?>">Import detail</a>
                <a class="secondary" href="ko-import-review.php?import_id=<?= (int) $importId ?>">KO review</a>
            </div>
        </div>

        <div class="muted-box stack">
            <div><strong>Bestand</strong><br><span class="small"><?= htmlspecialchars((string) ($import['source_filename'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div><strong>Pad</strong><br><span class="small"><?= htmlspecialchars($absolutePath, ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php if (is_file($absolutePath)): ?>
                <div><a class="secondary" href="<?= htmlspecialchars((string) $import['source_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open bronbestand</a></div>
            <?php endif; ?>
        </div>

        <section class="muted-box stack">
            <div>
                <h2>Ronde-template</h2>
                <p class="small">Kopieer deze opzet naar een OCR/vision prompt of plak hier handmatig de uitgelezen tekst in hetzelfde formaat.</p>
            </div>
            <textarea rows="18" style="width:100%; border-radius:12px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.04); color:var(--text); padding:12px;"><?= htmlspecialchars($prefillTemplate, ENT_QUOTES, 'UTF-8') ?></textarea>
        </section>

        <section class="muted-box stack">
            <div>
                <h2>Gebruik</h2>
                <ol>
                    <li>Lees de foto uit met vision/OCR.</li>
                    <li>Zet de uitkomst in het formaat van de template hierboven.</li>
                    <li>Plak die tekst in <strong>KO review / OCR prefill</strong>.</li>
                    <li>Klik op <strong>OCR tekst over velden verdelen</strong>.</li>
                    <li>Controleer en sla op naar KO-voorspellingen.</li>
                </ol>
            </div>
        </section>
    </section>
</div>
<?= wkPageShellEnd() ?>
