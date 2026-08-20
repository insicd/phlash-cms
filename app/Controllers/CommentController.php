<?php
namespace Phlash\Controllers;

use Phlash\Auth;
use Phlash\Csrf;
use Phlash\Database;
use Phlash\View;

class CommentController
{
    public static function store(): void
    {
        Csrf::check();
        $storyId = (int) ($_POST['story_id'] ?? 0);
        $parentId = (int) ($_POST['parent_id'] ?? 0);
        $body = trim((string) ($_POST['body'] ?? ''));
        $anon = !empty($_POST['anonymous']);
        $name = trim((string) ($_POST['author_name'] ?? ''));
        $story = Database::one('SELECT * FROM stories WHERE id = ?', [$storyId]);
        if (!$story || ($story['status'] !== 'published' && !Auth::isAdmin())) {
            flash('err', 'Non puoi commentare questa storia.');
            redirect('');
        }
        if (mb_strlen($body) < 2 || mb_strlen($body) > 8000) {
            flash('err', 'Il commento deve avere tra 2 e 8000 caratteri.');
            redirect('storia/' . $story['slug']);
        }

        $allowAnon = Database::setting('allow_anon_comments', '1') === '1';
        $user = Auth::user();

        if (!$user && !$allowAnon) {
            flash('err', 'Devi accedere per commentare.');
            redirect('login');
        }

        if (!$user || $anon) {
            if (!$allowAnon) {
                flash('err', 'I commenti anonimi sono disattivati.');
                redirect('storia/' . $story['slug']);
            }
            if (!$user) {
                $answer = (int) ($_POST['captcha'] ?? -1);
                if ($answer !== (int) ($_SESSION['captcha'] ?? -2)) {
                    flash('err', 'Controllo anti-spam errato.');
                    redirect('storia/' . $story['slug']);
                }
                $last = $_SESSION['last_anon_comment'] ?? 0;
                if (time() - (int) $last < 30) {
                    flash('err', 'Aspetta qualche secondo prima di un altro commento.');
                    redirect('storia/' . $story['slug']);
                }
                $_SESSION['last_anon_comment'] = time();
            }
            $userId = null;
            $authorName = $name !== '' ? mb_substr($name, 0, 40) : '';
        } else {
            $userId = (int) $user['id'];
            $authorName = '';
        }

        if ($parentId) {
            $parent = Database::one(
                'SELECT id FROM comments WHERE id = ? AND story_id = ? AND status = ?',
                [$parentId, $storyId, 'visible']
            );
            if (!$parent) {
                $parentId = 0;
            }
        }

        Database::insert(
            'INSERT INTO comments (story_id, user_id, parent_id, author_name, body, score, ip_hash, status, created_at)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?, NOW())',
            [
                $storyId,
                $userId,
                $parentId ?: null,
                $authorName,
                $body,
                ip_hash(),
                'visible',
            ]
        );
        Database::query('UPDATE stories SET comment_count = comment_count + 1 WHERE id = ?', [$storyId]);
        flash('ok', 'Commento pubblicato.');
        redirect('storia/' . $story['slug'] . '#comments');
    }

    public static function vote(): void
    {
        Csrf::check();
        $user = Auth::requireUser();
        $id = (int) ($_POST['id'] ?? 0);
        $dir = (int) ($_POST['value'] ?? 0);
        if ($dir !== 1 && $dir !== -1) {
            flash('err', 'Voto non valido.');
            redirect('');
        }
        $comment = Database::one(
            "SELECT c.*, s.slug FROM comments c JOIN stories s ON s.id = c.story_id WHERE c.id = ?",
            [$id]
        );
        if (!$comment) {
            flash('err', 'Commento non trovato.');
            redirect('');
        }
        if ($comment['user_id'] && (int) $comment['user_id'] === (int) $user['id']) {
            flash('err', 'Non puoi votare i tuoi commenti.');
            redirect('storia/' . $comment['slug'] . '#c' . $id);
        }
        $exists = Database::one(
            "SELECT id, value FROM votes WHERE user_id = ? AND target_type = 'comment' AND target_id = ?",
            [(int) $user['id'], $id]
        );
        if ($exists) {
            flash('err', 'Hai già votato questo commento.');
            redirect('storia/' . $comment['slug'] . '#c' . $id);
        }
        Database::query(
            "INSERT INTO votes (user_id, target_type, target_id, value, created_at) VALUES (?, 'comment', ?, ?, NOW())",
            [(int) $user['id'], $id, $dir]
        );
        Database::query('UPDATE comments SET score = score + ? WHERE id = ?', [$dir, $id]);
        if ($comment['user_id']) {
            bump_karma((int) $comment['user_id'], $dir);
        }
        redirect('storia/' . $comment['slug'] . '#c' . $id);
    }
}
