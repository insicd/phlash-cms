<?php
namespace Phlash\Controllers;

use Phlash\Auth;
use Phlash\Csrf;
use Phlash\Database;
use Phlash\View;

class AuthController
{
    public static function loginForm(): void
    {
        if (Auth::check()) {
            redirect('');
        }
        View::render('auth_login', ['title' => 'Accedi']);
    }

    public static function login(): void
    {
        Csrf::check();
        $login = trim((string) ($_POST['login'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $user = Auth::attempt($login, $pass);
        if (!$user) {
            flash('err', 'Credenziali non valide, oppure account sospeso.');
            redirect('login');
        }
        Auth::login($user);
        $next = $_SESSION['after_login'] ?? '';
        unset($_SESSION['after_login']);
        if (is_string($next) && $next !== '' && strpos($next, '://') === false) {
            header('Location: ' . $next, true, 302);
            exit;
        }
        redirect('');
    }

    public static function registerForm(): void
    {
        if (Auth::check()) {
            redirect('');
        }
        if (Database::setting('registration_open', '1') !== '1') {
            flash('err', 'Le registrazioni sono chiuse.');
            redirect('login');
        }
        $captcha = StoryController::freshCaptcha();
        View::render('auth_register', ['title' => 'Registrati', 'captcha' => $captcha]);
    }

    public static function register(): void
    {
        Csrf::check();
        if (Database::setting('registration_open', '1') !== '1') {
            flash('err', 'Le registrazioni sono chiuse.');
            redirect('login');
        }
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password2'] ?? '');
        $answer = (int) ($_POST['captcha'] ?? -1);

        if ($answer !== (int) ($_SESSION['captcha'] ?? -2)) {
            flash('err', 'Controllo anti-spam errato.');
            redirect('registrati');
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            flash('err', 'Lo username deve avere 3-20 caratteri (lettere, numeri, underscore).');
            redirect('registrati');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('err', 'Email non valida.');
            redirect('registrati');
        }
        if (strlen($pass) < 8 || $pass !== $pass2) {
            flash('err', 'La password deve avere almeno 8 caratteri e coincidere con la conferma.');
            redirect('registrati');
        }
        if (Database::one('SELECT id FROM users WHERE username = ? OR email = ?', [$username, $email])) {
            flash('err', 'Username o email già in uso.');
            redirect('registrati');
        }
        $id = Database::insert(
            'INSERT INTO users (username, email, password_hash, role, karma, created_at, status) VALUES (?, ?, ?, ?, 0, NOW(), ?)',
            [$username, $email, password_hash($pass, PASSWORD_DEFAULT), 'user', 'active']
        );
        $user = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
        Auth::login($user);
        flash('ok', 'Benvenuto su Phlash, ' . $username . '. Ora puoi inviare storie.');
        redirect('');
    }

    public static function logout(): void
    {
        Auth::logout();
        flash('ok', 'Sei uscito.');
        redirect('');
    }
}
