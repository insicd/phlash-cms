<h1 class="page-h">Registrati</h1>
<p class="page-intro">Con un account puoi proporre news, topic lunghi e votare. Commentare si può anche senza.</p>
<form method="post" action="<?= h(url('registrati')) ?>" class="stack narrow">
  <?= \Phlash\Csrf::field() ?>
  <label>Username
    <input type="text" name="username" required minlength="3" maxlength="20" pattern="[A-Za-z0-9_]+" value="<?= h($_POST['username'] ?? '') ?>">
  </label>
  <label>Email
    <input type="email" name="email" required>
  </label>
  <label>Password (min. 8 caratteri)
    <input type="password" name="password" required minlength="8">
  </label>
  <label>Conferma password
    <input type="password" name="password2" required minlength="8">
  </label>
  <label>Anti-spam: <?= (int)$captcha['a'] ?> + <?= (int)$captcha['b'] ?> =
    <input type="text" name="captcha" inputmode="numeric" required class="captcha">
  </label>
  <button type="submit" class="primary">Crea account</button>
</form>
