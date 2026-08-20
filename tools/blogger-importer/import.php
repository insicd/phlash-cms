<?php
/**
 * CLI: php tools/blogger-importer/import.php --blog="ilGlobale.it"
 *      php tools/blogger-importer/import.php --list
 *      php tools/blogger-importer/import.php --repair
 *      php tools/blogger-importer/import.php --dry-run --limit=5
 */
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Usare da terminale, oppure aprire index.php da admin.\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require $root . '/config.php';
require $root . '/app/helpers.php';
spl_autoload_register(function ($class) use ($root) {
    if (strpos($class, 'Phlash\\') !== 0) {
        return;
    }
    $rel = str_replace('\\', '/', substr($class, strlen('Phlash\\')));
    $file = $root . '/app/' . $rel . '.php';
    if (is_file($file)) {
        require $file;
    }
});
require __DIR__ . '/Importer.php';

$opts = [
    'blog' => 'ilGlobale.it',
    'user' => '',
    'dry_run' => false,
    'limit' => 0,
    'include_drafts' => false,
    'include_spam' => false,
    'site_url' => 'https://www.ilglobale.it',
    'list' => false,
    'repair' => false,
];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--list') {
        $opts['list'] = true;
    } elseif ($arg === '--repair') {
        $opts['repair'] = true;
    } elseif ($arg === '--dry-run') {
        $opts['dry_run'] = true;
    } elseif ($arg === '--include-drafts') {
        $opts['include_drafts'] = true;
    } elseif ($arg === '--include-spam') {
        $opts['include_spam'] = true;
    } elseif (preg_match('/^--blog=(.*)$/', $arg, $m)) {
        $opts['blog'] = $m[1];
    } elseif (preg_match('/^--user=(.*)$/', $arg, $m)) {
        $opts['user'] = $m[1];
    } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $opts['limit'] = (int) $m[1];
    } elseif (preg_match('/^--site-url=(.*)$/', $arg, $m)) {
        $opts['site_url'] = $m[1];
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Uso:\n";
        echo "  php import.php --list\n";
        echo "  php import.php --blog=\"ilGlobale.it\" [--dry-run] [--limit=N] [--user=admin]\n";
        echo "  php import.php --repair [--dry-run] [--blog=\"ilGlobale.it\"]\n";
        echo "  --site-url=https://www.ilglobale.it  (solo per --repair: toglie le fonti)\n";
        echo "  --include-drafts  --include-spam\n";
        exit(0);
    }
}

$imp = new BloggerImporter($root, $opts);
if ($opts['list']) {
    foreach ($imp->listBlogs() as $b) {
        echo sprintf("%s — %d post (%d bozze), %d commenti nel feed\n", $b['name'], $b['posts'], $b['drafts'], $b['comments']);
    }
    exit(0);
}

if ($opts['repair']) {
    echo ($opts['dry_run'] ? "[dry-run] " : '') . "Riparazione import " . $opts['blog'] . " …\n";
    $result = $imp->repair();
    $s = $result['stats'];
    echo sprintf(
        "Fonti rimosse: %d, post riscritti: %d, media localizzati: %d\n",
        $s['sources_cleared'],
        $s['posts_rewritten'],
        $s['media_copied']
    );
    exit(0);
}

echo ($opts['dry_run'] ? "[dry-run] " : '') . "Import " . $opts['blog'] . " …\n";
$result = $imp->run();
$s = $result['stats'];
echo sprintf(
    "Visti %d, importati %d, già presenti %d, commenti %d, media %d\n",
    $s['posts_seen'],
    $s['posts_imported'],
    $s['posts_skipped'],
    $s['comments_imported'],
    $s['media_copied']
);
foreach ($result['log'] as $row) {
    echo ($row['type'] === 'skip' ? '  - ' : '  + ') . $row['msg'] . "\n";
}
