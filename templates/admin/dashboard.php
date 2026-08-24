<h1 class="page-h">Amministrazione</h1>
<ul class="stats">
  <li><strong><?= (int)$stats['stories_pub'] ?></strong> storie in homepage</li>
  <li><strong><?= (int)$stats['stories_pend'] ?></strong> in coda</li>
  <li><strong><?= (int)$stats['comments'] ?></strong> commenti</li>
  <li><strong><?= (int)$stats['users'] ?></strong> utenti</li>
</ul>
<p>
  <a href="<?= h(url('admin/statistiche')) ?>">Statistiche visite</a>
  ·
  <a href="<?= h(url('tools/blogger-importer/index.php')) ?>">Importa da Blogger (Takeout)</a>
</p>
<h2 class="page-h2">Coda in arrivo</h2>
<?php if (!$pending): ?>
  <p class="muted">Niente in attesa.</p>
<?php else: ?>
  <table class="grid">
    <tr><th>Titolo</th><th>Autore</th><th>Voti</th><th></th></tr>
    <?php foreach ($pending as $s): ?>
      <tr>
        <td><a href="<?= h(url('storia/' . $s['slug'])) ?>"><?= h($s['title']) ?></a></td>
        <td><?= h($s['username']) ?></td>
        <td><?= (int)$s['score'] ?></td>
        <td>
          <a href="<?= h(url('invia?id=' . (int)$s['id'])) ?>">modifica</a>
          <form method="post" action="<?= h(url('admin/storie')) ?>" class="inline">
            <?= \Phlash\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button name="act" value="publish">Pubblica</button>
            <button name="act" value="reject">Rifiuta</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
