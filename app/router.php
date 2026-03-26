<?php

declare(strict_types=1);

$projectRoot = __DIR__;
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestedFile = realpath($projectRoot . $requestPath);
$projectRootReal = realpath($projectRoot);

if (
    $requestedFile !== false
    && $projectRootReal !== false
    && str_starts_with($requestedFile, $projectRootReal)
    && is_file($requestedFile)
) {
    return false;
}

readfile($projectRoot . '/index.html');
