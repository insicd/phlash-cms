<?php
$icon = phlash_fa_sanitize((string) ($icon ?? ''));
?>
<div class="icon-field">
  <span class="icon-preview" aria-hidden="true"><?php if ($icon !== ''): ?><i class="fa-solid fa-<?= h($icon) ?>"></i><?php endif; ?></span>
  <input type="text" name="icon" value="<?= h($icon) ?>" maxlength="64" placeholder="newspaper" class="icon-name" autocomplete="off">
  <button type="button" class="icon-pick-btn">Scegli</button>
</div>
