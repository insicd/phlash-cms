<h1 class="page-h"><?= h($profile['username']) ?></h1>
<p class="story-by">
  Registrato il <?= h(italian_datetime($profile['created_at'])) ?>
  · karma <?= (int)$profile['karma'] ?>
  · <?= $profile['role']==='admin' ? 'amministratore' : 'utente' ?>
</p>
<?php if (!empty($profile['bio'])): ?>
  <p><?= nl2br(h($profile['bio'])) ?></p>
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
