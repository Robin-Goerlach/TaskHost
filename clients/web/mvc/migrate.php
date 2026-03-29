<?php

declare(strict_types=1);

use App\Config\Env;
use App\Database\Connection;

require_once __DIR__ . '/app/Support/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

Env::load(__DIR__ . '/.env');
$pdo = Connection::make();
$sql = file_get_contents(__DIR__ . '/database/schema.sql');

if ($sql === false) {
    fwrite(STDERR, "Schema-Datei konnte nicht gelesen werden.\n");
    exit(1);
}

$pdo->exec($sql);

echo "Migration erfolgreich ausgeführt.\n";
