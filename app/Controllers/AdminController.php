<?php
namespace Phlash\Controllers;

use Phlash\Auth;
use Phlash\Csrf;
use Phlash\Database;
use Phlash\View;

class AdminController
{
    public static function dashboard(): void
    {
        Auth::requireAdmin();
        $stats = [
            'users' => (int) Database::one('SELECT COUNT(*) c FROM users')['c'],
            'stories_pub' => (int) Database::one("SELECT COUNT(*) c FROM stories WHERE status='published'")['c'],
            'stories_pend' => (int) Database::one("SELECT COUNT(*) c FROM stories WHERE status='pending'")['c'],
            'comments' => (int) Database::one("SELECT COUNT(*) c FROM comments WHERE status='visible'")['c'],
        ];
        $pending = Database::all(
            "SELECT s.*, u.username FROM stories s JOIN users u ON u.id = s.user_id
             WHERE s.status = 'pending' ORDER BY s.created_at DESC LIMIT 15"
        );
        View::render('admin/dashboard', [
            'title' => 'Admin',
            'stats' => $stats,
            'pending' => $pending,
            'admin_nav' => true,
        ]);
    }

    public static function stories(): void
    {
        Auth::requireAdmin();
        $status = $_GET['status'] ?? 'pending';
        if (!in_array($status, ['pending', 'published', 'rejected'], true)) {
            $status = 'pending';
        }
        $rows = Database::all(
            "SELECT s.*, u.username, t.name AS topic_name
             FROM stories s JOIN users u ON u.id = s.user_id JOIN topics t ON t.id = s.topic_id
             WHERE s.status = ? ORDER BY s.created_at DESC LIMIT 80",
            [$status]
        );
        View::render('admin/stories', [
            'title' => 'Storie — Admin',
            'rows' => $rows,
            'status' => $status,
            'admin_nav' => true,
        ]);
    }

    public static function storyAction(): void
    {
        Csrf::check();
        Auth::requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $act = $_POST['act'] ?? '';
        $story = Database::one('SELECT * FROM stories WHERE id = ?', [$id]);
        if (!$story) {
            flash('err', 'Storia non trovata.');
            redirect('admin/storie');
        }
        if ($act === 'publish') {
            Database::query("UPDATE stories SET status='published', published_at = IFNULL(published_at, NOW()) WHERE id=?", [$id]);
            if ($story['status'] !== 'published') {
                bump_karma((int) $story['user_id'], 5);
            }
            flash('ok', 'Storia pubblicata.');
        } elseif ($act === 'reject') {
            Database::query("UPDATE stories SET status='rejected' WHERE id=?", [$id]);
            flash('ok', 'Storia rifiutata.');
        } elseif ($act === 'pending') {
            Database::query("UPDATE stories SET status='pending' WHERE id=?", [$id]);
            flash('ok', 'Storia rimessa in coda.');
        } elseif ($act === 'delete') {
            Database::query('DELETE FROM stories WHERE id=?', [$id]);
            flash('ok', 'Storia eliminata.');
        }
        redirect('admin/storie?status=' . rawurlencode($story['status']));
    }

    public static function comments(): void
    {
        Auth::requireAdmin();
        $rows = Database::all(
            "SELECT c.*, s.title, s.slug, u.username
             FROM comments c
             JOIN stories s ON s.id = c.story_id
             LEFT JOIN users u ON u.id = c.user_id
             ORDER BY c.created_at DESC LIMIT 100"
        );
        View::render('admin/comments', [
            'title' => 'Commenti — Admin',
            'rows' => $rows,
            'admin_nav' => true,
        ]);
    }

    public static function commentAction(): void
    {
        Csrf::check();
        Auth::requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $act = $_POST['act'] ?? '';
        $c = Database::one('SELECT * FROM comments WHERE id = ?', [$id]);
        if ($c && $act === 'hide') {
            Database::query("UPDATE comments SET status='hidden' WHERE id=?", [$id]);
            Database::query(
                "UPDATE stories SET comment_count = GREATEST(CAST(comment_count AS SIGNED)-1, 0) WHERE id=?",
                [(int) $c['story_id']]
            );
            flash('ok', 'Commento nascosto.');
        } elseif ($c && $act === 'show') {
            Database::query("UPDATE comments SET status='visible' WHERE id=?", [$id]);
            Database::query('UPDATE stories SET comment_count = comment_count + 1 WHERE id=?', [(int) $c['story_id']]);
            flash('ok', 'Commento ripristinato.');
        } elseif ($c && $act === 'delete') {
            Database::query('DELETE FROM comments WHERE id=?', [$id]);
            flash('ok', 'Commento eliminato.');
        }
        redirect('admin/commenti');
    }

