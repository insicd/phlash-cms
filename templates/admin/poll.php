<h1 class="page-h">Sondaggio</h1>
<?php foreach ($polls as $p): ?>
  <div class="preview-box">
    <p><strong><?= h($p['question']) ?></strong> <?= $p['is_active'] ? '· attivo' : '' ?></p>
    <ul>
      <?php foreach ($p['options'] as $o): ?>
        <li><?= h($o['label']) ?> — <?= (int)$o['votes'] ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endforeach; ?>

<h2 class="page-h2">Nuovo sondaggio (sostituisce quello attivo)</h2>
<form method="post" action="<?= h(url('admin/sondaggio')) ?>" class="stack narrow">
  <?= \Phlash\Csrf::field() ?>
  <label>Domanda <input type="text" name="question" required maxlength="255"></label>
  <label>Opzione 1 <input type="text" name="options[]" required></label>
  <label>Opzione 2 <input type="text" name="options[]" required></label>
  <label>Opzione 3 <input type="text" name="options[]"></label>
  <label>Opzione 4 <input type="text" name="options[]"></label>
  <button type="submit" class="primary">Attiva</button>
</form>
