<?php

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function phlash_base_url(): string
{
    if (PHLASH_BASE_URL !== '') {
        return rtrim(PHLASH_BASE_URL, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(str_replace('\\', '/', $script), '/');
    if ($base === '/' || $base === '.') {
        $base = '';
    }
    return $scheme . '://' . $host . $base;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = phlash_base_url();
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path, int $code = 302): void
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path, true, $code);
    } else {
        header('Location: ' . url($path), true, $code);
    }
    exit;
}

function flash(?string $type = null, ?string $msg = null): ?array
{
    if ($type !== null && $msg !== null) {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $map = [
        'à'=>'a','á'=>'a','â'=>'a','ä'=>'a','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ò'=>'o','ó'=>'o','ô'=>'o','ö'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n','ß'=>'ss',
        '’'=>'','\''=>'','"'=>'','.'=>'','?'=>'','!'=>'','(',')'=>'',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'n-' . substr(sha1($text . microtime()), 0, 8);
}

function unique_slug(string $table, string $slug, ?int $ignoreId = null): string
{
    $base = $slug;
    $n = 2;
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$slug];
        if ($ignoreId) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        if (!\Phlash\Database::one($sql, $params)) {
            return $slug;
        }
        $slug = $base . '-' . $n;
        $n++;
    }
}

function excerpt(string $markdown, int $len = 420): string
{
    $plain = trim(preg_replace('/\s+/', ' ', \Phlash\Markdown::plain($markdown)));
    if (mb_strlen($plain) <= $len) {
        return $plain;
    }
    return rtrim(mb_substr($plain, 0, $len), " \t.,;:") . '…';
}

function phlash_timezone(): DateTimeZone
{
    try {
        return new DateTimeZone(\Phlash\Database::timezoneName());
    } catch (Throwable $e) {
        return new DateTimeZone('Europe/Rome');
    }
}

function italian_datetime(string $dt): string
{
    $tz = phlash_timezone();
    try {
        $when = new DateTime($dt, $tz);
    } catch (Throwable $e) {
        $when = new DateTime('now', $tz);
    }
    $giorni = ['domenica','lunedì','martedì','mercoledì','giovedì','venerdì','sabato'];
    $mesi = [1=>'gennaio',2=>'febbraio',3=>'marzo',4=>'aprile',5=>'maggio',6=>'giugno',
        7=>'luglio',8=>'agosto',9=>'settembre',10=>'ottobre',11=>'novembre',12=>'dicembre'];
    return $giorni[(int) $when->format('w')] . ' ' . $when->format('j') . ' ' . $mesi[(int) $when->format('n')]
        . ' ' . $when->format('Y') . ' @' . $when->format('H:i');
}

function source_host(?string $url): string
{
    if (!$url) {
        return '';
    }
    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        return '';
    }
    return (string) preg_replace('/^www\./i', '', $host);
}

