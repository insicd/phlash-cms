(function () {
  var node = document.getElementById('stats-charts-data');
  if (!node || typeof Chart === 'undefined') {
    return;
  }
  var data;
  try {
    data = JSON.parse(node.textContent || '{}');
  } catch (err) {
    return;
  }

  var ink = '#1c1410';
  var muted = '#6b5344';
  var accent = '#c2410c';
  var hot = '#ea580c';
  var grid = 'rgba(154, 52, 18, 0.18)';
  var pie = ['#9a3412', '#c2410c', '#ea580c', '#f97316', '#d97706', '#b45309', '#7c2d12', '#fdba74', '#78716c'];

  Chart.defaults.font.family = 'Arial, Helvetica, Verdana, sans-serif';
  Chart.defaults.font.size = 12;
  Chart.defaults.color = muted;
  Chart.defaults.plugins.tooltip.backgroundColor = '#140c08';
  Chart.defaults.plugins.tooltip.titleColor = '#fff7ed';
  Chart.defaults.plugins.tooltip.bodyColor = '#ffedd5';
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.displayColors = true;
  Chart.defaults.plugins.legend.labels.boxWidth = 12;

  var trend = document.getElementById('chart-trend');
  if (trend && data.labels && data.labels.length) {
    new Chart(trend, {
      type: 'line',
      data: {
        labels: data.labels,
        datasets: [
          {
            label: 'Pagine viste',
            data: data.views,
            borderColor: accent,
            backgroundColor: 'rgba(194, 65, 12, 0.18)',
            fill: true,
            tension: 0.25,
            pointRadius: 3,
            pointHoverRadius: 6,
            pointBackgroundColor: accent,
          },
          {
            label: 'Visitatori unici',
            data: data.uniques,
            borderColor: hot,
            backgroundColor: 'rgba(234, 88, 12, 0.08)',
            fill: false,
            tension: 0.25,
            pointRadius: 3,
            pointHoverRadius: 6,
            pointBackgroundColor: hot,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'bottom' },
        },
        scales: {
          x: {
            ticks: { maxRotation: 45, minRotation: 0, autoSkip: true, maxTicksLimit: 12 },
            grid: { color: grid },
          },
          y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: grid },
          },
        },
      },
    });
  }

  var topEl = document.getElementById('chart-top');
  if (topEl && data.barLabels && data.barLabels.length) {
    new Chart(topEl, {
      type: 'bar',
      data: {
        labels: data.barLabels,
        datasets: [{
          label: 'Viste',
          data: data.barViews,
          backgroundColor: accent,
          hoverBackgroundColor: hot,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'nearest', intersect: true },
        plugins: {
          legend: { display: false },
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: grid },
          },
          y: {
            ticks: { color: ink },
            grid: { display: false },
          },
        },
      },
    });
  }

  var shareEl = document.getElementById('chart-share');
  if (shareEl && data.shareLabels && data.shareLabels.length) {
    new Chart(shareEl, {
      type: 'doughnut',
      data: {
        labels: data.shareLabels,
        datasets: [{
          data: data.shareViews,
          backgroundColor: pie,
          borderColor: '#fff8f0',
          borderWidth: 2,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
        },
      },
    });
  }
})();
