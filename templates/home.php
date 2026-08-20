<?php if (($mode ?? '') !== 'home'): ?>
  <?php if (!empty($intro)): ?>
    <p class="page-intro"><?= h($intro) ?></p>
  <?php endif; ?>
  <h1 class="page-h"><?= h($heading ?? 'Storie') ?></h1>
<?php endif; ?>
<?php if (empty($stories)): ?>
  <p class="empty">Nessuna storia qui, per ora. <?php if (!empty($user)): ?><a href="<?= h(url('invia')) ?>">Inviarne una</a><?php else: ?><a href="<?= h(url('registrati')) ?>">Registrati</a> per proporre contenuti.<?php endif; ?></p>
<?php else: ?>
  <?php foreach ($stories as $story): ?>
    <?php $list_view = true; include PHLASH_ROOT . '/templates/partials/story.php'; ?>
  <?php endforeach; ?>
  <?php include PHLASH_ROOT . '/templates/partials/pager.php'; ?>
<?php endif; ?>