    public static function users(): void
    {
        Auth::requireAdmin();
        $rows = Database::all('SELECT id, username, email, role, karma, status, created_at, last_login FROM users ORDER BY id DESC LIMIT 200');
        View::render('admin/users', [
            'title' => 'Utenti — Admin',
            'rows' => $rows,
            'admin_nav' => true,
        ]);
    }

    public static function userAction(): void
    {
        Csrf::check();
        $admin = Auth::requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $act = $_POST['act'] ?? '';
        if ($id === (int) $admin['id']) {
            flash('err', 'Non puoi modificare il tuo stesso ruolo/stato da qui.');
            redirect('admin/utenti');
        }
        if ($act === 'ban') {
            Database::query("UPDATE users SET status='banned' WHERE id=?", [$id]);
        } elseif ($act === 'unban') {
            Database::query("UPDATE users SET status='active' WHERE id=?", [$id]);
        } elseif ($act === 'make_admin') {
            Database::query("UPDATE users SET role='admin' WHERE id=?", [$id]);
        } elseif ($act === 'make_user') {
            Database::query("UPDATE users SET role='user' WHERE id=?", [$id]);
        }
        flash('ok', 'Utente aggiornato.');
        redirect('admin/utenti');
    }

    public static function topics(): void
    {
        Auth::requireAdmin();
        $rows = Database::all('SELECT * FROM topics ORDER BY sort_order, name');
        View::render('admin/topics', [
            'title' => 'Sezioni — Admin',
            'rows' => $rows,
            'admin_nav' => true,
        ]);
    }

    public static function topicSave(): void
    {
        Csrf::check();
        Auth::requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $desc = trim((string) ($_POST['description'] ?? ''));
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $icon = phlash_fa_sanitize((string) ($_POST['icon'] ?? ''), true);
        if ($name === '') {
            flash('err', 'Nome sezione obbligatorio.');
            redirect('admin/sezioni');
        }
        if ($id) {
            Database::query(
                'UPDATE topics SET name=?, description=?, icon=?, sort_order=? WHERE id=?',
                [$name, $desc, $icon, $sort, $id]
            );
        } else {
            $slug = unique_slug('topics', slugify($name));
            Database::insert(
                'INSERT INTO topics (name, slug, description, icon, sort_order) VALUES (?,?,?,?,?)',
                [$name, $slug, $desc, $icon, $sort]
            );
        }
        flash('ok', 'Sezione salvata.');
        redirect('admin/sezioni');
    }

    public static function topicDelete(): void
    {
        Csrf::check();
        Auth::requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $used = Database::one('SELECT id FROM stories WHERE topic_id = ? LIMIT 1', [$id]);
        if ($used) {
            flash('err', 'Non puoi eliminare una sezione che contiene storie.');
            redirect('admin/sezioni');
        }
        Database::query('DELETE FROM topics WHERE id=?', [$id]);
        flash('ok', 'Sezione eliminata.');
        redirect('admin/sezioni');
    }

    public static function settings(): void
    {
        Auth::requireAdmin();
        $keys = [
            'site_name', 'site_tagline', 'stories_per_page', 'promote_threshold',
            'allow_anon_comments', 'registration_open', 'comment_threshold',
            'timezone', 'custom_css',
        ];
        $vals = [];
        foreach ($keys as $k) {
            $vals[$k] = Database::setting($k, '');
        }
        View::render('admin/settings', [
            'title' => 'Impostazioni — Admin',
            'vals' => $vals,
            'admin_nav' => true,
        ]);
    }

    public static function settingsSave(): void
    {
        Csrf::check();
        Auth::requireAdmin();
        $map = [
            'site_name' => trim((string) ($_POST['site_name'] ?? 'Phlash')),
            'site_tagline' => trim((string) ($_POST['site_tagline'] ?? '')),
            'stories_per_page' => (string) max(5, min(30, (int) ($_POST['stories_per_page'] ?? 10))),
            'promote_threshold' => (string) max(1, min(50, (int) ($_POST['promote_threshold'] ?? 5))),
            'allow_anon_comments' => !empty($_POST['allow_anon_comments']) ? '1' : '0',
            'registration_open' => !empty($_POST['registration_open']) ? '1' : '0',
            'comment_threshold' => (string) (int) ($_POST['comment_threshold'] ?? 1),
            'timezone' => trim((string) ($_POST['timezone'] ?? 'Europe/Rome')),
            'custom_css' => (string) ($_POST['custom_css'] ?? ''),
        ];
        foreach ($map as $k => $v) {
            Database::setSetting($k, $v);
        }
        flash('ok', 'Impostazioni salvate.');
        redirect('admin/impostazioni');
    }

