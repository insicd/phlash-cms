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
$mesi = [1=>'gen',2=>'feb',3=>'mar',4=>'apr',5=>'mag',6=>'giu',7=>'lug',8=>'ago',9=>'set',10=>'ott',11=>'nov',12=>'dic'];
$grain = (string) ($report['series_grain'] ?? 'day');
$tz = phlash_timezone();
$labelBucket = static function (string $bucket) use ($grain, $mesi, $tz): string {
    try {
        $when = new DateTime($bucket, $tz);
    } catch (Throwable $e) {
        $when = new DateTime('now', $tz);
    }
    $m = $mesi[(int) $when->format('n')];
    if ($grain === 'hour') {
        return $when->format('j') . ' ' . $m . ' ' . $when->format('H:i');
    }
    if ($grain === 'month') {
        return $m . ' ' . $when->format('Y');
    }
    return $when->format('j') . ' ' . $m;
};
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
$trendLabels = [];
$trendViews = [];
$trendUniques = [];
foreach ($report['series'] as $row) {
    $trendLabels[] = $labelBucket((string) $row['bucket']);
    $trendViews[] = (int) $row['views'];
    $trendUniques[] = (int) $row['uniques'];
}
$topSlice = array_slice($report['top'], 0, 10);
$barLabels = [];
$barViews = [];
foreach ($topSlice as $row) {
    $barLabels[] = $pageLabel($row);
    $barViews[] = (int) $row['views'];
}
$shareSlice = array_slice($report['top'], 0, 8);
$shareLabels = [];
$shareViews = [];
$shareRest = 0;
foreach ($report['top'] as $i => $row) {
    if ($i < 8) {
        $shareLabels[] = $pageLabel($row);
        $shareViews[] = (int) $row['views'];
    } else {
        $shareRest += (int) $row['views'];
    }
}
if ($shareRest > 0) {
    $shareLabels[] = 'Altre';
    $shareViews[] = $shareRest;
}
$trendTitle = $grain === 'hour' ? 'Andamento orario' : ($grain === 'month' ? 'Andamento mensile' : 'Andamento giornaliero');
$charts = [
    'trendTitle' => $trendTitle,
    'labels' => $trendLabels,
    'views' => $trendViews,
    'uniques' => $trendUniques,
    'barLabels' => $barLabels,
    'barViews' => $barViews,
    'shareLabels' => $shareLabels,
    'shareViews' => $shareViews,
];
?>
<h1 class="page-h">Statistiche</h1>
<p class="page-intro">
  Visite raccolte sul sito, senza servizi esterni. Sono esclusi bot, prefetch e le sessioni admin.
  I dati partono da quando il tracciamento è attivo<?php if (!empty($report['since'])): ?>
    (<?= h(italian_datetime($report['since'])) ?>)<?php endif; ?>.
  Grafici con Chart.js in locale: passa il mouse o tocca un punto per i numeri.
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
<p class="muted"><?= h($range['label']) ?> · fuso orario <?= h($timezone ?? \Phlash\Database::timezoneName()) ?></p>

<ul class="stats">
  <li><strong><?= h($fmt($report['views'])) ?></strong> pagine viste</li>
  <li><strong><?= h($fmt($report['uniques'])) ?></strong> visitatori unici</li>
  <li><strong><?= h((string) $report['pages_per_visit']) ?></strong> pagine per visitatore</li>
</ul>

<?php if (!$report['series']): ?>
  <p class="muted">Nessuna visita in questo periodo. Naviga il sito (non da admin) per iniziare a raccogliere dati.</p>
<?php else: ?>
  <script type="application/json" id="stats-charts-data"><?= json_encode($charts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>

  <h2 class="page-h2"><?= h($trendTitle) ?></h2>
  <div class="chart-card">
    <canvas id="chart-trend" aria-label="<?= h($trendTitle) ?>"></canvas>
  </div>

  <div class="chart-grid">
    <div>
      <h2 class="page-h2">Pagine più viste</h2>
      <div class="chart-card chart-card-bar">
        <canvas id="chart-top" aria-label="Pagine più viste"></canvas>
      </div>
    </div>
    <div>
      <h2 class="page-h2">Quota sul traffico</h2>
      <div class="chart-card chart-card-pie">
        <canvas id="chart-share" aria-label="Quota sul traffico"></canvas>
      </div>
    </div>
  </div>
<?php endif; ?>

<h2 class="page-h2">Dettaglio pagine</h2>
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
