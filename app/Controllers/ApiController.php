<?php
namespace Phlash\Controllers;

use Phlash\Auth;
use Phlash\Database;

class ApiController
{
    public static function index(): void
    {
        self::ok([
            'name' => 'Phlash API',
            'version' => '1',
            'auth' => 'Authorization: Bearer <token>  oppure  X-Phlash-Token: <token>',
            'endpoints' => [
                'GET /api/v1/me',
                'GET /api/v1/topics',
                'POST /api/v1/stories',
                'GET /api/v1/stories/{id}',
            ],
            'note' => 'Le storie create via API restano sempre in arrivo (pending).',
        ]);
    }

    public static function me(): void
    {
        $user = Auth::requireApiUser();
        self::ok([
            'user' => [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
            ],
        ]);
    }

    public static function topics(): void
    {
        Auth::requireApiUser();
        $rows = Database::all('SELECT id, name, slug, description FROM topics ORDER BY sort_order, name');
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => $row['description'],
            ];
        }
        self::ok(['topics' => $out]);
    }

    public static function createStory(): void
    {
        $user = Auth::requireApiUser();
        $in = self::input();

        $hourAgo = date('Y-m-d H:i:s', time() - 3600);
        $n = (int) (Database::one(
            'SELECT COUNT(*) AS c FROM stories WHERE user_id = ? AND created_at >= ?',
            [(int) $user['id'], $hourAgo]
        )['c'] ?? 0);
        if ($n >= 20) {
            self::fail('Limite raggiunto: al massimo 20 storie via API in un’ora.', 429);
        }

        $title = trim((string) ($in['title'] ?? ''));
        $body = trim((string) ($in['body'] ?? ''));
        $dept = trim((string) ($in['dept'] ?? ''));
        $source = trim((string) ($in['source_url'] ?? ''));
        $tags = $in['tags'] ?? '';
        if (is_array($tags)) {
            $tags = implode(', ', array_map('strval', $tags));
        }
        $tags = trim((string) $tags);

        if (mb_strlen($title) < 8 || mb_strlen($title) > 200) {
            self::fail('Il titolo deve avere tra 8 e 200 caratteri.', 422);
        }
        if (mb_strlen($body) < 80) {
            self::fail('Il testo è troppo corto: almeno 80 caratteri in Markdown.', 422);
        }
        if (mb_strlen($body) > 80000) {
            self::fail('Testo troppo lungo (max 80000 caratteri).', 422);
        }
        if ($source !== '' && !preg_match('#^https?://#i', $source)) {
            self::fail('source_url deve iniziare con http:// o https://.', 422);
        }

        $topic = self::resolveTopic($in);
        if (!$topic) {
            self::fail('Sezione non valida: indica topic (slug) oppure topic_id.', 422);
        }
        $dept = mb_substr($dept, 0, 80);
        if ($dept === '') {
            $dept = slugify((string) $topic['name']);
        }

        $slug = unique_slug('stories', slugify($title));
        $id = Database::insert(
            'INSERT INTO stories (user_id, topic_id, title, slug, dept, body, source_url, status, score, comment_count, views, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 0, NOW())',
            [
                (int) $user['id'],
                (int) $topic['id'],
                $title,
                $slug,
                $dept,
                $body,
                $source !== '' ? $source : null,
                'pending',
            ]
        );
        story_tags_sync($id, $tags);

        self::ok([
            'story' => [
                'id' => $id,
                'title' => $title,
                'slug' => $slug,
                'status' => 'pending',
                'topic' => $topic['slug'],
                'url' => url('storia/' . $slug),
                'upcoming_url' => url('upcoming'),
            ],
        ], 201);
    }

    public static function showStory(array $p): void
    {
        $user = Auth::requireApiUser();
        $id = (int) ($p['id'] ?? 0);
        $story = Database::one(
            'SELECT s.id, s.title, s.slug, s.status, s.score, s.created_at, t.slug AS topic
             FROM stories s JOIN topics t ON t.id = s.topic_id WHERE s.id = ?',
            [$id]
        );
        if (!$story) {
            self::fail('Storia non trovata.', 404);
        }
        $full = Database::one('SELECT user_id FROM stories WHERE id = ?', [$id]);
        if ((int) $full['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin') {
            self::fail('Storia non trovata.', 404);
        }
        self::ok([
            'story' => [
                'id' => (int) $story['id'],
                'title' => $story['title'],
                'slug' => $story['slug'],
                'status' => $story['status'],
                'score' => (int) $story['score'],
                'topic' => $story['topic'],
                'created_at' => $story['created_at'],
                'url' => url('storia/' . $story['slug']),
            ],
        ]);
    }

    private static function resolveTopic(array $in): ?array
    {
        $id = (int) ($in['topic_id'] ?? 0);
        $slug = trim((string) ($in['topic'] ?? $in['topic_slug'] ?? ''));
        if ($id > 0) {
            $row = Database::one('SELECT id, name, slug FROM topics WHERE id = ?', [$id]);
            return $row ?: null;
        }
        if ($slug !== '') {
            $row = Database::one('SELECT id, name, slug FROM topics WHERE slug = ?', [$slug]);
            return $row ?: null;
        }
        return null;
    }

    private static function input(): array
    {
        $raw = (string) file_get_contents('php://input');
        $ctype = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if ($raw !== '' && strpos($ctype, 'application/json') !== false) {
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }
        if ($raw !== '' && ($_POST === [] || $_POST === null)) {
            $data = json_decode($raw, true);
            if (is_array($data)) {
                return $data;
            }
        }
        return is_array($_POST) ? $_POST : [];
    }

    private static function ok(array $data, int $code = 200): void
    {
        self::send(array_merge(['ok' => true], $data), $code);
    }

    private static function fail(string $error, int $code): void
    {
        self::send(['ok' => false, 'error' => $error], $code);
    }

    private static function send(array $data, int $code): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
