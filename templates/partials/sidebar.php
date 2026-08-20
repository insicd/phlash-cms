<div class="phlashbox">
  <h4><?= !empty($user) ? h($user['username']) : 'Login' ?></h4>
  <div class="box-body">
    <?php if (!empty($user)): ?>
      <p>Karma: <strong><?= (int)$user['karma'] ?></strong></p>
      <p><a href="<?= h(url('invia')) ?>">Invia una storia</a></p>
      <p><a href="<?= h(url('utente/' . $user['username'])) ?>">Il tuo profilo</a></p>
      <?php if ($user['role']==='admin'): ?>
        <p><a href="<?= h(url('admin')) ?>">Amministrazione</a></p>
      <?php endif; ?>
    <?php else: ?>
      <form method="post" action="<?= h(url('login')) ?>">
        <?= \Phlash\Csrf::field() ?>
        <label>Utente o email</label>
        <input type="text" name="login" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Entra</button>
      </form>
      <p class="tiny"><a href="<?= h(url('registrati')) ?>">Non hai un account? Registrati.</a></p>
    <?php endif; ?>
  </div>
</div>

<div class="phlashbox">
  <h4>Sezioni</h4>
  <div class="box-body">
    <ul class="plain">
      <?php foreach ($topics ?? [] as $t): ?>
        <li><a href="<?= h(url('sezione/' . $t['slug'])) ?>"><?= topic_icon_html($t['icon'] ?? '') ?> <?= h($t['name']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<?php if (!empty($poll)): ?>
<div class="phlashbox">
  <h4>Sondaggio</h4>
  <div class="box-body">
    <p class="poll-q"><?= h($poll['question']) ?></p>
    <?php if (!empty($poll['voted'])): ?>
      <?php foreach ($poll['options'] as $opt):
        $pct = $poll['total'] ? round(100 * (int)$opt['votes'] / $poll['total']) : 0;
      ?>
        <div class="poll-row">
          <span><?= h($opt['label']) ?></span>
          <span class="muted"><?= (int)$opt['votes'] ?> (<?= $pct ?>%)</span>
        </div>
        <div class="poll-bar"><i style="width:<?= $pct ?>%"></i></div>
      <?php endforeach; ?>
      <p class="tiny"><?= (int)$poll['total'] ?> voti</p>
    <?php else: ?>
      <form method="post" action="<?= h(url('sondaggio')) ?>">
        <?= \Phlash\Csrf::field() ?>
        <?php foreach ($poll['options'] as $opt): ?>
          <label class="poll-opt">
            <input type="radio" name="option_id" value="<?= (int)$opt['id'] ?>" required>
            <?= h($opt['label']) ?>
          </label>
        <?php endforeach; ?>
        <button type="submit">Vota</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="phlashbox">
  <h4>Commenti recenti</h4>
  <div class="box-body">
    <?php if (empty($recent_comments)): ?>
      <p class="muted tiny">Ancora silenzio.</p>
    <?php else: ?>
      <ul class="plain recent-c">
        <?php foreach ($recent_comments as $rc):
          $who = $rc['username'] ?: ($rc['author_name'] !== '' ? $rc['author_name'] : 'Codardo Anonimo');
          $snip = excerpt($rc['body'], 70);
        ?>
          <li>
            <a href="<?= h(url('storia/' . $rc['slug'])) ?>#c<?= (int)$rc['id'] ?>"><?= h($snip) ?></a>
            <span class="muted"> — <?= h($who) ?> su <?= h($rc['title']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="phlashbox">
  <h4>Cerca</h4>
  <div class="box-body">
    <form action="<?= h(url('cerca')) ?>" method="get">
      <input type="text" name="q" placeholder="titoli e testi">
      <button type="submit">Cerca</button>
    </form>
  </div>
</div>
