<?php require_once PHLASH_APP . '/fa-icons.php'; ?>
<div id="icon-picker" hidden>
  <div class="icon-picker-head">
    <strong>Icone Font Awesome (locale)</strong>
    <button type="button" class="icon-picker-close" aria-label="Chiudi">×</button>
  </div>
  <input type="search" id="icon-filter" placeholder="Filtra per nome, es. flask, gamepad…" autocomplete="off">
  <div class="icon-grid">
    <?php foreach (phlash_fa_icons() as $ic): ?>
      <button type="button" class="icon-choice" data-icon="<?= h($ic) ?>" title="<?= h($ic) ?>">
        <i class="fa-solid fa-<?= h($ic) ?>"></i>
      </button>
    <?php endforeach; ?>
  </div>
</div>
