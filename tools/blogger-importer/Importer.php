<?php
/**
 * Importa un export Google Takeout di Blogger nello schema Phlash.
 */
class BloggerImporter
{
    private string $root;
    private string $takeout;
    private array $opts;
    private array $mediaIndex = [];
    private array $googleCache = [];
    private array $log = [];
    private array $topicMap = [
        'tecnologia' => 'tecnologia',
        'tech' => 'tecnologia',
        'software' => 'tecnologia',
        'internet' => 'tecnologia',
        'cultura' => 'cultura',
        'sport' => 'cultura',
        'cinema' => 'cultura',
        'film' => 'cultura',
        'musica' => 'cultura',
        'libri' => 'cultura',
        'scienza' => 'scienza',
        'salute' => 'scienza',
        'ambiente' => 'scienza',
        'giochi' => 'giochi',
        'videogiochi' => 'giochi',
        'politica' => 'societa',
        'società' => 'societa',
        'societa' => 'societa',
        'diritti' => 'societa',
    ];

    public function __construct(string $projectRoot, array $opts = [])
    {
        $this->root = rtrim($projectRoot, '/');
        $this->takeout = $this->root . '/tools/blogger-importer/Takeout/Blogger';
        $this->opts = array_merge([
            'blog' => 'ilGlobale.it',
            'user' => '',
            'dry_run' => false,
            'limit' => 0,
            'include_drafts' => false,
            'include_spam' => false,
            'site_url' => 'https://www.ilglobale.it',
        ], $opts);
    }

    public function listBlogs(): array
    {
        $out = [];
        foreach (glob($this->takeout . '/Blogs/*/feed.atom') ?: [] as $feed) {
            $dir = dirname($feed);
            $name = basename($dir);
            $xml = @simplexml_load_file($feed);
            $posts = 0;
            $comments = 0;
            $drafts = 0;
            if ($xml) {
                foreach ($xml->entry as $e) {
                    $ns = $e->children('http://schemas.google.com/blogger/2018');
                    $type = (string) $ns->type;
                    $status = (string) $ns->status;
                    if ($type === 'POST') {
                        $posts++;
                        if ($status === 'DRAFT') {
                            $drafts++;
                        }
                    } elseif ($type === 'COMMENT') {
                        $comments++;
                    }
                }
            }
            $out[] = [
                'name' => $name,
                'feed' => $feed,
                'posts' => $posts,
                'drafts' => $drafts,
                'comments' => $comments,
            ];
        }
        usort($out, fn ($a, $b) => $b['posts'] <=> $a['posts']);
        return $out;
    }

