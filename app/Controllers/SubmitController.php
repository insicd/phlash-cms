<?php
namespace Phlash\Controllers;

use Phlash\Auth;
use Phlash\Csrf;
use Phlash\Database;
use Phlash\View;

class SubmitController
{
    public static function form(): void
    {
        $user = Auth::requireUser();
        $id = (int) ($_GET['id'] ?? 0);
        $story = null;
        $tagStr = '';
        if ($id) {
            $story = Database::one('SELECT * FROM stories WHERE id = ?', [$id]);
            if (!$story || (!Auth::isAdmin() && (int) $story['user_id'] !== (int) $user['id'])) {
                View::notFound();
            }
            if ($story['status'] === 'published' && !Auth::isAdmin()) {
                flash('err', 'Una storia già pubblicata può essere modificata solo da un admin.');
                redirect('storia/' . $story['slug']);
            }
            $tags = tags_for_story($id);
            $tagStr = implode(', ', array_column($tags, 'name'));
        }
        View::render('submit', [
            'title' => $story ? 'Modifica storia' : 'Invia una storia',
            'story' => $story,
            'tag_str' => $tagStr,
        ]);
    }

    public static function save(): void
    {
        Csrf::check();
        $user = Auth::requireUser();
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $dept = trim((string) ($_POST['dept'] ?? ''));
        $source = trim((string) ($_POST['source_url'] ?? ''));
        $topicId = (int) ($_POST['topic_id'] ?? 0);
        $tags = trim((string) ($_POST['tags'] ?? ''));
        $preview = isset($_POST['preview']);

        if (mb_strlen($title) < 8 || mb_strlen($title) > 200) {
            flash('err', 'Il titolo deve avere tra 8 e 200 caratteri.');
            redirect($id ? 'invia?id=' . $id : 'invia');
        }
        if (mb_strlen($body) < 80) {
            flash('err', 'Il testo è troppo corto: Phlash è pensato per post e topic, non solo per un link. Scrivi almeno qualche paragrafo.');
            redirect($id ? 'invia?id=' . $id : 'invia');
        }
        if (mb_strlen($body) > 80000) {
            flash('err', 'Testo troppo lungo.');
            redirect($id ? 'invia?id=' . $id : 'invia');
        }
        if (!Database::one('SELECT id FROM topics WHERE id = ?', [$topicId])) {
            flash('err', 'Sezione non valida.');
            redirect('invia');
        }
        if ($source !== '' && !preg_match('#^https?://#i', $source)) {
            flash('err', 'L\'URL della fonte deve iniziare con http:// o https://.');
            redirect($id ? 'invia?id=' . $id : 'invia');
        }
        $dept = mb_substr($dept, 0, 80);
        if ($dept === '') {
            $topic = Database::one('SELECT name FROM topics WHERE id = ?', [$topicId]);
            $dept = slugify($topic['name'] ?? 'misc');
        }

        if ($preview) {
            View::render('submit', [
                'title' => 'Anteprima',
                'story' => [
                    'id' => $id,
                    'title' => $title,
                    'body' => $body,
                    'dept' => $dept,
                    'source_url' => $source,
                    'topic_id' => $topicId,
                ],
                'tag_str' => $tags,
                'preview' => true,
            ]);
            return;
        }

        if ($id) {
            $existing = Database::one('SELECT * FROM stories WHERE id = ?', [$id]);
            if (!$existing || (!Auth::isAdmin() && (int) $existing['user_id'] !== (int) $user['id'])) {
                View::notFound();
            }
            $slug = unique_slug('stories', slugify($title), $id);
            Database::query(
                'UPDATE stories SET topic_id=?, title=?, slug=?, dept=?, body=?, source_url=? WHERE id=?',
                [$topicId, $title, $slug, $dept, $body, $source !== '' ? $source : null, $id]
            );
            story_tags_sync($id, $tags);
            flash('ok', 'Storia aggiornata.');
            if ($existing['status'] === 'published') {
                redirect('storia/' . $slug);
            }
            redirect('upcoming');
        }

        $slug = unique_slug('stories', slugify($title));
        $newId = Database::insert(
            'INSERT INTO stories (user_id, topic_id, title, slug, dept, body, source_url, status, score, comment_count, views, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 0, NOW())',
            [
                (int) $user['id'],
                $topicId,
                $title,
                $slug,
                $dept,
                $body,
                $source !== '' ? $source : null,
                'pending',
            ]
        );
        story_tags_sync($newId, $tags);
        flash('ok', 'Storia inviata. Resta in «In arrivo» finché la comunità (o un admin) non la promuove.');
        redirect('upcoming');
    }
}
