<?php
$c = $c ?? [];
$score = (int) $c['score'];
$who = $c['username'] ?: ($c['author_name'] !== '' ? $c['author_name'] : 'Codardo Anonimo');
$isAnon = empty($c['username']);
$cls = 'cmt';
if ($score >= 5) $cls .= ' c-high';
elseif ($score >= 2) $cls .= ' c-good';
elseif ($score < 0) $cls .= ' c-neg';
$below = $score < (int)($threshold ?? 1);
if ($below) $cls .= ' c-below';
?>
<div class="<?= $cls ?>" id="c<?= (int)$c['id'] ?>" data-score="<?= $score ?>">
  <div class="cmt-head">
    <button type="button" class="cmt-toggle" aria-label="comprimi">−</button>
    <span class="cmt-score">Punteggio: <?= $score ?></span>
    <?php if ($isAnon): ?>
      <span class="coward"><?= h($who) ?></span>
    <?php else: ?>
      <a href="<?= h(url('utente/' . $c['username'])) ?>"><?= h($c['username']) ?></a>
      <span class="muted">karma <?= (int)($c['user_karma'] ?? 0) ?></span>
    <?php endif; ?>
    <span class="muted"><?= h(italian_datetime($c['created_at'])) ?></span>
  </div>
  <div class="cmt-body">
    <div class="cmt-text"><?= \Phlash\Markdown::inlineComment($c['body']) ?></div>
    <div class="cmt-actions">
      <?php if (!empty($user)): ?>
        <form class="inline" method="post" action="<?= h(url('commento/vota')) ?>">
          <?= \Phlash\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <input type="hidden" name="value" value="1">
          <button type="submit">+1</button>
        </form>
        <form class="inline" method="post" action="<?= h(url('commento/vota')) ?>">
          <?= \Phlash\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <input type="hidden" name="value" value="-1">
          <button type="submit">−1</button>
        </form>
      <?php endif; ?>
      <button type="button" class="reply-btn" data-target="reply-<?= (int)$c['id'] ?>">Rispondi</button>
    </div>
    <div class="reply-form" id="reply-<?= (int)$c['id'] ?>" hidden>
      <?php
        $parent_id = (int)$c['id'];
        $compact = true;
        include PHLASH_ROOT . '/templates/partials/comment_form.php';
      ?>
    </div>
    <?php if (!empty($c['children'])): ?>
      <div class="cmt-kids">
        <?php foreach ($c['children'] as $child): ?>
          <?php render_comment_node($child, $ctx); ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
