<h1 class="page-h">Commenti</h1>
<table class="grid">
  <tr><th>Quando</th><th>Chi</th><th>Storia</th><th>Testo</th><th>Stato</th><th></th></tr>
  <?php foreach ($rows as $c):
    $who = $c['username'] ?: ($c['author_name'] !== '' ? $c['author_name'] : 'Codardo Anonimo');
  ?>
    <tr>
      <td class="nowrap"><?= h($c['created_at']) ?></td>
      <td><?= h($who) ?></td>
      <td><a href="<?= h(url('storia/' . $c['slug'])) ?>#c<?= (int)$c['id'] ?>"><?= h($c['title']) ?></a></td>
      <td><?= h(excerpt($c['body'], 90)) ?></td>
      <td><?= h($c['status']) ?></td>
      <td>
        <form method="post" action="<?= h(url('admin/commenti')) ?>" class="inline">
          <?= \Phlash\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <?php if ($c['status']==='visible'): ?>
            <button name="act" value="hide">Nascondi</button>
          <?php else: ?>
            <button name="act" value="show">Mostra</button>
          <?php endif; ?>
          <button name="act" value="delete" onclick="return confirm('Eliminare?')">Elimina</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
