<h1 class="page-h"><?= h($title ?? 'Errore') ?></h1>
<p><?= h($message ?? 'Qualcosa non ha funzionato.') ?></p>
<p><a href="<?= h(url()) ?>">Torna alle storie</a></p>
