<?php if ($pg['pages'] <= 1) return; ?>
<nav class="pager">
  <?php for ($i = 1; $i <= $pg['pages']; $i++):
    $base = $pg['base'];
    if ($i === 1) {
      $href = url($base);
    } else {
      $join = (strpos($base, '?') !== false) ? '&' : '?';
      $href = url(($base === '' ? '' : $base) . $join . 'p=' . $i);
    }
  ?>
    <?php if ($i === (int)$pg['page']): ?>
      <strong><?= $i ?></strong>
    <?php else: ?>
      <a href="<?= h($href) ?>"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>
</nav>
