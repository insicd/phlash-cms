<?php
namespace Phlash;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . h(self::token()) . '">';
    }

    public static function check(): void
    {
        $sent = $_POST['_csrf'] ?? '';
        if (!is_string($sent) || $sent === '' || !hash_equals(self::token(), $sent)) {
            http_response_code(400);
            echo 'Richiesta non valida (CSRF). Torna indietro e riprova.';
            exit;
        }
    }
}
