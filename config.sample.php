<?php
/**
 * Copia questo file in config.php e inserisci i dati del database.
 * Su hosting shared di solito li trovi nel pannello (cPanel / Plesk).
 */
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'config.php') {
    http_response_code(403);
    exit;
}

define('PHLASH_DB_HOST', 'localhost');
define('PHLASH_DB_NAME', 'phlash');
define('PHLASH_DB_USER', 'phlash');
define('PHLASH_DB_PASS', '');
define('PHLASH_DB_CHARSET', 'utf8mb4');

/** URL pubblico del sito, senza slash finale. Es. https://esempio.it */
define('PHLASH_BASE_URL', '');
