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
  ?>
  <p class="tiny muted">Tocca una barra per i dettagli di quel giorno.</p>
  <style>
    .spark-ui > .spark-tip { display: none; }
    <?php foreach ($report['series'] as $i => $row): ?>
    #sd<?= (int)$i ?>:checked ~ .spark label[for="sd<?= (int)$i ?>"] { background: #fdba74; }
    #sd<?= (int)$i ?>:checked ~ .spark label[for="sd<?= (int)$i ?>"] .spark-fill { background: var(--accent-hot); }
    #sd<?= (int)$i ?>:checked ~ #st<?= (int)$i ?> { display: block; }
    <?php endforeach; ?>
  </style>
  <div class="spark-ui">
    <?php foreach ($report['series'] as $i => $row): ?>
      <input class="spark-input" type="radio" name="spark-day" id="sd<?= (int)$i ?>" <?= ((int)$i === $sparkLast) ? 'checked' : '' ?>>
    <?php endforeach; ?>
    <div class="spark" id="spark-chart" role="group" aria-label="Andamento delle pagine viste">
      <?php foreach ($report['series'] as $i => $row): ?>
        <?php
          $viewsN = (int) $row['views'];
          $hgt = $viewsN <= 0 ? 2 : max(4, (int) round(($viewsN / $maxBar) * 72));
          $tip = $row['bucket'] . ': ' . $fmt($viewsN) . ' viste, ' . $fmt($row['uniques']) . ' unici';
        ?>
        <label class="spark-bar" for="sd<?= (int)$i ?>" title="<?= h($tip) ?>">
          <span class="spark-fill" style="height: <?= $hgt ?>px"></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?php foreach ($report['series'] as $i => $row): ?>
      <?php
        $tip = $row['bucket'] . ': ' . $fmt($row['views']) . ' viste, ' . $fmt($row['uniques']) . ' unici';
      ?>
      <p class="spark-tip" id="st<?= (int)$i ?>"><?= h($tip) ?></p>
    <?php endforeach; ?>
  </div>
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
