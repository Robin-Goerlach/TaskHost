#!/usr/bin/env php
<?php

declare(strict_types=1);

use TaskHost\Infrastructure\Config\Env;
use TaskHost\Infrastructure\Database\ConnectionFactory;

require_once dirname(__DIR__) . '/src/Infrastructure/Autoloader.php';

$projectRoot = dirname(__DIR__);
Env::load($projectRoot);

$dsn = Env::get('DB_DSN', '');
if ($dsn === '') {
    fwrite(STDERR, "DB_DSN ist nicht gesetzt.\n");
    exit(1);
}

$driver = str_starts_with($dsn, 'sqlite:') ? 'sqlite' : 'mysql';
$runLegacyUpgrade = in_array('--legacy-upgrade', $argv, true);

$baseMigration = findLatestFullSchemaMigration($projectRoot . '/migrations', $driver);
if ($baseMigration === null) {
    fwrite(STDERR, "Keine Full-Schema-Migration für {$driver} gefunden.\n");
    exit(1);
}

$pdo = ConnectionFactory::create();
ensureStorageDirectories();

$sql = file_get_contents($baseMigration);
if ($sql === false) {
    fwrite(STDERR, "Migration konnte nicht geladen werden: {$baseMigration}\n");
    exit(1);
}

$pdo->exec($sql);
fwrite(STDOUT, "Full-Schema erfolgreich ausgeführt: {$baseMigration}\n");

if ($runLegacyUpgrade) {
    $legacyMigration = $projectRoot . '/migrations/020_' . $driver . '_async_mail_and_queue.sql';
    if (is_file($legacyMigration)) {
        $legacySql = file_get_contents($legacyMigration);
        if ($legacySql === false) {
            fwrite(STDERR, "Legacy-Upgrade konnte nicht geladen werden: {$legacyMigration}\n");
            exit(1);
        }
        $pdo->exec($legacySql);
        fwrite(STDOUT, "Legacy-Upgrade erfolgreich ausgeführt: {$legacyMigration}\n");
    }
}

/**
 * Finds the latest full schema migration for the selected database driver.
 */
function findLatestFullSchemaMigration(string $migrationsDir, string $driver): ?string
{
    $files = glob($migrationsDir . '/*_' . $driver . '_full_schema*.sql') ?: [];
    sort($files, SORT_NATURAL);

    return $files !== [] ? end($files) : null;
}

/**
 * Ensures that the local storage directories exist before runtime starts.
 */
function ensureStorageDirectories(): void
{
    $paths = [
        Env::resolvePath(Env::get('UPLOAD_DIR'), 'storage/uploads'),
        Env::resolvePath(Env::get('MAIL_FILE_DIR'), 'storage/mail'),
    ];

    foreach ($paths as $path) {
        if (!is_dir($path) && !mkdir($concurrentDirectory = $path, 0775, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException('Verzeichnis konnte nicht erstellt werden: ' . $path);
        }
    }
}
