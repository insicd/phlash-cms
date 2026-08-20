<?php
namespace Phlash;

class View
{
    public static function render(string $template, array $data = [], bool $layout = true): void
    {
        if ($layout) {
            $data['user'] = $data['user'] ?? Auth::user();
            $data['flash'] = $data['flash'] ?? flash();
            $data['topics'] = $data['topics'] ?? Database::all('SELECT * FROM topics ORDER BY sort_order, name');
            $data['settings'] = $data['settings'] ?? [
                'site_name' => Database::setting('site_name', 'Phlash'),
                'site_tagline' => Database::setting('site_tagline', 'Notizie per nerd, cose che contano.'),
                'custom_css' => Database::setting('custom_css', ''),
            ];
            $data['poll'] = $data['poll'] ?? \Phlash\Controllers\PollController::active();
            $data['recent_comments'] = $data['recent_comments'] ?? Database::all(
                "SELECT c.id, c.body, c.author_name, c.created_at, u.username, s.title, s.slug
                 FROM comments c
                 JOIN stories s ON s.id = c.story_id
                 LEFT JOIN users u ON u.id = c.user_id
                 WHERE c.status = 'visible' AND s.status = 'published'
                 ORDER BY c.created_at DESC LIMIT 6"
            );
            $data['pending_count'] = $data['pending_count'] ?? (int) Database::one(
                "SELECT COUNT(*) c FROM stories WHERE status='pending'"
            )['c'];
            try {
                Stats::hit($template, $data);
            } catch (\Throwable $e) {
                // le stats non devono mai rompere la pagina
            }
        }
        extract($data, EXTR_SKIP);
        $content = self::capture($template, $data);
        if ($layout) {
            $sidebar = self::capture('partials/sidebar', $data);
            include PHLASH_ROOT . '/templates/layout.php';
            return;
        }
        echo $content;
    }

    public static function partial(string $template, array $data = []): void
    {
        echo self::capture($template, $data);
    }

    public static function capture(string $template, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        $file = PHLASH_ROOT . '/templates/' . $template . '.php';
        if (!is_file($file)) {
            return '<!-- template mancante: ' . h($template) . ' -->';
        }
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    public static function notFound(string $msg = 'Pagina non trovata.'): void
    {
        http_response_code(404);
        self::render('error', ['title' => '404', 'message' => $msg]);
        exit;
    }
}
