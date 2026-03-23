<?php

declare(strict_types=1);

use TaskHost\Infrastructure\Config\Env;
use TaskHost\Infrastructure\Database\ConnectionFactory;

require_once dirname(__DIR__) . '/src/Infrastructure/Autoloader.php';

$projectRoot = dirname(__DIR__);
Env::load($projectRoot);

$pdo = ConnectionFactory::create();
$dsn = Env::get('DB_DSN', '');
$migration = str_starts_with($dsn, 'sqlite:')
    ? $projectRoot . '/migrations/001_sqlite.sql'
    : $projectRoot . '/migrations/001_mysql.sql';

$sql = file_get_contents($migration);

if ($sql === false) {
    fwrite(STDERR, "Migration konnte nicht geladen werden.\n");
    exit(1);
}

$pdo->exec($sql);

fwrite(STDOUT, "Migration erfolgreich ausgeführt: {$migration}\n");
