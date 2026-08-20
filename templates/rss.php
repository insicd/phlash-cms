<rss version="2.0">
  <channel>
    <title><?= h($site) ?></title>
    <link><?= h(url()) ?></link>
    <description><?= h($site) ?> — storie pubblicate</description>
    <?php foreach ($stories as $s): ?>
    <item>
      <title><?= h($s['title']) ?></title>
      <link><?= h(url('storia/' . $s['slug'])) ?></link>
      <guid><?= h(url('storia/' . $s['slug'])) ?></guid>
      <pubDate><?= h(date('r', strtotime($s['published_at'] ?: $s['created_at']))) ?></pubDate>
      <description><?= h(excerpt($s['body'], 280)) ?></description>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