    public function run(): array
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);

        $blog = $this->findBlog($this->opts['blog']);
        if (!$blog) {
            throw new RuntimeException('Blog non trovato nel Takeout: ' . $this->opts['blog']);
        }

        $user = $this->resolveUser();
        $topics = $this->loadTopics();
        $defaultTopic = $topics['notizie'] ?? reset($topics);
        if (!$defaultTopic) {
            throw new RuntimeException('Nessuna sezione Phlash disponibile.');
        }

        $this->indexMedia($blog['dir']);
        $xml = simplexml_load_file($blog['feed']);
        if (!$xml) {
            throw new RuntimeException('Impossibile leggere ' . $blog['feed']);
        }

        $stats = [
            'blog' => $blog['name'],
            'posts_seen' => 0,
            'posts_imported' => 0,
            'posts_skipped' => 0,
            'comments_imported' => 0,
            'media_copied' => 0,
            'dry_run' => (bool) $this->opts['dry_run'],
        ];

        $postMap = [];
        $limit = (int) $this->opts['limit'];

        foreach ($xml->entry as $e) {
            $ns = $e->children('http://schemas.google.com/blogger/2018');
            if ((string) $ns->type !== 'POST') {
                continue;
            }
            $status = (string) $ns->status;
            if ($status === 'DRAFT' && !$this->opts['include_drafts']) {
                continue;
            }
            if ($status !== 'LIVE' && $status !== 'DRAFT') {
                continue;
            }
            if ($limit > 0 && ($stats['posts_imported'] + $stats['posts_skipped']) >= $limit) {
                break;
            }
            $stats['posts_seen']++;

            $title = trim(html_entity_decode((string) $e->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($title === '') {
                $title = 'Senza titolo';
            }
            $title = mb_substr($title, 0, 200);
            $filename = (string) $ns->filename;
            $slug = $this->slugFromFilename($filename, $title);
            $existing = \Phlash\Database::one('SELECT id FROM stories WHERE slug = ?', [$slug]);
            $bloggerId = (string) $e->id;

            if ($existing) {
                $stats['posts_skipped']++;
                $postMap[$bloggerId] = (int) $existing['id'];
                $this->log('skip', $title);
                continue;
            }

            $labels = $this->labels($e);
            $topic = $this->mapTopic($labels, $topics, $defaultTopic);
            $html = (string) $e->content;
            $copied = [];
            $markdown = $this->htmlToMarkdown($html, $copied);
            $stats['media_copied'] += count($copied);
            if (mb_strlen(trim($markdown)) < 2) {
                $markdown = $title;
            }

            $published = $this->toLocalSql((string) $e->published);
            $created = $this->toLocalSql((string) $ns->created ?: (string) $e->published);
            $dept = $labels ? slugify($labels[0]) : 'blogger';
            $dept = mb_substr($dept, 0, 80);
            $phStatus = $status === 'LIVE' ? 'published' : 'pending';
            $pubAt = $phStatus === 'published' ? $published : null;

            if ($this->opts['dry_run']) {
                $stats['posts_imported']++;
                $this->log('ok', $title . ' → ' . $topic['slug'] . ' [' . implode(', ', $labels) . ']');
                continue;
            }

            $id = \Phlash\Database::insert(
                'INSERT INTO stories (user_id, topic_id, title, slug, dept, body, source_url, status, score, comment_count, views, created_at, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, NULL, ?, 1, 0, 0, ?, ?)',
                [
                    (int) $user['id'],
                    (int) $topic['id'],
                    $title,
                    unique_slug('stories', $slug),
                    $dept,
                    $markdown,
                    $phStatus,
                    $created,
                    $pubAt,
                ]
            );
            story_tags_sync($id, implode(', ', $labels));
            $postMap[$bloggerId] = $id;
            $stats['posts_imported']++;
            $this->log('ok', $title);
        }

        if (!$this->opts['dry_run']) {
            $stats['comments_imported'] = $this->importComments($xml, $postMap);
            $stats['comments_imported'] += $this->importExternalComments($postMap);
        }

        return ['stats' => $stats, 'log' => $this->log];
    }

    /** Toglie le fonti da un import già fatto e riscrive gli URL Google verso uploads. */
    public function repair(): array
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);
        $blog = $this->findBlog($this->opts['blog']);
        if ($blog) {
            $this->indexMedia($blog['dir']);
        } else {
            $this->indexMedia($this->takeout . '/Blogs');
        }

        $host = parse_url((string) $this->opts['site_url'], PHP_URL_HOST) ?: 'ilglobale.it';
        $host = preg_replace('/^www\./i', '', (string) $host) ?: 'ilglobale.it';
        $like = '%' . $host . '%';
        if ($this->opts['dry_run']) {
            $row = \Phlash\Database::one('SELECT COUNT(*) AS n FROM stories WHERE source_url LIKE ?', [$like]);
            $cleared = (int) ($row['n'] ?? 0);
        } else {
            $cleared = \Phlash\Database::query(
                'UPDATE stories SET source_url = NULL WHERE source_url LIKE ?',
                [$like]
            )->rowCount();
        }

        $updated = 0;
        $media = 0;
        $rows = \Phlash\Database::all(
            'SELECT id, body FROM stories WHERE body LIKE ? OR body LIKE ? OR body LIKE ?',
            ['%googleusercontent%', '%bp.blogspot.com%', '%ggpht.com%']
        );
        foreach ($rows as $row) {
            $copied = [];
            $body = $this->rewriteRemoteImages((string) $row['body'], $copied);
            if ($body !== $row['body']) {
                if (!$this->opts['dry_run']) {
                    \Phlash\Database::query('UPDATE stories SET body = ? WHERE id = ?', [$body, (int) $row['id']]);
                }
                $updated++;
                $media += count($copied);
            }
        }
        return [
            'stats' => [
                'sources_cleared' => $cleared,
                'posts_rewritten' => $updated,
                'media_copied' => $media,
                'dry_run' => (bool) $this->opts['dry_run'],
            ],
            'log' => $this->log,
        ];
    }

    public function logs(): array
    {
        return $this->log;
    }

    private function findBlog(string $name): ?array
    {
        foreach ($this->listBlogs() as $b) {
            if (strcasecmp($b['name'], $name) === 0) {
                $b['dir'] = dirname($b['feed']);
                return $b;
            }
        }
        return null;
    }

    private function resolveUser(): array
    {
        $want = trim((string) $this->opts['user']);
        if ($want !== '') {
            $u = \Phlash\Database::one('SELECT * FROM users WHERE username = ? OR email = ?', [$want, $want]);
            if (!$u) {
                throw new RuntimeException('Utente Phlash non trovato: ' . $want);
            }
            return $u;
        }
        $u = \Phlash\Database::one("SELECT * FROM users WHERE role = 'admin' AND status = 'active' ORDER BY id ASC LIMIT 1");
        if (!$u) {
            throw new RuntimeException('Nessun admin attivo su Phlash.');
        }
        return $u;
    }

    private function loadTopics(): array
    {
        $rows = \Phlash\Database::all('SELECT * FROM topics');
        $out = [];
        foreach ($rows as $row) {
            $out[$row['slug']] = $row;
        }
        return $out;
    }

    private function labels(SimpleXMLElement $e): array
    {
        $labels = [];
        foreach ($e->category as $c) {
            $term = trim((string) $c['term']);
            if ($term === '' || strpos($term, 'http') === 0 || strpos($term, 'tag:') === 0) {
                continue;
            }
            $term = ltrim($term, '#');
            if ($term !== '') {
                $labels[] = mb_substr($term, 0, 40);
            }
        }
        return array_values(array_unique($labels));
    }

    private function mapTopic(array $labels, array $topics, array $default): array
    {
        foreach ($labels as $label) {
            $key = mb_strtolower($label);
            $slug = $this->topicMap[$key] ?? null;
            if ($slug && isset($topics[$slug])) {
                return $topics[$slug];
            }
            if (isset($topics[slugify($label)])) {
                return $topics[slugify($label)];
            }
        }
        return $default;
    }

    private function slugFromFilename(string $filename, string $title): string
    {
        $base = basename($filename, '.html');
        $base = preg_replace('/\.(html|htm)$/i', '', $base) ?? $base;
        return slugify($base !== '' ? $base : $title);
    }

    private function toLocalSql(string $iso): string
    {
        if ($iso === '') {
            return date('Y-m-d H:i:s');
        }
        try {
            $dt = new DateTime($iso);
            $tz = \Phlash\Database::setting('timezone', 'Europe/Rome');
            $dt->setTimezone(new DateTimeZone($tz));
            return $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return date('Y-m-d H:i:s');
        }
    }

    private function indexMedia(string $blogDir): void
    {
        $dirs = [$blogDir];
        $albums = $this->takeout . '/Albums';
        if (is_dir($albums)) {
            $dirs[] = $albums;
        }
        $ok = 'jpg,jpeg,png,gif,webp,mp4,mov,webm';
        foreach ($dirs as $dir) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $ext = strtolower($file->getExtension());
                if (strpos(',' . $ok . ',', ',' . $ext . ',') === false) {
                    continue;
                }
                $path = $file->getPathname();
                $this->mediaIndex[$file->getFilename()] = $path;
                $this->mediaIndex[urldecode($file->getFilename())] = $path;
                $this->mediaIndex[rawurlencode($file->getFilename())] = $path;
            }
        }
    }

    private function copyMedia(string $srcUrl): string
    {
        $srcUrl = trim($srcUrl);
        if ($srcUrl === '' || strpos($srcUrl, 'data:') === 0) {
            return $srcUrl;
        }
        $srcUrl = preg_replace('/#.*$/', '', $srcUrl) ?? $srcUrl;

        $token = $this->isGoogleHosted($srcUrl) ? $this->googleToken($srcUrl) : '';
        if ($token !== '' && isset($this->googleCache[$token])) {
            return $this->googleCache[$token];
        }

        $localFile = $this->matchLocalFile($srcUrl);
        if ($localFile) {
            $public = $this->storeFile($localFile, $this->safeFilename(basename($localFile)));
            if ($token !== '') {
                $this->googleCache[$token] = $public;
            }
            return $public;
        }
        if ($this->isGoogleHosted($srcUrl)) {
            return $this->downloadGoogle($srcUrl);
        }
        return $srcUrl;
    }

    private function matchLocalFile(string $srcUrl): ?string
    {
        $path = parse_url($srcUrl, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }
        $candidates = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '') {
                continue;
            }
            $decoded = urldecode($part);
            if (!preg_match('/\.(jpe?g|png|gif|webp|mp4|mov|webm)$/i', $decoded)) {
                continue;
            }
            $candidates[] = $decoded;
            $candidates[] = $part;
        }
        foreach ($candidates as $name) {
            if (isset($this->mediaIndex[$name])) {
                return $this->mediaIndex[$name];
            }
        }
        return null;
    }

    private function isGoogleHosted(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        return strpos($host, 'googleusercontent.com') !== false
            || strpos($host, 'bp.blogspot.com') !== false
            || strpos($host, 'ggpht.com') !== false;
    }

    private function googleToken(string $url): string
    {
        if (preg_match('/(AVvXs[A-Za-z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }
        $norm = preg_replace('#/s\d+/#', '/s/', $url) ?? $url;
        $norm = preg_replace('/=s\d+$/', '', $norm) ?? $norm;
        return substr(sha1($norm), 0, 20);
    }

    private function preferLargeGoogleUrl(string $url): string
    {
        $url = preg_replace('#/s\d+/#', '/s16000/', $url) ?? $url;
        $url = preg_replace('/=s\d+$/', '=s16000', $url) ?? $url;
        return $url;
    }

    private function downloadGoogle(string $url): string
    {
        $token = $this->googleToken($url);
        if (isset($this->googleCache[$token])) {
            return $this->googleCache[$token];
        }
        $rel = '/uploads/import/' . slugify($this->opts['blog']) . '/g-' . $token;
        if ($this->opts['dry_run']) {
            $this->googleCache[$token] = $rel . '.jpg';
            return $this->googleCache[$token];
        }
        $large = $this->preferLargeGoogleUrl($url);
        $bin = $this->fetchUrl($large);
        if ($bin === null && $large !== $url) {
            $bin = $this->fetchUrl($url);
        }
        if ($bin === null) {
            return $url;
        }
        $ext = $this->sniffExt($bin);
        $destDir = $this->root . '/uploads/import/' . slugify($this->opts['blog']);
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            return $url;
        }
        $name = 'g-' . $token . '.' . $ext;
        if (strlen($name) > 200) {
            $name = 'g-' . sha1($token) . '.' . $ext;
        }
        $dest = $destDir . '/' . $name;
        if (!is_file($dest)) {
            file_put_contents($dest, $bin);
        }
        $public = '/uploads/import/' . slugify($this->opts['blog']) . '/' . $name;
        $this->googleCache[$token] = $public;
        return $public;
    }

    private function fetchUrl(string $url): ?string
    {
        $data = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_USERAGENT => 'PhlashBloggerImporter/0.9',
            ]);
            $data = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (PHP_VERSION_ID < 80000) {
                curl_close($ch);
            }
            if ($code < 200 || $code >= 300) {
                $data = false;
            }
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 25,
                    'header' => "User-Agent: PhlashBloggerImporter/0.9\r\n",
                ],
            ]);
            $data = @file_get_contents($url, false, $ctx);
        }
        if (!is_string($data) || strlen($data) < 80) {
            return null;
        }
        if (stripos($data, '<html') !== false) {
            return null;
        }
        return $data;
    }

    private function sniffExt(string $bin): string
    {
        if (strncmp($bin, "\xFF\xD8\xFF", 3) === 0) {
            return 'jpg';
        }
        if (strncmp($bin, "\x89PNG", 4) === 0) {
            return 'png';
        }
        if (strncmp($bin, 'GIF8', 4) === 0) {
            return 'gif';
        }
        if (strncmp($bin, 'RIFF', 4) === 0 && strpos($bin, 'WEBP') !== false) {
            return 'webp';
        }
        return 'jpg';
    }

    private function safeFilename(string $name): string
    {
        $name = urldecode($name);
        $name = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $name) ?: 'file';
        return $name;
    }

    private function storeFile(string $src, string $safe): string
    {
        $rel = '/uploads/import/' . slugify($this->opts['blog']) . '/' . rawurlencode($safe);
        if ($this->opts['dry_run']) {
            return $rel;
        }
        $destDir = $this->root . '/uploads/import/' . slugify($this->opts['blog']);
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            return $src;
        }
        $dest = $destDir . '/' . $safe;
        if (!is_file($dest)) {
            @copy($src, $dest);
        }
        return $rel;
    }

    private function rewriteRemoteImages(string $body, array &$copied): string
    {
        return preg_replace_callback(
            '#https?://[^\s)<>"\']*(?:googleusercontent\.com|bp\.blogspot\.com|ggpht\.com)[^\s)<>"\']*#i',
            function ($m) use (&$copied) {
                $url = rtrim($m[0], '.,;');
                $local = $this->copyMedia($url);
                if ($local !== $url) {
                    $copied[$local] = true;
                }
                return $local;
            },
            $body
        ) ?? $body;
    }

    private function htmlToMarkdown(string $html, array &$copied): string
    {
        $html = trim(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($html === '') {
            return '';
        }
        $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $wrapped = '<div id="phlash-root">' . $html . '</div>';
        $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NODEFDTD);
        $root = $dom->getElementById('phlash-root');
        if (!$root) {
            return trim(strip_tags($html));
        }
        $md = $this->domToMarkdown($root, $copied);
        $md = preg_replace("/[ \t]+\n/", "\n", $md) ?? $md;
        $md = preg_replace("/\n{3,}/", "\n\n", $md) ?? $md;
        $md = trim($md);
        return $this->rewriteRemoteImages($md, $copied);
    }

    private function domToMarkdown(DOMNode $node, array &$copied): string
    {
        if ($node instanceof DOMText) {
            return str_replace("\xc2\xa0", ' ', $node->textContent);
        }
        if (!$node instanceof DOMElement) {
            return '';
        }
        $tag = strtolower($node->tagName);
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= $this->domToMarkdown($child, $copied);
        }
        $inner = str_replace("\xc2\xa0", ' ', $inner);

        if ($tag === 'script' || $tag === 'style') {
            return '';
        }
        if ($tag === 'br') {
            return "\n";
        }
        if ($tag === 'img') {
            $src = $node->getAttribute('src');
            if ($src === '') {
                return '';
            }
            $local = $this->copyMedia($src);
            if ($local !== $src) {
                $copied[$local] = true;
            }
            $alt = trim($node->getAttribute('alt'));
            return "\n\n![" . $alt . "](" . $local . ")\n\n";
        }
        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));
            $text = trim($inner);
            if ($href === '') {
                return $inner;
            }
            $local = $this->copyMedia($href);
            if ($local !== $href) {
                $copied[$local] = true;
                $href = $local;
            }
            if ($text === '') {
                return $href;
            }
            if (strpos($inner, '![') !== false) {
                return trim($inner);
            }
            return '[' . $text . '](' . $href . ')';
        }
        if (in_array($tag, ['h1', 'h2', 'h3'], true)) {
            $level = $tag === 'h1' ? '##' : ($tag === 'h3' ? '###' : '##');
            return "\n\n" . $level . ' ' . trim($inner) . "\n\n";
        }
        if ($tag === 'strong' || $tag === 'b') {
            $t = trim($inner);
            return $t === '' ? '' : '**' . $t . '**';
        }
        if ($tag === 'em' || $tag === 'i') {
            $t = trim($inner);
            return $t === '' ? '' : '*' . $t . '*';
        }
        if ($tag === 'code') {
            return '`' . trim($inner, "\n") . '`';
        }
        if ($tag === 'blockquote') {
            $lines = preg_split('/\n/', trim($inner)) ?: [];
            return "\n\n" . implode("\n", array_map(fn ($l) => '> ' . $l, $lines)) . "\n\n";
        }
        if ($tag === 'li') {
            return "\n- " . trim($inner);
        }
        if ($tag === 'ul' || $tag === 'ol') {
            return "\n" . trim($inner) . "\n";
        }
        if ($tag === 'iframe') {
            $src = $node->getAttribute('src');
            return $src !== '' ? "\n\n" . $src . "\n\n" : '';
        }
        if ($tag === 'p' || $tag === 'div' || $tag === 'tr') {
            return "\n\n" . trim($inner) . "\n\n";
        }
        if ($tag === 'pre') {
            return "\n\n```\n" . trim($inner) . "\n```\n\n";
        }
        return $inner;
    }

    private function importComments(SimpleXMLElement $xml, array $postMap): int
    {
        $n = 0;
        $commentIds = [];
        foreach ($xml->entry as $e) {
            $ns = $e->children('http://schemas.google.com/blogger/2018');
            if ((string) $ns->type !== 'COMMENT') {
                continue;
            }
            $status = (string) $ns->status;
            if ($status === 'SPAM_COMMENT' && !$this->opts['include_spam']) {
                continue;
            }
            $parent = (string) $ns->parent;
            $storyId = $postMap[$parent] ?? 0;
            if (!$storyId) {
                continue;
            }
            $body = trim(html_entity_decode(strip_tags((string) $e->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($body === '') {
                continue;
            }
            $name = mb_substr(trim((string) $e->author->name), 0, 40);
            $when = $this->toLocalSql((string) $e->published);
            $inReply = (string) $ns->inReplyTo;
            $parentComment = ($inReply !== '' && isset($commentIds[$inReply])) ? $commentIds[$inReply] : null;
            $id = \Phlash\Database::insert(
                'INSERT INTO comments (story_id, user_id, parent_id, author_name, body, score, ip_hash, status, created_at)
                 VALUES (?, NULL, ?, ?, ?, 1, ?, ?, ?)',
                [
                    $storyId,
                    $parentComment,
                    $name === 'Anonymous' ? '' : $name,
                    mb_substr($body, 0, 8000),
                    'import',
                    $status === 'LIVE' ? 'visible' : 'hidden',
                    $when,
                ]
            );
            $commentIds[(string) $e->id] = $id;
            if ($status === 'LIVE') {
                \Phlash\Database::query('UPDATE stories SET comment_count = comment_count + 1 WHERE id = ?', [$storyId]);
            }
            $n++;
        }
        return $n;
    }

    private function importExternalComments(array $postMap): int
    {
        $n = 0;
        foreach (glob($this->takeout . '/Comments/*/feed.atom') ?: [] as $feed) {
            $xml = @simplexml_load_file($feed);
            if (!$xml) {
                continue;
            }
            $n += $this->importComments($xml, $postMap);
        }
        return $n;
    }

    private function log(string $type, string $msg): void
    {
        $this->log[] = ['type' => $type, 'msg' => $msg];
    }
}
