<?php

declare(strict_types=1);

use TaskHost\Infrastructure\Config\Env;
use TaskHost\Infrastructure\Database\ConnectionFactory;

require_once dirname(__DIR__) . '/src/Infrastructure/Autoloader.php';

$projectRoot = dirname(__DIR__);
Env::load($projectRoot);

$checks = [];
$checks[] = checkValue('DB_DSN', Env::get('DB_DSN') !== null && Env::get('DB_DSN') !== '', 'DB_DSN ist gesetzt.');
$checks[] = checkValue('MAIL_FROM_ADDRESS', filter_var(Env::get('MAIL_FROM_ADDRESS', 'no-reply@taskhost.local'), FILTER_VALIDATE_EMAIL) !== false, 'MAIL_FROM_ADDRESS ist gültig.');
$checks[] = checkValue('MAIL_TRANSPORT', in_array(strtolower(Env::get('MAIL_TRANSPORT', 'file') ?? 'file'), ['file', 'native', 'null'], true), 'MAIL_TRANSPORT ist unterstützt.');
$checks[] = checkDirectory('UPLOAD_DIR', Env::get('UPLOAD_DIR', $projectRoot . '/storage/uploads') ?? ($projectRoot . '/storage/uploads'));
$checks[] = checkDirectory('MAIL_FILE_DIR', Env::get('MAIL_FILE_DIR', $projectRoot . '/storage/mail') ?? ($projectRoot . '/storage/mail'));

try {
    $pdo = ConnectionFactory::create();
    $checks[] = checkValue('DATABASE_CONNECTION', true, 'Datenbankverbindung erfolgreich aufgebaut.');
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $checks[] = checkValue('PDO_DRIVER', true, 'PDO-Treiber erkannt: ' . $driver);
} catch (Throwable $e) {
    $checks[] = checkValue('DATABASE_CONNECTION', false, 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
}

$hasFailure = false;
foreach ($checks as $check) {
    fwrite(STDOUT, sprintf('[%s] %s - %s', $check['ok'] ? 'OK' : 'FAIL', $check['name'], $check['message']) . PHP_EOL);
    if (!$check['ok']) {
        $hasFailure = true;
    }
}

exit($hasFailure ? 1 : 0);

function checkValue(string $name, bool $ok, string $message): array
{
    return ['name' => $name, 'ok' => $ok, 'message' => $message];
}

function checkDirectory(string $name, string $path): array
{
    if (!is_dir($path) && !mkdir($concurrentDirectory = $path, 0775, true) && !is_dir($concurrentDirectory)) {
        return ['name' => $name, 'ok' => false, 'message' => 'Verzeichnis konnte nicht erstellt werden: ' . $path];
    }

    return ['name' => $name, 'ok' => is_writable($path), 'message' => 'Verzeichnis geprüft: ' . $path];
}
