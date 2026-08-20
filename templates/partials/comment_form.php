<?php
$allow_anon = $allow_anon ?? true;
$captcha = $captcha ?? ['a' => 2, 'b' => 2];
$parent_id = $parent_id ?? 0;
$compact = !empty($compact);
?>
<form class="comment-form<?= $compact ? ' compact' : '' ?>" method="post" action="<?= h(url('commento')) ?>">
  <?= \Phlash\Csrf::field() ?>
  <input type="hidden" name="story_id" value="<?= (int)$story['id'] ?>">
  <input type="hidden" name="parent_id" value="<?= (int)$parent_id ?>">
  <label>Commento</label>
  <div class="md-wrap">
    <?php $md_level = 'inline'; include PHLASH_ROOT . '/templates/partials/md_toolbar.php'; ?>
    <textarea class="md-input" name="body" rows="<?= $compact ? 4 : 7 ?>" required minlength="2" maxlength="8000" placeholder="Testo del commento. Anche da anonimo"></textarea>
  </div>
  <?php if (empty($user) || $allow_anon): ?>
    <div class="form-row">
      <?php if ($allow_anon): ?>
        <label class="chk">
          <input type="checkbox" name="anonymous" value="1" <?= empty($user) ? 'checked' : '' ?>>
          Pubblica come Codardo Anonimo
        </label>
        <label class="grow">Nome (facoltativo, se anonimo)
          <input type="text" name="author_name" maxlength="40" placeholder="vuoto = Codardo Anonimo">
        </label>
      <?php endif; ?>
    </div>
    <?php if (empty($user)): ?>
      <label>Anti-spam: <?= (int)$captcha['a'] ?> + <?= (int)$captcha['b'] ?> =
        <input type="text" name="captcha" inputmode="numeric" required class="captcha">
      </label>
    <?php endif; ?>
  <?php endif; ?>
  <button type="submit">Pubblica commento</button>
</form>
