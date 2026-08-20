<?php
namespace Phlash\Controllers;

use Phlash\Auth;
use Phlash\Csrf;
use Phlash\Database;

class PollController
{
    public static function vote(): void
    {
        Csrf::check();
        $optionId = (int) ($_POST['option_id'] ?? 0);
        $option = Database::one(
            'SELECT o.*, p.is_active FROM poll_options o JOIN polls p ON p.id = o.poll_id WHERE o.id = ?',
            [$optionId]
        );
        if (!$option || !(int) $option['is_active']) {
            flash('err', 'Sondaggio non disponibile.');
            redirect('');
        }
        $pollId = (int) $option['poll_id'];
        $uid = Auth::id();
        $hash = ip_hash();
        if ($uid) {
            $dup = Database::one('SELECT id FROM poll_votes WHERE poll_id = ? AND user_id = ?', [$pollId, $uid]);
        } else {
            $dup = Database::one('SELECT id FROM poll_votes WHERE poll_id = ? AND ip_hash = ? AND user_id IS NULL', [$pollId, $hash]);
        }
        if ($dup) {
            flash('err', 'Hai già votato questo sondaggio.');
            redirect('');
        }
        Database::query(
            'INSERT INTO poll_votes (poll_id, option_id, user_id, ip_hash, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$pollId, $optionId, $uid, $hash]
        );
        Database::query('UPDATE poll_options SET votes = votes + 1 WHERE id = ?', [$optionId]);
        flash('ok', 'Voto al sondaggio registrato.');
        redirect('');
    }

    public static function active(): ?array
    {
        $poll = Database::one('SELECT * FROM polls WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
        if (!$poll) {
            return null;
        }
        $poll['options'] = Database::all(
            'SELECT * FROM poll_options WHERE poll_id = ? ORDER BY id',
            [(int) $poll['id']]
        );
        $poll['total'] = 0;
        foreach ($poll['options'] as $o) {
            $poll['total'] += (int) $o['votes'];
        }
        $uid = Auth::id();
        if ($uid) {
            $poll['voted'] = (bool) Database::one(
                'SELECT id FROM poll_votes WHERE poll_id = ? AND user_id = ?',
                [(int) $poll['id'], $uid]
            );
        } else {
            $poll['voted'] = (bool) Database::one(
                'SELECT id FROM poll_votes WHERE poll_id = ? AND ip_hash = ? AND user_id IS NULL',
                [(int) $poll['id'], ip_hash()]
            );
        }
        return $poll;
    }
}
