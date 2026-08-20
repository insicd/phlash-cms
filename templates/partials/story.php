<?php
$story = $story ?? [];
$list_view = !empty($list_view);
$href = url('storia/' . $story['slug']);
$when = $story['status'] === 'published' && !empty($story['published_at'])
    ? $story['published_at'] : $story['created_at'];
$dept = $story['dept'] !== '' ? $story['dept'] : $story['topic_slug'];
$host = source_host($story['source_url'] ?? null);
$comments = (int) $story['comment_count'];
?>
<article class="story">
  <div class="story-icon" title="<?= h($story['topic_name']) ?>"><?= topic_icon_html($story['topic_icon'] ?? '', $story['topic_name']) ?></div>
  <header class="story-head">
    <h2 class="story-title">
      <a href="<?= h($href) ?>"><?= h($story['title']) ?></a>
      <?php if ($host !== ''): ?>
        <span class="story-src">(<?= h($host) ?>)</span>
      <?php endif; ?>
    </h2>
    <a class="story-bubble" href="<?= h($href) ?>#comments" title="<?= $comments ?> commenti">
      <?= $comments ?>
    </a>
  </header>
  <p class="story-meta">
    Inviato da <a href="<?= h(url('utente/' . $story['username'])) ?>"><?= h($story['username']) ?></a>
    il <?= h(italian_datetime($when)) ?>
    dal dipartimento <em><?= h($dept) ?></em>.
    <?php if ($story['status'] !== 'published'): ?>
      <span class="badge-pending">in arrivo</span>
    <?php endif; ?>
  </p>
  <div class="story-body">
    <?= \Phlash\Markdown::parse($story['body'], !$list_view) ?>
    <?php if (!empty($story['source_url'])): ?>
      <p class="source"><a href="<?= h($story['source_url']) ?>" rel="nofollow noopener" target="_blank"><?= h($story['source_url']) ?></a></p>
    <?php endif; ?>
  </div>
  <?php
    $canVote = !empty($user) && (int)$user['id'] !== (int)$story['user_id'];
    $canEdit = !empty($user) && ($user['role']==='admin' || ((int)$user['id']===(int)$story['user_id'] && $story['status']!=='published'));
  ?>
  <?php if ($canVote || $canEdit || $story['status'] !== 'published'): ?>
    <div class="story-tools">
      <span>Punteggio: <?= (int)$story['score'] ?></span>
      <?php if ($canVote): ?>
        <form class="inline" method="post" action="<?= h(url('storia/vota')) ?>">
          <?= \Phlash\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int)$story['id'] ?>">
          <button type="submit" class="vote-up">+1</button>
        </form>
      <?php endif; ?>
      <?php if ($canEdit): ?>
        <a href="<?= h(url('invia?id=' . (int)$story['id'])) ?>">modifica</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</article>
