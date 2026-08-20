<h1 class="page-h"><?= !empty($story['id']) ? 'Modifica storia' : 'Invia una storia' ?></h1>
<p class="page-intro">
  Phlash non è un raccoglitore di link: il testo è obbligatorio e deve stare in piedi da solo.
  Serve un account. La storia finisce in <em>In arrivo</em> e sale in homepage con i voti della comunità
  (soglia: <?= h(\Phlash\Database::setting('promote_threshold', '5')) ?>) oppure se un admin la pubblica.
</p>

<?php if (!empty($preview)): ?>
  <div class="preview-box">
    <h2>Anteprima</h2>
    <h3><?= h($story['title']) ?></h3>
    <div class="story-body"><?= \Phlash\Markdown::parse($story['body']) ?></div>
  </div>
<?php endif; ?>

<form method="post" action="<?= h(url('invia')) ?>" class="stack">
  <?= \Phlash\Csrf::field() ?>
  <?php if (!empty($story['id'])): ?>
    <input type="hidden" name="id" value="<?= (int)$story['id'] ?>">
  <?php endif; ?>

  <label>Titolo
    <input type="text" name="title" required minlength="8" maxlength="200" value="<?= h($story['title'] ?? '') ?>">
  </label>
  <label>Sezione
    <select name="topic_id" required>
      <?php foreach ($topics as $t): ?>
        <option value="<?= (int)$t['id'] ?>" <?= (int)($story['topic_id'] ?? 0)===(int)$t['id']?'selected':'' ?>><?= h($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Dipartimento <span class="muted">(la riga «dal dipartimento …»)</span>
    <input type="text" name="dept" maxlength="80" value="<?= h($story['dept'] ?? '') ?>" placeholder="es. not-again, php-puro, too-long-did-read">
  </label>
  <label>URL di fonte <span class="muted">(facoltativo)</span>
    <input type="url" name="source_url" value="<?= h($story['source_url'] ?? '') ?>" placeholder="https://…">
  </label>
  <label>Testo</label>
  <div class="md-wrap">
    <?php $md_level = 'full'; include PHLASH_ROOT . '/templates/partials/md_toolbar.php'; ?>
    <textarea class="md-input" name="body" rows="18" required minlength="80"><?= h($story['body'] ?? '') ?></textarea>
  </div>
  <p class="tiny muted">Markdown: grassetto, corsivo, titoli, elenchi, citazioni, link, codice. Seleziona il testo e usa la barra.</p>
  <label>Tag <span class="muted">(separati da virgola)</span>
    <input type="text" name="tags" value="<?= h($tag_str ?? '') ?>" placeholder="linux, privacy, web">
  </label>
  <div class="btn-row">
    <button type="submit" name="preview" value="1">Anteprima</button>
    <button type="submit" class="primary"><?= !empty($story['id']) ? 'Salva' : 'Invia in coda' ?></button>
  </div>
</form>
