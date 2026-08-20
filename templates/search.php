<h1 class="page-h">Cerca</h1>
<form method="get" action="<?= h(url('cerca')) ?>" class="stack narrow">
  <label>Parole nel titolo o nel testo
    <input type="text" name="q" value="<?= h($q) ?>" minlength="2">
  </label>
  <button type="submit" class="primary">Cerca</button>
</form>
<?php if ($q !== '' && mb_strlen($q) < 2): ?>
  <p>Scrivi almeno due caratteri.</p>
<?php elseif ($q !== '' && empty($stories)): ?>
  <p>Nessun risultato per «<?= h($q) ?>».</p>
<?php elseif ($stories): ?>
  <p class="muted"><?= (int)$pg['total'] ?> risultati per «<?= h($q) ?>»</p>
  <?php foreach ($stories as $story): ?>
    <?php $list_view = true; include PHLASH_ROOT . '/templates/partials/story.php'; ?>
  <?php endforeach; ?>
  <?php include PHLASH_ROOT . '/templates/partials/pager.php'; ?>
<?php endif; ?>
