<?php
namespace Phlash\Controllers;

use Phlash\Auth;
use Phlash\Database;
use Phlash\View;

class HomeController
{
    public static function index(): void
    {
        $per = (int) Database::setting('stories_per_page', '10');
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $total = (int) Database::one("SELECT COUNT(*) AS c FROM stories WHERE status = 'published'")['c'];
        $pg = pagination($total, $page, $per, '');
        $stories = self::fetchStories("s.status = 'published'", $pg['offset'], $per, 's.published_at DESC');
        View::render('home', [
            'title' => Database::setting('site_name', 'Phlash'),
            'heading' => 'Ultime storie',
            'stories' => $stories,
            'pg' => $pg,
            'mode' => 'home',
        ]);
    }

    public static function upcoming(): void
    {
        $per = (int) Database::setting('stories_per_page', '10');
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $total = (int) Database::one("SELECT COUNT(*) AS c FROM stories WHERE status = 'pending'")['c'];
        $pg = pagination($total, $page, $per, 'upcoming');
        $stories = self::fetchStories("s.status = 'pending'", $pg['offset'], $per, 's.score DESC, s.created_at DESC');
        $threshold = (int) Database::setting('promote_threshold', '5');
        View::render('home', [
            'title' => 'In arrivo',
            'heading' => 'In arrivo',
            'intro' => 'Storie proposte dalla comunità. Con ' . $threshold . ' voti vengono pubblicate in homepage. Per votare serve un account.',
            'stories' => $stories,
            'pg' => $pg,
            'mode' => 'upcoming',
        ]);
    }

    public static function topic(array $p): void
    {
        $topic = Database::one('SELECT * FROM topics WHERE slug = ?', [$p['slug']]);
        if (!$topic) {
            View::notFound('Sezione non trovata.');
        }
        $per = (int) Database::setting('stories_per_page', '10');
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $total = (int) Database::one(
            "SELECT COUNT(*) AS c FROM stories WHERE status = 'published' AND topic_id = ?",
            [(int) $topic['id']]
        )['c'];
        $pg = pagination($total, $page, $per, 'sezione/' . $topic['slug']);
        $stories = self::fetchStories(
            "s.status = 'published' AND s.topic_id = " . (int) $topic['id'],
            $pg['offset'],
            $per,
            's.published_at DESC'
        );
        View::render('home', [
            'title' => $topic['name'],
            'heading' => $topic['name'],
            'intro' => $topic['description'],
            'stories' => $stories,
            'pg' => $pg,
            'mode' => 'topic',
            'current_topic' => $topic,
        ]);
    }

    public static function search(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $stories = [];
        $pg = pagination(0, 1, 10, 'cerca');
        if (mb_strlen($q) >= 2) {
            $like = '%' . $q . '%';
            $per = (int) Database::setting('stories_per_page', '10');
            $page = max(1, (int) ($_GET['p'] ?? 1));
            $total = (int) Database::one(
                "SELECT COUNT(*) AS c FROM stories s WHERE s.status = 'published' AND (s.title LIKE ? OR s.body LIKE ? OR s.dept LIKE ?)",
                [$like, $like, $like]
            )['c'];
            $pg = pagination($total, $page, $per, 'cerca');
            $pg['base'] = 'cerca?q=' . rawurlencode($q);
            $stories = Database::all(
                 "SELECT s.*, u.username, t.name AS topic_name, t.slug AS topic_slug, t.icon AS topic_icon
                 FROM stories s
                 JOIN users u ON u.id = s.user_id
                 JOIN topics t ON t.id = s.topic_id
                 WHERE s.status = 'published' AND (s.title LIKE ? OR s.body LIKE ? OR s.dept LIKE ?)
                 ORDER BY s.published_at DESC
                 LIMIT {$per} OFFSET {$pg['offset']}",
                [$like, $like, $like]
            );
        }
        View::render('search', [
            'title' => 'Cerca',
            'q' => $q,
            'stories' => $stories,
            'pg' => $pg,
        ]);
    }

    public static function rss(): void
    {
        $site = Database::setting('site_name', 'Phlash');
        $stories = self::fetchStories("s.status = 'published'", 0, 25, 's.published_at DESC');
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        View::render('rss', ['site' => $site, 'stories' => $stories], false);
    }

    public static function fetchStories(string $where, int $offset, int $limit, string $order): array
    {
        $offset = max(0, $offset);
        $limit = max(1, min(50, $limit));
        return Database::all(
            "SELECT s.*, u.username, t.name AS topic_name, t.slug AS topic_slug, t.icon AS topic_icon
             FROM stories s
             JOIN users u ON u.id = s.user_id
             JOIN topics t ON t.id = s.topic_id
             WHERE {$where}
             ORDER BY {$order}
             LIMIT {$limit} OFFSET {$offset}"
        );
    }
}
