<h1 class="page-h">Sezioni</h1>
<p class="page-intro">Ogni sezione può avere un’icona Font Awesome ospitata in locale (niente CDN). Il nome, es. <code>newspaper</code>, è quello del pacchetto Free 6.</p>
<table class="grid">
  <tr><th>Sezione</th><th>Icona</th><th>Slug</th><th></th></tr>
  <?php foreach ($rows as $t): ?>
    <tr>
      <td>
        <form method="post" action="<?= h(url('admin/sezioni')) ?>" class="inline-fields topic-edit">
          <?= \Phlash\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <input type="text" name="name" value="<?= h($t['name']) ?>" required>
          <input type="text" name="description" value="<?= h($t['description']) ?>" placeholder="Descrizione">
          <input type="number" name="sort_order" value="<?= (int)$t['sort_order'] ?>" class="num" title="Ordine">
          <?php $icon = $t['icon'] ?? ''; include PHLASH_ROOT . '/templates/partials/icon_field.php'; ?>
          <button type="submit">Salva</button>
        </form>
      </td>
      <td class="topic-icon-cell"><?= topic_icon_html($t['icon'] ?? '', $t['name']) ?></td>
      <td><?= h($t['slug']) ?></td>
      <td>
        <form method="post" action="<?= h(url('admin/sezioni/elimina')) ?>" class="inline">
          <?= \Phlash\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <button onclick="return confirm('Eliminare la sezione?')">Elimina</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<h2 class="page-h2">Nuova sezione</h2>
<form method="post" action="<?= h(url('admin/sezioni')) ?>" class="stack narrow">
  <?= \Phlash\Csrf::field() ?>
  <label>Nome <input type="text" name="name" required></label>
  <label>Descrizione <input type="text" name="description"></label>
  <label>Ordine <input type="number" name="sort_order" value="10"></label>
  <label>Icona
    <?php $icon = ''; include PHLASH_ROOT . '/templates/partials/icon_field.php'; ?>
  </label>
  <button type="submit" class="primary">Crea</button>
</form>
<?php include PHLASH_ROOT . '/templates/partials/icon_picker.php'; ?>
