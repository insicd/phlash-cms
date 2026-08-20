<?php
namespace Phlash\Controllers;

use Phlash\Auth;
use Phlash\Csrf;
use Phlash\Database;
use Phlash\View;

class StoryController
{
    public static function show(array $p): void
    {
        $story = Database::one(
            "SELECT s.*, u.username, u.karma AS author_karma, t.name AS topic_name, t.slug AS topic_slug, t.icon AS topic_icon
             FROM stories s
             JOIN users u ON u.id = s.user_id
             JOIN topics t ON t.id = s.topic_id
             WHERE s.slug = ?",
            [$p['slug']]
        );
        if (!$story) {
            View::notFound('Storia non trovata.');
        }
        $canSee = $story['status'] === 'published'
            || Auth::isAdmin()
            || (Auth::id() && (int) Auth::id() === (int) $story['user_id']);
        if (!$canSee) {
            View::notFound('Storia non trovata.');
        }

        $viewed = $_SESSION['viewed_stories'] ?? [];
        if ($story['status'] === 'published' && empty($viewed[(int) $story['id']])) {
            Database::query('UPDATE stories SET views = views + 1 WHERE id = ?', [(int) $story['id']]);
            $story['views'] = (int) $story['views'] + 1;
            $viewed[(int) $story['id']] = 1;
            $_SESSION['viewed_stories'] = $viewed;
        }

        $comments = Database::all(
            "SELECT c.*, u.username, u.karma AS user_karma
             FROM comments c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.story_id = ? AND c.status = 'visible'
             ORDER BY c.created_at ASC",
            [(int) $story['id']]
        );
        $tree = comment_tree($comments);
        $tags = tags_for_story((int) $story['id']);
        $threshold = (int) ($_GET['soglia'] ?? Database::setting('comment_threshold', '1'));
        $sort = $_GET['ordina'] ?? 'thread';

        if ($sort === 'best') {
            $tree = self::sortTree($tree, 'score');
        } elseif ($sort === 'new') {
            $tree = self::sortTree($tree, 'created_at');
        }

        $captcha = self::freshCaptcha();
        View::render('story', [
            'title' => $story['title'],
            'story' => $story,
            'tags' => $tags,
            'tree' => $tree,
            'comment_count' => count($comments),
            'threshold' => $threshold,
            'sort' => $sort,
            'captcha' => $captcha,
            'allow_anon' => Database::setting('allow_anon_comments', '1') === '1',
        ]);
    }

    public static function vote(): void
    {
        Csrf::check();
        $user = Auth::requireUser();
        $id = (int) ($_POST['id'] ?? 0);
        $story = Database::one('SELECT * FROM stories WHERE id = ?', [$id]);
        if (!$story) {
            flash('err', 'Storia non trovata.');
            redirect('');
        }
        if ((int) $story['user_id'] === (int) $user['id']) {
            flash('err', 'Non puoi votare una storia che hai scritto tu.');
            redirect(self::backPath($story));
        }
        $exists = Database::one(
            "SELECT id FROM votes WHERE user_id = ? AND target_type = 'story' AND target_id = ?",
            [(int) $user['id'], $id]
        );
        if ($exists) {
            flash('err', 'Hai già votato questa storia.');
            redirect(self::backPath($story));
        }
        Database::query(
            "INSERT INTO votes (user_id, target_type, target_id, value, created_at) VALUES (?, 'story', ?, 1, NOW())",
            [(int) $user['id'], $id]
        );
        Database::query('UPDATE stories SET score = score + 1 WHERE id = ?', [$id]);
        bump_karma((int) $story['user_id'], 1);

        $threshold = (int) Database::setting('promote_threshold', '5');
        $fresh = Database::one('SELECT * FROM stories WHERE id = ?', [$id]);
        if ($fresh && $fresh['status'] === 'pending' && (int) $fresh['score'] >= $threshold) {
            Database::query(
                "UPDATE stories SET status = 'published', published_at = NOW() WHERE id = ?",
                [$id]
            );
            bump_karma((int) $story['user_id'], 5);
            flash('ok', 'Voto registrato. La storia ha raggiunto la soglia ed è in homepage.');
        } else {
            flash('ok', 'Voto registrato.');
        }
        redirect(self::backPath($story));
    }

    private static function backPath(array $story): string
    {
        if ($story['status'] === 'published') {
            return 'storia/' . $story['slug'];
        }
        return 'upcoming';
    }

    private static function sortTree(array $tree, string $field): array
    {
        usort($tree, function ($a, $b) use ($field) {
            if ($field === 'score') {
                return (int) $b['score'] <=> (int) $a['score'];
            }
            return strcmp($b['created_at'], $a['created_at']);
        });
        foreach ($tree as &$n) {
            if (!empty($n['children'])) {
                $n['children'] = self::sortTree($n['children'], $field);
            }
        }
        unset($n);
        return $tree;
    }

    public static function freshCaptcha(): array
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);
        $_SESSION['captcha'] = $a + $b;
        return ['a' => $a, 'b' => $b];
    }
}
