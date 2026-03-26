<?php

declare(strict_types=1);

$projectRoot = __DIR__;
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestedFile = realpath($projectRoot . $requestPath);
$projectRootReal = realpath($projectRoot);

// The PHP built-in web server ignores .htaccess. This router mirrors the
// shared-hosting behaviour and prevents accidental exposure of source files.
if (
    $requestedFile !== false
    && $projectRootReal !== false
    && str_starts_with($requestedFile, $projectRootReal)
    && is_file($requestedFile)
) {
    $relativePath = ltrim(substr($requestedFile, strlen($projectRootReal)), DIRECTORY_SEPARATOR);

    if (preg_match('#^(src|bin|vendor|storage|migrations|docs)(/|\\\\|$)#', str_replace('\\', '/', $relativePath)) === 1) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Direkter Zugriff auf interne Dateien ist nicht erlaubt.';
        return true;
    }

    return false;
}

require $projectRoot . '/index.php';
