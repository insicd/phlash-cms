<h1 class="page-h">Accedi</h1>
<p class="page-intro">Per <strong>inviare storie</strong> e votare serve un account. I commenti, invece, possono restare anonimi.</p>
<form method="post" action="<?= h(url('login')) ?>" class="stack narrow">
  <?= \Phlash\Csrf::field() ?>
  <label>Username o email
    <input type="text" name="login" required autofocus>
  </label>
  <label>Password
    <input type="password" name="password" required>
  </label>
  <button type="submit" class="primary">Entra</button>
</form>
<p>Nuovo su Phlash? <a href="<?= h(url('registrati')) ?>">Registrati</a>.</p>
