<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require __DIR__ . '/Importer.php';

use Phlash\Auth;
use Phlash\Csrf;

Auth::requireAdmin();

$result = null;
$error = '';
$action = '';
$imp = new BloggerImporter(PHLASH_ROOT, ['blog' => 'ilGlobale.it']);
$blogs = $imp->listBlogs();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::check();
    $action = (string) ($_POST['action'] ?? 'import');
    try {
        $imp = new BloggerImporter(PHLASH_ROOT, [
            'blog' => (string) ($_POST['blog'] ?? 'ilGlobale.it'),
            'user' => (string) ($_POST['user'] ?? ''),
            'dry_run' => !empty($_POST['dry_run']),
            'limit' => (int) ($_POST['limit'] ?? 0),
            'include_drafts' => !empty($_POST['include_drafts']),
            'include_spam' => !empty($_POST['include_spam']),
            'site_url' => trim((string) ($_POST['site_url'] ?? 'https://www.ilglobale.it')),
        ]);
        $result = $action === 'repair' ? $imp->repair() : $imp->run();
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$site = \Phlash\Database::setting('site_name', 'Phlash');
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Import Blogger — <?= h($site) ?></title>
<link rel="stylesheet" href="<?= h(asset('css/phlash.css')) ?>">
</head>
<body>
<div id="page" style="max-width:720px">
  <p><a href="<?= h(url('admin')) ?>">← Admin</a></p>
  <h1 class="page-h">Importatore Blogger (Takeout)</h1>
  <p class="page-intro">
    Legge <code>tools/blogger-importer/Takeout</code>, converte i post HTML in Markdown Phlash,
    copia i media in <code>uploads/import/</code> (anche le immagini Google) e crea tag dalle etichette.
    I post importati diventano contenuti del sito: <strong>senza fonte</strong>.
    Quelli già presenti (stesso slug) vengono saltati.
  </p>
  <?php if ($error): ?><div class="flash flash-err"><?= h($error) ?></div><?php endif; ?>
  <?php if ($result && $action === 'repair'): ?>
    <div class="flash flash-ok">
      Fonti rimosse <?= (int)$result['stats']['sources_cleared'] ?>,
      post riscritti <?= (int)$result['stats']['posts_rewritten'] ?>,
      media localizzati <?= (int)$result['stats']['media_copied'] ?>
      <?= !empty($result['stats']['dry_run']) ? ' (simulazione)' : '' ?>.
    </div>
  <?php elseif ($result): ?>
    <div class="flash flash-ok">
      Visti <?= (int)$result['stats']['posts_seen'] ?>,
      importati <?= (int)$result['stats']['posts_imported'] ?>,
      già presenti <?= (int)$result['stats']['posts_skipped'] ?>,
      commenti <?= (int)$result['stats']['comments_imported'] ?>,
      media <?= (int)$result['stats']['media_copied'] ?>
      <?= !empty($result['stats']['dry_run']) ? ' (simulazione)' : '' ?>.
    </div>
  <?php endif; ?>

  <form method="post" class="stack">
    <?= Csrf::field() ?>
    <label>Blog
      <select name="blog">
        <?php foreach ($blogs as $b): ?>
          <option value="<?= h($b['name']) ?>" <?= $b['name']==='ilGlobale.it'?'selected':'' ?>>
            <?= h($b['name']) ?> — <?= (int)$b['posts'] ?> post
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Dominio originale (solo riparazione: toglie le fonti già importate)
      <input type="url" name="site_url" value="https://www.ilglobale.it">
    </label>
    <label>Utente Phlash (vuoto = primo admin)
      <input type="text" name="user" placeholder="admin">
    </label>
    <label>Limite post (0 = tutti)
      <input type="number" name="limit" value="0" min="0">
    </label>
    <label class="chk"><input type="checkbox" name="dry_run" value="1" checked> Solo simulazione</label>
    <label class="chk"><input type="checkbox" name="include_drafts" value="1"> Includi bozze</label>
    <label class="chk"><input type="checkbox" name="include_spam" value="1"> Includi commenti spam</label>
    <p>
      <button type="submit" class="primary" name="action" value="import">Avvia import</button>
      <button type="submit" name="action" value="repair">Ripara import esistenti</button>
    </p>
  </form>

  <?php if ($result && $result['log']): ?>
    <h2 class="page-h2">Dettaglio</h2>
    <ul class="plain">
      <?php foreach (array_slice($result['log'], 0, 200) as $row): ?>
        <li><?= $row['type']==='skip' ? 'già presente' : 'nuovo' ?> — <?= h($row['msg']) ?></li>
      <?php endforeach; ?>
      <?php if (count($result['log']) > 200): ?>
        <li>… altri <?= count($result['log']) - 200 ?></li>
      <?php endif; ?>
    </ul>
  <?php endif; ?>
</div>
</body>
</html>
