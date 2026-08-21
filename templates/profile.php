<h1 class="page-h"><?= h($profile['username']) ?></h1>
<p class="story-by">
  Registrato il <?= h(italian_datetime($profile['created_at'])) ?>
  · karma <?= (int)$profile['karma'] ?>
  · <?= $profile['role']==='admin' ? 'amministratore' : 'utente' ?>
</p>
<?php if (!empty($profile['bio'])): ?>
  <p><?= nl2br(h($profile['bio'])) ?></p>
<?php endif; ?>

<?php if (!empty($own_profile)): ?>
  <h2 class="page-h2">API</h2>
  <p class="page-intro">
    Con un token puoi inviare storie in <em>In arrivo</em> da uno script. Le storie via API non vanno mai in homepage da sole.
  </p>
  <?php if (!empty($api_token_once)): ?>
    <p class="api-token"><?= h($api_token_once) ?></p>
    <p class="tiny muted">Copialo e conservalo: chiudendo questa pagina non sarà più visibile.</p>
  <?php elseif (!empty($has_api_token)): ?>
    <p class="muted">Un token è già attivo. Per sicurezza il valore non viene mostrato di nuovo.</p>
  <?php else: ?>
    <p class="muted">Nessun token attivo.</p>
  <?php endif; ?>
  <form method="post" action="<?= h(url('utente/api-token')) ?>" class="inline">
    <?= \Phlash\Csrf::field() ?>
    <button name="act" value="generate"><?= !empty($has_api_token) || !empty($api_token_once) ? 'Rigenera token' : 'Genera token' ?></button>
    <?php if (!empty($has_api_token) || !empty($api_token_once)): ?>
      <button name="act" value="revoke">Revoca</button>
    <?php endif; ?>
  </form>
<?php endif; ?>

<h2 class="page-h2">Storie pubblicate</h2>
<?php if (!$stories): ?>
  <p class="muted">Nessuna storia in homepage, per ora.</p>
<?php else: ?>
  <ul class="plain">
    <?php foreach ($stories as $s): ?>
      <li><a href="<?= h(url('storia/' . $s['slug'])) ?>"><?= h($s['title']) ?></a>
        <span class="muted"> — <?= h($s['topic_name']) ?>, punteggio <?= (int)$s['score'] ?></span></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<h2 class="page-h2">Commenti recenti</h2>
<?php if (!$comments): ?>
  <p class="muted">Nessun commento firmato.</p>
<?php else: ?>
  <ul class="plain">
    <?php foreach ($comments as $c): ?>
      <li>
        <a href="<?= h(url('storia/' . $c['slug'])) ?>#c<?= (int)$c['id'] ?>"><?= h(excerpt($c['body'], 110)) ?></a>
        <span class="muted"> su <?= h($c['title']) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
