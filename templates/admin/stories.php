<h1 class="page-h">Storie</h1>
<p>
  <a href="<?= h(url('admin/storie?status=pending')) ?>">In coda</a> ·
  <a href="<?= h(url('admin/storie?status=published')) ?>">Pubblicate</a> ·
  <a href="<?= h(url('admin/storie?status=rejected')) ?>">Rifiutate</a>
</p>
<table class="grid">
  <tr><th>Titolo</th><th>Autore</th><th>Sezione</th><th>Voti</th><th></th></tr>
  <?php foreach ($rows as $s): ?>
    <tr>
      <td><a href="<?= h(url('storia/' . $s['slug'])) ?>"><?= h($s['title']) ?></a></td>
      <td><?= h($s['username']) ?></td>
      <td><?= h($s['topic_name']) ?></td>
      <td><?= (int)$s['score'] ?></td>
      <td>
        <a href="<?= h(url('invia?id=' . (int)$s['id'])) ?>">modifica</a>
        <form method="post" action="<?= h(url('admin/storie')) ?>" class="inline">
          <?= \Phlash\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <?php if ($status !== 'published'): ?><button name="act" value="publish">Pubblica</button><?php endif; ?>
          <?php if ($status !== 'pending'): ?><button name="act" value="pending">In coda</button><?php endif; ?>
          <?php if ($status !== 'rejected'): ?><button name="act" value="reject">Rifiuta</button><?php endif; ?>
          <button name="act" value="delete" onclick="return confirm('Eliminare?')">Elimina</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
