<?php
namespace Phlash;

/**
 * Statistiche first-party: un INSERT per pagina pubblica, niente JS esterni.
 */
class Stats
{
    private static bool $ready = false;

    public static function hit(string $template, array $data): void
    {
        if ($template === 'error' || !empty($data['admin_nav'])) {
            return;
        }
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            return;
        }
        if (Auth::isAdmin() || self::isBot() || self::isPrefetch()) {
            return;
        }
        $path = self::normalizePath(phlash_path());
        if ($path === '' || self::skipPath($path)) {
            return;
        }
        self::ensure();
        $title = mb_substr(trim((string) ($data['title'] ?? '')), 0, 200);
        try {
            Database::insert(
                'INSERT INTO pageviews (viewed_at, path, title, visitor) VALUES (?, ?, ?, ?)',
                [date('Y-m-d H:i:s'), $path, $title, self::visitorId()]
            );
        } catch (\Throwable $e) {
            return;
        }
        if (random_int(1, 250) === 1) {
            Database::query(
                'DELETE FROM pageviews WHERE viewed_at < ?',
                [date('Y-m-d H:i:s', time() - 400 * 86400)]
            );
        }
    }

    public static function report(string $from, string $to): array
    {
        self::ensure();
        $viewsRow = Database::one(
            'SELECT COUNT(*) AS n FROM pageviews WHERE viewed_at >= ? AND viewed_at < ?',
            [$from, $to]
        );
        $uniqRow = Database::one(
            'SELECT COUNT(DISTINCT visitor) AS n FROM pageviews WHERE viewed_at >= ? AND viewed_at < ?',
            [$from, $to]
        );
        $views = (int) ($viewsRow['n'] ?? 0);
        $uniques = (int) ($uniqRow['n'] ?? 0);
        $first = Database::one('SELECT MIN(viewed_at) AS d FROM pageviews');
        $grain = (strtotime($to) - strtotime($from)) > 92 * 86400 ? 'month' : 'day';
        if ($grain === 'month') {
            $series = Database::all(
                "SELECT DATE_FORMAT(viewed_at, '%Y-%m-01') AS bucket, COUNT(*) AS views, COUNT(DISTINCT visitor) AS uniques
                 FROM pageviews WHERE viewed_at >= ? AND viewed_at < ?
                 GROUP BY DATE_FORMAT(viewed_at, '%Y-%m-01') ORDER BY bucket",
                [$from, $to]
            );
        } else {
            $series = Database::all(
                "SELECT DATE(viewed_at) AS bucket, COUNT(*) AS views, COUNT(DISTINCT visitor) AS uniques
                 FROM pageviews WHERE viewed_at >= ? AND viewed_at < ?
                 GROUP BY DATE(viewed_at) ORDER BY bucket",
                [$from, $to]
            );
        }
        if ($views === 0) {
            $series = [];
        } else {
            $seriesFrom = $from;
            if (!empty($first['d']) && $first['d'] > $from) {
                $seriesFrom = $first['d'];
            }
            $series = self::fillSeries($series, $seriesFrom, $to, $grain);
        }
        $top = Database::all(
            'SELECT path, MAX(title) AS title, COUNT(*) AS views, COUNT(DISTINCT visitor) AS uniques
             FROM pageviews WHERE viewed_at >= ? AND viewed_at < ?
             GROUP BY path ORDER BY views DESC LIMIT 25',
            [$from, $to]
        );
        return [
            'views' => $views,
            'uniques' => $uniques,
            'pages_per_visit' => $uniques > 0 ? round($views / $uniques, 1) : 0,
            'top' => $top,
            'series' => $series,
            'series_grain' => $grain,
            'since' => $first['d'] ?? null,
        ];
    }

    private static function fillSeries(array $rows, string $from, string $to, string $grain): array
    {
        $by = [];
        foreach ($rows as $row) {
            $by[(string) $row['bucket']] = $row;
        }
        $start = strtotime(substr($from, 0, 10)) ?: time();
        $last = (strtotime($to) ?: time()) - 1;
        $out = [];
        if ($grain === 'month') {
            $cursor = strtotime(date('Y-m-01', $start)) ?: $start;
            $limit = strtotime(date('Y-m-01', $last)) ?: $last;
            while ($cursor <= $limit) {
                $key = date('Y-m-01', $cursor);
                $out[] = $by[$key] ?? ['bucket' => $key, 'views' => 0, 'uniques' => 0];
                $cursor = strtotime('+1 month', $cursor) ?: ($cursor + 32 * 86400);
            }
            return $out;
        }
        $cursor = $start;
        $limit = strtotime(date('Y-m-d', $last)) ?: $last;
        while ($cursor <= $limit) {
            $key = date('Y-m-d', $cursor);
            $out[] = $by[$key] ?? ['bucket' => $key, 'views' => 0, 'uniques' => 0];
            $cursor += 86400;
        }
        return $out;
    }

    public static function ensure(): void
    {
        if (self::$ready) {
            return;
        }
        self::$ready = true;
        Database::pdo()->exec(
            "CREATE TABLE IF NOT EXISTS pageviews (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              viewed_at DATETIME NOT NULL,
              path VARCHAR(250) NOT NULL,
              title VARCHAR(200) NOT NULL DEFAULT '',
              visitor CHAR(32) NOT NULL,
              KEY idx_viewed (viewed_at),
              KEY idx_path_viewed (path, viewed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function visitorId(): string
    {
        $cookie = (string) ($_COOKIE['phlash_vid'] ?? '');
        if (preg_match('/^[a-f0-9]{32}$/', $cookie)) {
            return $cookie;
        }
        $id = bin2hex(random_bytes(16));
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
        $path = parse_url(phlash_base_url(), PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }
        setcookie('phlash_vid', $id, [
            'expires' => time() + 86400 * 400,
            'path' => $path,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $https,
        ]);
        $_COOKIE['phlash_vid'] = $id;
        return $id;
    }

    private static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        if ($path === '/index.php') {
            $path = '/';
        }
        return mb_substr($path, 0, 250);
    }

    private static function skipPath(string $path): bool
    {
        if (strpos($path, '/admin') === 0) {
            return true;
        }
        return $path === '/rss' || $path === '/logout';
    }

    private static function isPrefetch(): bool
    {
        $purpose = strtolower((string) ($_SERVER['HTTP_PURPOSE'] ?? $_SERVER['HTTP_SEC_PURPOSE'] ?? ''));
        return strpos($purpose, 'prefetch') !== false || strpos($purpose, 'prerender') !== false;
    }

    private static function isBot(): bool
    {
        $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '') {
            return true;
        }
        foreach ([
            'bot', 'spider', 'crawler', 'curl/', 'wget', 'python-requests', 'httpclient',
            'slurp', 'bingpreview', 'facebookexternalhit', 'pingdom', 'uptimerobot',
            'preview', 'headless', 'phantom', 'scrapy',
        ] as $n) {
            if (strpos($ua, $n) !== false) {
                return true;
            }
        }
        return false;
    }
}
