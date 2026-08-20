<?php
namespace Phlash\Controllers;

use Phlash\Auth;
use Phlash\Database;
use Phlash\View;

class UserController
{
    public static function profile(array $p): void
    {
        $u = Database::one('SELECT * FROM users WHERE username = ?', [$p['username']]);
        if (!$u || $u['status'] !== 'active') {
            View::notFound('Utente non trovato.');
        }
        $stories = Database::all(
            "SELECT s.*, t.name AS topic_name, t.slug AS topic_slug, t.icon AS topic_icon
             FROM stories s JOIN topics t ON t.id = s.topic_id
             WHERE s.user_id = ? AND s.status = 'published'
             ORDER BY s.published_at DESC LIMIT 20",
            [(int) $u['id']]
        );
        $comments = Database::all(
            "SELECT c.*, s.title, s.slug
             FROM comments c JOIN stories s ON s.id = c.story_id
             WHERE c.user_id = ? AND c.status = 'visible'
             ORDER BY c.created_at DESC LIMIT 20",
            [(int) $u['id']]
        );
        View::render('profile', [
            'title' => $u['username'],
            'profile' => $u,
            'stories' => $stories,
            'comments' => $comments,
        ]);
    }
}
