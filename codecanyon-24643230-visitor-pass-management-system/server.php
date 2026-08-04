<?php
/**
 * Router script for PHP built-in server (fallback local host).
 * Used when `php artisan serve` is unavailable.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false; // serve the requested resource as-is
}

require_once __DIR__ . '/public/index.php';
