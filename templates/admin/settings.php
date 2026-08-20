<h1 class="page-h">Impostazioni</h1>
<form method="post" action="<?= h(url('admin/impostazioni')) ?>" class="stack">
  <?= \Phlash\Csrf::field() ?>
  <label>Nome del sito
    <input type="text" name="site_name" value="<?= h($vals['site_name']) ?>" required>
  </label>
  <label>Tagline
    <input type="text" name="site_tagline" value="<?= h($vals['site_tagline']) ?>">
  </label>
  <label>Storie per pagina
    <input type="number" name="stories_per_page" min="5" max="30" value="<?= h($vals['stories_per_page']) ?>">
  </label>
  <label>Voti per promuovere dalla coda
    <input type="number" name="promote_threshold" min="1" max="50" value="<?= h($vals['promote_threshold']) ?>">
  </label>
  <label>Soglia commenti predefinita
    <input type="number" name="comment_threshold" value="<?= h($vals['comment_threshold']) ?>">
  </label>
  <label>Fuso orario
    <input type="text" name="timezone" value="<?= h($vals['timezone']) ?>">
  </label>
  <label class="chk"><input type="checkbox" name="allow_anon_comments" value="1" <?= $vals['allow_anon_comments']==='1'?'checked':'' ?>> Commenti anonimi (Codardo Anonimo)</label>
  <label class="chk"><input type="checkbox" name="registration_open" value="1" <?= $vals['registration_open']==='1'?'checked':'' ?>> Registrazioni aperte</label>
  <label>CSS personalizzato
    <textarea name="custom_css" rows="8"><?= h($vals['custom_css']) ?></textarea>
  </label>
  <button type="submit" class="primary">Salva</button>
</form>
