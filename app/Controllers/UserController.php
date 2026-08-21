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
        $own = Auth::id() && (int) Auth::id() === (int) $u['id'];
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
            'api_token_once' => ($own && !empty($_SESSION['api_token_once'])) ? $_SESSION['api_token_once'] : null,
            'has_api_token' => $own && !empty($u['api_token_hash']),
            'own_profile' => $own,
        ]);
        if ($own) {
            unset($_SESSION['api_token_once']);
        }
    }

    public static function apiToken(): void
    {
        $user = Auth::requireUser();
        $act = (string) ($_POST['act'] ?? '');
        if ($act === 'generate') {
            $_SESSION['api_token_once'] = Auth::issueApiToken((int) $user['id']);
            flash('ok', 'Nuovo token API creato. Copialo ora: non verrà più mostrato.');
        } elseif ($act === 'revoke') {
            Auth::revokeApiToken((int) $user['id']);
            unset($_SESSION['api_token_once']);
            flash('ok', 'Token API revocato.');
        }
        redirect('utente/' . $user['username']);
    }
}
