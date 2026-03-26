<?php

/**
 * Built-in PHP development server router for the optional public/ webroot.
 *
 * The PHP built-in web server does not evaluate .htaccess. This router mirrors
 * the behaviour of public/.htaccess so that developers can test the classic
 * webroot profile locally if they ever need it.
 *
 * @package TaskHost
 */

declare(strict_types=1);

$publicRoot = __DIR__;
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestedFile = realpath($publicRoot . $requestPath);
$publicRootReal = realpath($publicRoot);

// Real files in the public webroot should still be served directly.
if (
    $requestedFile !== false
    && $publicRootReal !== false
    && str_starts_with($requestedFile, $publicRootReal)
    && is_file($requestedFile)
) {
    return false;
}

require $publicRoot . '/index.php';
