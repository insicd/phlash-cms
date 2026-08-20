<?php
$md_level = $md_level ?? 'full';
?>
<div class="md-bar" role="toolbar" aria-label="Formattazione Markdown">
  <button type="button" data-md="bold" title="Grassetto (Ctrl+B)"><b>B</b></button>
  <button type="button" data-md="italic" title="Corsivo (Ctrl+I)"><i>I</i></button>
  <button type="button" data-md="code" title="Codice inline"><code>code</code></button>
  <button type="button" data-md="link" title="Link (Ctrl+K)">Link</button>
  <?php if ($md_level === 'full'): ?>
    <span class="md-sep"></span>
    <button type="button" data-md="h2" title="Titolo">H</button>
    <button type="button" data-md="quote" title="Citazione">&gt;</button>
    <button type="button" data-md="ul" title="Elenco puntato">-</button>
    <button type="button" data-md="ol" title="Elenco numerato">1.</button>
    <button type="button" data-md="pre" title="Blocco di codice">```</button>
  <?php endif; ?>
</div>
