<?php

require __DIR__ . '/app/bootstrap.php';

use Phlash\Csrf;
use Phlash\Router;
use Phlash\Controllers\AdminController;
use Phlash\Controllers\ApiController;
use Phlash\Controllers\AuthController;
use Phlash\Controllers\CommentController;
use Phlash\Controllers\HomeController;
use Phlash\Controllers\PollController;
use Phlash\Controllers\StoryController;
use Phlash\Controllers\SubmitController;
use Phlash\Controllers\UserController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/upcoming', [HomeController::class, 'upcoming']);
$router->get('/sezione/{slug}', [HomeController::class, 'topic']);
$router->get('/cerca', [HomeController::class, 'search']);
$router->get('/rss', [HomeController::class, 'rss']);

$router->get('/api/v1', [ApiController::class, 'index']);
$router->get('/api/v1/me', [ApiController::class, 'me']);
$router->get('/api/v1/topics', [ApiController::class, 'topics']);
$router->post('/api/v1/stories', [ApiController::class, 'createStory']);
$router->get('/api/v1/stories/{id}', [ApiController::class, 'showStory']);

$router->get('/storia/{slug}', [StoryController::class, 'show']);
$router->post('/storia/vota', [StoryController::class, 'vote']);
$router->get('/{year}/{month}/{filename}', [StoryController::class, 'legacy']);
$router->get('/{year}/{month}/{day}/{filename}', [StoryController::class, 'legacy']);

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/registrati', [AuthController::class, 'registerForm']);
$router->post('/registrati', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/invia', [SubmitController::class, 'form']);
$router->post('/invia', [SubmitController::class, 'save']);

$router->post('/commento', [CommentController::class, 'store']);
$router->post('/commento/vota', [CommentController::class, 'vote']);

$router->get('/utente/{username}', [UserController::class, 'profile']);
$router->post('/utente/api-token', [UserController::class, 'apiToken']);

$router->post('/sondaggio', [PollController::class, 'vote']);

$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/statistiche', [AdminController::class, 'stats']);
$router->get('/admin/storie', [AdminController::class, 'stories']);
$router->post('/admin/storie', [AdminController::class, 'storyAction']);
$router->get('/admin/commenti', [AdminController::class, 'comments']);
$router->post('/admin/commenti', [AdminController::class, 'commentAction']);
$router->get('/admin/utenti', [AdminController::class, 'users']);
$router->post('/admin/utenti', [AdminController::class, 'userAction']);
$router->get('/admin/sezioni', [AdminController::class, 'topics']);
$router->post('/admin/sezioni', [AdminController::class, 'topicSave']);
$router->post('/admin/sezioni/elimina', [AdminController::class, 'topicDelete']);
$router->get('/admin/impostazioni', [AdminController::class, 'settings']);
$router->post('/admin/impostazioni', [AdminController::class, 'settingsSave']);
$router->get('/admin/sondaggio', [AdminController::class, 'poll']);
$router->post('/admin/sondaggio', [AdminController::class, 'pollSave']);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = phlash_path();
if ($method === 'POST' && strpos($path, '/api/') !== 0) {
    Csrf::check();
}

$router->dispatch($method, $path);
