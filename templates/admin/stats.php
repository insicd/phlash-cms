<?php
$presets = [
    'oggi' => 'Oggi',
    '7g' => '7 giorni',
    '30g' => '30 giorni',
    '90g' => '90 giorni',
    'tutto' => 'Tutto',
];
$fmt = static function ($n): string {
    return number_format((int) $n, 0, ',', '.');
};
$maxBar = 1;
foreach ($report['series'] as $row) {
    $maxBar = max($maxBar, (int) $row['views']);
}
$pageLabel = static function (array $row): string {
    $path = (string) $row['path'];
    $title = trim((string) ($row['title'] ?? ''));
    $names = [
        '/' => 'Homepage',
        '/upcoming' => 'In arrivo',
        '/cerca' => 'Cerca',
        '/login' => 'Accedi',
        '/registrati' => 'Registrati',
        '/invia' => 'Invia una storia',
    ];
    if (isset($names[$path])) {
        return $names[$path];
    }
    return $title !== '' ? $title : $path;
};
?>
<h1 class="page-h">Statistiche</h1>
<p class="page-intro">
  Visite raccolte sul sito, senza servizi esterni. Sono esclusi bot, prefetch e le sessioni admin.
  I dati partono da quando il tracciamento è attivo<?php if (!empty($report['since'])): ?>
    (<?= h(italian_datetime($report['since'])) ?>)<?php endif; ?>.
</p>

<nav class="period-nav">
  <?php foreach ($presets as $key => $label): ?>
    <a href="<?= h(url('admin/statistiche?periodo=' . $key)) ?>" class="<?= ($range['period'] ?? '') === $key ? 'on' : '' ?>"><?= h($label) ?></a>
  <?php endforeach; ?>
</nav>
<form method="get" action="<?= h(url('admin/statistiche')) ?>" class="period-custom">
  <input type="hidden" name="periodo" value="custom">
  <label>Dal
    <input type="date" name="da" value="<?= h($range['da']) ?>" required>
  </label>
  <label>al
    <input type="date" name="a" value="<?= h($range['a']) ?>" required>
  </label>
  <button type="submit">Applica periodo</button>
</form>
<p class="muted"><?= h($range['label']) ?></p>

<ul class="stats">
  <li><strong><?= h($fmt($report['views'])) ?></strong> pagine viste</li>
  <li><strong><?= h($fmt($report['uniques'])) ?></strong> visitatori unici</li>
  <li><strong><?= h((string) $report['pages_per_visit']) ?></strong> pagine per visitatore</li>
</ul>

<h2 class="page-h2"><?= $report['series_grain'] === 'month' ? 'Andamento mensile' : 'Andamento giornaliero' ?></h2>
<?php if (!$report['series']): ?>
  <p class="muted">Nessuna visita in questo periodo. Naviga il sito (non da admin) per iniziare a raccogliere dati.</p>
<?php else: ?>
  <?php
    $sparkLast = count($report['series']) - 1;
    $sparkTip0 = '';
    if ($sparkLast >= 0) {
      $lastRow = $report['series'][$sparkLast];
      $sparkTip0 = $lastRow['bucket'] . ': ' . $fmt($lastRow['views']) . ' viste, ' . $fmt($lastRow['uniques']) . ' unici';
    }
  ?>
  <p class="tiny muted">Tocca una barra per i dettagli di quel giorno. In evidenza: ultimo giorno del periodo.</p>
  <div class="spark" id="spark-chart" role="group" aria-label="Andamento delle pagine viste">
    <?php foreach ($report['series'] as $i => $row): ?>
      <?php
        $viewsN = (int) $row['views'];
        $hgt = $viewsN <= 0 ? 2 : max(4, (int) round(($viewsN / $maxBar) * 72));
        $tip = $row['bucket'] . ': ' . $fmt($viewsN) . ' viste, ' . $fmt($row['uniques']) . ' unici';
        $on = ((int) $i === $sparkLast);
      ?>
      <button type="button" class="spark-bar<?= $on ? ' is-on' : '' ?>" data-tip="<?= h($tip) ?>" title="<?= h($tip) ?>" aria-label="<?= h($tip) ?>" aria-pressed="<?= $on ? 'true' : 'false' ?>">
        <span class="spark-fill" style="height: <?= $hgt ?>px"></span>
      </button>
    <?php endforeach; ?>
  </div>
  <p class="spark-tip" id="spark-tip"><?= h($sparkTip0) ?></p>
<?php endif; ?>

<h2 class="page-h2">Pagine più viste</h2>
<?php if (!$report['top']): ?>
  <p class="muted">Niente da mostrare.</p>
<?php else: ?>
  <table class="grid">
    <tr>
      <th>Pagina</th>
      <th class="num">Viste</th>
      <th class="num">Visitatori</th>
    </tr>
    <?php foreach ($report['top'] as $row): ?>
      <tr>
        <td>
          <a href="<?= h(url(ltrim((string) $row['path'], '/'))) ?>"><?= h($pageLabel($row)) ?></a>
          <div class="tiny muted"><?= h($row['path']) ?></div>
        </td>
        <td class="num"><?= h($fmt($row['views'])) ?></td>
        <td class="num"><?= h($fmt($row['uniques'])) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