    public static function poll(): void
    {
        Auth::requireAdmin();
        $polls = Database::all('SELECT * FROM polls ORDER BY id DESC LIMIT 20');
        foreach ($polls as &$p) {
            $p['options'] = Database::all('SELECT * FROM poll_options WHERE poll_id = ?', [(int) $p['id']]);
        }
        unset($p);
        View::render('admin/poll', [
            'title' => 'Sondaggio — Admin',
            'polls' => $polls,
            'admin_nav' => true,
        ]);
    }

    public static function pollSave(): void
    {
        Csrf::check();
        Auth::requireAdmin();
        $question = trim((string) ($_POST['question'] ?? ''));
        $options = $_POST['options'] ?? [];
        if ($question === '' || !is_array($options)) {
            flash('err', 'Domanda e opzioni obbligatorie.');
            redirect('admin/sondaggio');
        }
        Database::query('UPDATE polls SET is_active = 0');
        $id = Database::insert('INSERT INTO polls (question, is_active, created_at) VALUES (?, 1, NOW())', [$question]);
        foreach ($options as $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            Database::insert('INSERT INTO poll_options (poll_id, label, votes) VALUES (?,?,0)', [$id, mb_substr($label, 0, 120)]);
        }
        flash('ok', 'Nuovo sondaggio attivato.');
        redirect('admin/sondaggio');
    }

    public static function stats(): void
    {
        Auth::requireAdmin();
        $range = self::statsRange();
        $report = \Phlash\Stats::report($range['from'], $range['to']);
        View::render('admin/stats', [
            'title' => 'Statistiche — Admin',
            'admin_nav' => true,
            'range' => $range,
            'report' => $report,
        ]);
    }

    private static function statsRange(): array
    {
        $period = (string) ($_GET['periodo'] ?? '30g');
        $da = trim((string) ($_GET['da'] ?? ''));
        $a = trim((string) ($_GET['a'] ?? ''));
        $today = strtotime('today') ?: time();
        $tomorrow = $today + 86400;
        $mesi = [1=>'gennaio',2=>'febbraio',3=>'marzo',4=>'aprile',5=>'maggio',6=>'giugno',
            7=>'luglio',8=>'agosto',9=>'settembre',10=>'ottobre',11=>'novembre',12=>'dicembre'];
        $labelDay = static function (int $ts) use ($mesi): string {
            return date('j', $ts) . ' ' . $mesi[(int) date('n', $ts)] . ' ' . date('Y', $ts);
        };

        if ($da !== '' && $a !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $da) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $a)) {
            $fromTs = strtotime($da . ' 00:00:00');
            $toTs = strtotime($a . ' 00:00:00');
            if ($fromTs && $toTs) {
                if ($fromTs > $toTs) {
                    $tmp = $fromTs;
                    $fromTs = $toTs;
                    $toTs = $tmp;
                    $tmp = $da;
                    $da = $a;
                    $a = $tmp;
                }
                $toTs += 86400;
                return [
                    'period' => 'custom',
                    'from' => date('Y-m-d H:i:s', $fromTs),
                    'to' => date('Y-m-d H:i:s', $toTs),
                    'da' => $da,
                    'a' => $a,
                    'label' => $labelDay($fromTs) . ' – ' . $labelDay($toTs - 86400),
                ];
            }
        }

        $presets = [
            'oggi' => [$today, $tomorrow, 'Oggi'],
            '7g' => [$today - 6 * 86400, $tomorrow, 'Ultimi 7 giorni'],
            '30g' => [$today - 29 * 86400, $tomorrow, 'Ultimi 30 giorni'],
            '90g' => [$today - 89 * 86400, $tomorrow, 'Ultimi 90 giorni'],
            'tutto' => [strtotime('2000-01-01') ?: $today, $tomorrow, 'Tutto il periodo'],
        ];
        if (!isset($presets[$period])) {
            $period = '30g';
        }
        [$fromTs, $toTs, $name] = $presets[$period];
        $label = $period === 'tutto' ? $name : $name . ' (' . $labelDay($fromTs) . ' – ' . $labelDay($toTs - 86400) . ')';
        return [
            'period' => $period,
            'from' => date('Y-m-d H:i:s', $fromTs),
            'to' => date('Y-m-d H:i:s', $toTs),
            'da' => date('Y-m-d', $fromTs),
            'a' => date('Y-m-d', $toTs - 86400),
            'label' => $label,
        ];
    }
}
