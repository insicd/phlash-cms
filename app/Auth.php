<?php
namespace Phlash;

class Auth
{
    public static function user(): ?array
    {
        static $cached = false;
        static $user = null;
        if ($cached) {
            return $user;
        }
        $cached = true;
        $id = $_SESSION['uid'] ?? 0;
        if (!$id) {
            return null;
        }
        $user = Database::one('SELECT * FROM users WHERE id = ? AND status = ?', [(int) $id, 'active']);
        if (!$user) {
            unset($_SESSION['uid']);
        }
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u && $u['role'] === 'admin';
    }

    public static function apiUser(): ?array
    {
        static $cached = false;
        static $user = null;
        if ($cached) {
            return $user;
        }
        $cached = true;
        $token = self::bearerToken();
        if ($token === '') {
            return null;
        }
        $user = Database::one(
            'SELECT * FROM users WHERE api_token_hash = ? AND status = ?',
            [self::hashApiToken($token), 'active']
        );
        return $user;
    }

    public static function requireApiUser(): array
    {
        $u = self::apiUser();
        if (!$u) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            header('WWW-Authenticate: Bearer realm="Phlash"');
            echo json_encode(['ok' => false, 'error' => 'Token API mancante o non valido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return $u;
    }

    public static function issueApiToken(int $userId): string
    {
        $token = 'phl_' . bin2hex(random_bytes(24));
        Database::query('UPDATE users SET api_token_hash = ? WHERE id = ?', [self::hashApiToken($token), $userId]);
        return $token;
    }

    public static function revokeApiToken(int $userId): void
    {
        Database::query('UPDATE users SET api_token_hash = NULL WHERE id = ?', [$userId]);
    }

    public static function hashApiToken(string $token): string
    {
        $pepper = Database::setting('ip_salt', 'phlash');
        return hash('sha256', $pepper . '|' . $token);
    }

    private static function bearerToken(): string
    {
        $auth = request_header('Authorization');
        if (preg_match('/^Bearer\s+(\S+)/i', $auth, $m)) {
            return $m[1];
        }
        $alt = trim(request_header('X-Phlash-Token'));
        return $alt;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $user['id'];
        Database::query('UPDATE users SET last_login = NOW() WHERE id = ?', [(int) $user['id']]);
    }

    public static function logout(): void
    {
        unset($_SESSION['uid']);
        session_regenerate_id(true);
    }

    public static function requireUser(): array
    {
        $u = self::user();
        if (!$u) {
            flash('err', 'Devi essere registrato per continuare.');
            $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('login');
        }
        return $u;
    }

    public static function requireAdmin(): array
    {
        $u = self::requireUser();
        if ($u['role'] !== 'admin') {
            http_response_code(403);
            echo 'Accesso riservato agli amministratori.';
            exit;
        }
        return $u;
    }

    public static function attempt(string $login, string $password): ?array
    {
        $user = Database::one(
            'SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1',
            [$login, $login]
        );
        if (!$user || $user['status'] !== 'active') {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        return $user;
    }
}
