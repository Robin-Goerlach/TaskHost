<?php

declare(strict_types=1);

/**
 * Router-Datei für den eingebauten PHP-Entwicklungsserver.
 *
 * Statische Dateien (CSS, Bilder, JS) werden direkt ausgeliefert.
 * Alle anderen Requests gehen an index.php.
 */

$requestedFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($requestedFile !== false && is_file($requestedFile)) {
    return false;
}

require __DIR__ . '/index.php';
