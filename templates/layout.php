<?php $lp = logo_parts($settings['site_name']); ?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php
$siteName = $settings['site_name'];
$tagline = trim((string) ($settings['site_tagline'] ?? ''));
if (($mode ?? '') === 'home') {
    echo h($tagline !== '' ? $siteName . ' — ' . $tagline : $siteName);
} else {
    $pageTitle = trim((string) ($title ?? ''));
    echo h(($pageTitle !== '' && $pageTitle !== $siteName) ? $pageTitle . ' — ' . $siteName : $siteName);
}
?></title>
<link rel="stylesheet" href="<?= h(asset('css/phlash.css')) ?>">
<link rel="stylesheet" href="<?= h(asset('vendor/fontawesome/css/fontawesome.min.css')) ?>">
<link rel="stylesheet" href="<?= h(asset('vendor/fontawesome/css/solid.min.css')) ?>">
<link rel="icon" href="<?= h(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<?php if (!empty($settings['custom_css'])): ?>
<style><?= $settings['custom_css'] ?></style>
<?php endif; ?>
</head>
<body>
<div id="sky">
  <div id="topbar">
    <a class="logo" href="<?= h(url()) ?>" title="<?= h($settings['site_name']) ?>">
      <span class="logo-slash">/</span><span class="logo-a"><?= h($lp['a']) ?></span><span class="logo-b"><?= h($lp['b']) ?></span>
    </a>
    <nav id="nav">
      <a href="<?= h(url()) ?>" class="<?= empty($mode) || $mode==='home' ? 'on' : '' ?>">Storie</a>
      <a href="<?= h(url('upcoming')) ?>" class="<?= ($mode ?? '')==='upcoming' ? 'on' : '' ?>">In arrivo<?php if (!empty($pending_count)): ?> <em><?= (int)$pending_count ?></em><?php endif; ?></a>
      <a href="<?= h(url('cerca')) ?>">Cerca</a>
      <a href="<?= h(url('rss')) ?>">RSS</a>
    </nav>
    <?php if (!empty($user)): ?>
      <a class="btn-submit" href="<?= h(url('invia')) ?>">Invia</a>
    <?php endif; ?>
    <form class="top-search" action="<?= h(url('cerca')) ?>" method="get">
      <input type="search" name="q" placeholder="Cerca" aria-label="Cerca" value="">
    </form>
    <div class="top-user">
      <?php if (!empty($user)): ?>
        <a href="<?= h(url('utente/' . $user['username'])) ?>"><?= h($user['username']) ?></a>
        <?php if ($user['role']==='admin'): ?><a href="<?= h(url('admin')) ?>">Admin</a><?php endif; ?>
        <a href="<?= h(url('logout')) ?>">Esci</a>
      <?php else: ?>
        <a href="<?= h(url('login')) ?>">Accedi</a>
        <a href="<?= h(url('registrati')) ?>">Registrati</a>
      <?php endif; ?>
    </div>
  </div>
  <?php if (trim((string) ($settings['site_tagline'] ?? '')) !== ''): ?>
    <p class="tagline"><?= h($settings['site_tagline']) ?></p>
  <?php endif; ?>
  <nav id="subnav">
    <?php foreach ($topics ?? [] as $tn): ?>
      <a href="<?= h(url('sezione/' . $tn['slug'])) ?>" class="<?= !empty($current_topic) && $current_topic['slug']===$tn['slug'] ? 'on' : '' ?>"><?= topic_icon_html($tn['icon'] ?? '') ?> <?= h($tn['name']) ?></a>
    <?php endforeach; ?>
  </nav>
</div>

<div id="page">
  <?php if (!empty($admin_nav)): ?>
    <div class="admin-strip">
      <a href="<?= h(url('admin')) ?>">Pannello</a>
      <a href="<?= h(url('admin/statistiche')) ?>">Statistiche</a>
      <a href="<?= h(url('admin/storie')) ?>">Storie</a>
      <a href="<?= h(url('admin/commenti')) ?>">Commenti</a>
      <a href="<?= h(url('admin/utenti')) ?>">Utenti</a>
      <a href="<?= h(url('admin/sezioni')) ?>">Sezioni</a>
      <a href="<?= h(url('admin/sondaggio')) ?>">Sondaggio</a>
      <a href="<?= h(url('admin/impostazioni')) ?>">Impostazioni</a>
    </div>
  <?php endif; ?>

  <?php if (!empty($flash)): ?>
    <div class="flash flash-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
  <?php endif; ?>

  <div id="columns">
    <div id="articles">
      <?= $content ?>
    </div>
    <aside id="slashboxes">
      <?= $sidebar ?>
    </aside>
  </div>
</div>

<footer id="foot">
  <p><strong><?= h($settings['site_name']) ?></strong> — Powered by <a href="https://github.com/insicd/phlash-cms" target="_blank"class="soft-ver"><?= h(PHLASH_NAME) ?> <?= h(PHLASH_VERSION) ?></a> a PHP+MySQL CMS, based on Slashdot and Pligg.</p>
</footer>
<script src="<?= h(asset('js/phlash.js')) ?>"></script>
<?php if (!empty($use_chartjs)): ?>
<script src="<?= h(asset('vendor/chartjs/chart.umd.min.js')) ?>"></script>
<script src="<?= h(asset('js/stats-charts.js')) ?>"></script>
<?php endif; ?>
</body>
</html>
