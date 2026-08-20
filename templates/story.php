<?php include PHLASH_ROOT . '/templates/partials/story.php'; ?>

<?php if (!empty($tags)): ?>
  <p class="tags">Tag:
    <?php foreach ($tags as $tg): ?>
      <span class="tag"><?= h($tg['name']) ?></span>
    <?php endforeach; ?>
  </p>
<?php endif; ?>

<section id="comments" class="comments">
  <div class="comments-bar">
    <h2><?= (int)$comment_count ?> commenti</h2>
    <form class="threshold" method="get" action="<?= h(url('storia/' . $story['slug'])) ?>">
      <label>Soglia
        <select name="soglia" onchange="this.form.submit()">
          <?php foreach ([-1, 0, 1, 2, 3, 4, 5] as $n): ?>
            <option value="<?= $n ?>" <?= (int)$threshold === $n ? 'selected' : '' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Ordina
        <select name="ordina" onchange="this.form.submit()">
          <option value="thread" <?= $sort==='thread'?'selected':'' ?>>Discussione</option>
          <option value="best" <?= $sort==='best'?'selected':'' ?>>Punteggio</option>
          <option value="new" <?= $sort==='new'?'selected':'' ?>>Recenti</option>
        </select>
      </label>
    </form>
  </div>
  <p class="tiny muted">I commenti sotto la soglia restano compressi. Chiunque può scrivere, anche senza account usando il Codardo Anonimo.</p>

  <?php
    $ctx = [
        'user' => $user ?? null,
        'story' => $story,
        'threshold' => $threshold,
        'allow_anon' => $allow_anon,
        'captcha' => $captcha,
    ];
    foreach ($tree as $node) {
        render_comment_node($node, $ctx);
    }
  ?>

  <div class="post-comment">
    <h3>Scrivi un commento</h3>
    <?php
      $parent_id = 0;
      $compact = false;
      include PHLASH_ROOT . '/templates/partials/comment_form.php';
    ?>
  </div>
</section>