function logo_parts(string $name): array
{
    $name = trim($name);
    if ($name === '') {
        $name = 'Phlash';
    }
    if (preg_match('/^(\S+)\s+(.+)$/u', $name, $m)) {
        return ['a' => $m[1], 'b' => ' ' . $m[2]];
    }
    $len = mb_strlen($name);
    $mid = max(1, (int) floor($len / 2));
    return [
        'a' => mb_substr($name, 0, $mid),
        'b' => mb_substr($name, $mid),
    ];
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ip_hash(): string
{
    $salt = \Phlash\Database::setting('ip_salt', 'phlash');
    return hash('sha256', client_ip() . '|' . $salt);
}

function pagination(int $total, int $page, int $per, string $basePath): array
{
    $pages = max(1, (int) ceil($total / $per));
    $page = max(1, min($page, $pages));
    return [
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'per' => $per,
        'offset' => ($page - 1) * $per,
        'base' => $basePath,
    ];
}

function topic_abbrev(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];
    if (count($words) >= 2) {
        return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
    return mb_strtoupper(mb_substr($name, 0, 2));
}

function phlash_fa_sanitize(string $name, bool $strict = false): string
{
    $name = strtolower(trim($name));
    $name = preg_replace('/^(fa-solid|fa-regular|fa-brands)\s+/', '', $name) ?? $name;
    $name = preg_replace('/^fa-/', '', $name) ?? $name;
    if ($name === '' || strlen($name) > 48 || !preg_match('/^[a-z0-9-]+$/', $name)) {
        return '';
    }
    if ($strict) {
        require_once PHLASH_APP . '/fa-icons.php';
        static $set = null;
        if ($set === null) {
            $set = array_flip(phlash_fa_icons());
        }
        if (!isset($set[$name])) {
            return '';
        }
    }
    return $name;
}

function topic_icon_html(?string $icon, string $name = '', string $extraClass = ''): string
{
    $icon = phlash_fa_sanitize((string) $icon);
    if ($icon !== '') {
        $cls = 'fa-solid fa-' . $icon;
        if ($extraClass !== '') {
            $cls .= ' ' . $extraClass;
        }
        return '<i class="' . h($cls) . '" aria-hidden="true"></i>';
    }
    if ($name !== '') {
        return '<span class="topic-abbrev">' . h(topic_abbrev($name)) . '</span>';
    }
    return '';
}

function comment_tree(array $comments): array
{
    $byParent = [];
    foreach ($comments as $c) {
        $pid = !empty($c['parent_id']) ? (int) $c['parent_id'] : 0;
        $byParent[$pid][] = $c;
    }
    $build = function (int $pid) use (&$build, $byParent): array {
        $out = [];
        foreach ($byParent[$pid] ?? [] as $row) {
            $row['children'] = $build((int) $row['id']);
            $out[] = $row;
        }
        return $out;
    };
    return $build(0);
}

function story_tags_sync(int $storyId, string $raw): void
{
    \Phlash\Database::query('DELETE FROM story_tags WHERE story_id = ?', [$storyId]);
    $parts = preg_split('/[,;]+/', $raw) ?: [];
    $seen = [];
    foreach ($parts as $part) {
        $name = trim($part);
        if ($name === '' || mb_strlen($name) > 40) {
            continue;
        }
        $key = mb_strtolower($name);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $slug = slugify($name);
        $tag = \Phlash\Database::one('SELECT id FROM tags WHERE slug = ?', [$slug]);
        if (!$tag) {
            $id = \Phlash\Database::insert(
                'INSERT INTO tags (name, slug) VALUES (?, ?)',
                [$name, unique_slug('tags', $slug)]
            );
        } else {
            $id = (int) $tag['id'];
        }
        \Phlash\Database::query('INSERT IGNORE INTO story_tags (story_id, tag_id) VALUES (?, ?)', [$storyId, $id]);
    }
}

function tags_for_story(int $storyId): array
{
    return \Phlash\Database::all(
        'SELECT t.* FROM tags t INNER JOIN story_tags st ON st.tag_id = t.id WHERE st.story_id = ? ORDER BY t.name',
        [$storyId]
    );
}

function bump_karma(int $userId, int $delta): void
{
    \Phlash\Database::query('UPDATE users SET karma = karma + ? WHERE id = ?', [$delta, $userId]);
}

function request_header(string $name): string
{
    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    if (!empty($_SERVER[$serverKey]) && is_string($_SERVER[$serverKey])) {
        return $_SERVER[$serverKey];
    }
    if (strcasecmp($name, 'Authorization') === 0) {
        foreach (['REDIRECT_HTTP_AUTHORIZATION', 'HTTP_AUTHORIZATION'] as $k) {
            if (!empty($_SERVER[$k]) && is_string($_SERVER[$k])) {
                return $_SERVER[$k];
            }
        }
    }
    return '';
}

function render_comment_node(array $c, array $ctx): void
{
    $user = $ctx['user'] ?? null;
    $story = $ctx['story'] ?? [];
    $threshold = $ctx['threshold'] ?? 1;
    $allow_anon = $ctx['allow_anon'] ?? true;
    $captcha = $ctx['captcha'] ?? ['a' => 2, 'b' => 2];
    include PHLASH_ROOT . '/templates/partials/comment.php';
}
