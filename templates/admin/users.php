<h1 class="page-h">Utenti</h1>
<table class="grid">
  <tr><th>User</th><th>Email</th><th>Ruolo</th><th>Karma</th><th>Stato</th><th>API</th><th></th></tr>
  <?php foreach ($rows as $u): ?>
    <tr>
      <td><a href="<?= h(url('utente/' . $u['username'])) ?>"><?= h($u['username']) ?></a></td>
      <td><?= h($u['email']) ?></td>
      <td><?= h($u['role']) ?></td>
      <td><?= (int)$u['karma'] ?></td>
      <td><?= h($u['status']) ?></td>
      <td><?= !empty($u['has_api']) ? 'sì' : '—' ?></td>
      <td>
        <form method="post" action="<?= h(url('admin/utenti')) ?>" class="inline">
          <?= \Phlash\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <?php if ($u['status']==='active'): ?>
            <button name="act" value="ban">Sospendi</button>
          <?php else: ?>
            <button name="act" value="unban">Riattiva</button>
          <?php endif; ?>
          <?php if ($u['role']==='user'): ?>
            <button name="act" value="make_admin">Rendi admin</button>
          <?php else: ?>
            <button name="act" value="make_user">Rendi utente</button>
          <?php endif; ?>
          <button name="act" value="api_token"><?= !empty($u['has_api']) ? 'Rigenera token' : 'Token API' ?></button>
          <?php if (!empty($u['has_api'])): ?>
            <button name="act" value="api_revoke">Revoca token</button>
          <?php endif; ?>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
