<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'TaskHost\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    $file = __DIR__ . '/../' . $relativePath;

    if (is_file($file)) {
        require $file;
    }
});
