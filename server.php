<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}
$_GET['r'] = $uri;
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
