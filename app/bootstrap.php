<?php

use Phlash\Database;

define('PHLASH_ROOT', dirname(__DIR__));
define('PHLASH_APP', PHLASH_ROOT . '/app');
define('PHLASH_NAME', 'Phlash');
define('PHLASH_VERSION', '0.9');

spl_autoload_register(function ($class) {
    if (strpos($class, 'Phlash\\') !== 0) {
        return;
    }
    $rel = str_replace('\\', '/', substr($class, strlen('Phlash\\')));
    $file = PHLASH_APP . '/' . $rel . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require PHLASH_APP . '/helpers.php';

$installed = is_file(PHLASH_ROOT . '/config.php') && is_file(PHLASH_ROOT . '/install.lock');
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');

if (!$installed && $script !== 'install.php') {
    header('Location: install.php');
    exit;
}

if ($installed) {
    require PHLASH_ROOT . '/config.php';
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('phlash');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

if ($installed) {
    $tz = Database::setting('timezone', 'Europe/Rome');
    @date_default_timezone_set($tz);
    try {
        Database::pdo()->query('SELECT icon FROM topics LIMIT 1');
    } catch (Throwable $e) {
        Database::pdo()->exec("ALTER TABLE topics ADD COLUMN icon VARCHAR(64) NOT NULL DEFAULT '' AFTER description");
        $defaults = [
            'notizie' => 'newspaper',
            'tecnologia' => 'microchip',
            'scienza' => 'flask',
            'cultura' => 'book-open',
            'chiedi-a-phlash' => 'circle-question',
            'giochi' => 'gamepad',
            'societa' => 'users',
        ];
        foreach ($defaults as $slug => $icon) {
            Database::query('UPDATE topics SET icon = ? WHERE slug = ? AND icon = \'\'', [$icon, $slug]);
        }
    }
}

function phlash_path(): string
{
    $r = $_GET['r'] ?? '';
    if (is_string($r) && $r !== '') {
        return $r;
    }
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($base !== '/' && $base !== '.' && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }
    $uri = '/' . trim($uri, '/');
    if ($uri === '/index.php') {
        return '/';
    }
    return $uri === '/' ? '/' : $uri;
}
