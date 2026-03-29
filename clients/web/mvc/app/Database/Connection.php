<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\Env;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Kapselt den Aufbau der PDO-Verbindung.
 *
 * Diese Klasse sorgt dafür, dass die Zugangsdaten sauber aus der .env gelesen
 * und die PDO-Optionen an einer zentralen Stelle gesetzt werden.
 */
class Connection
{
    public static function make(): PDO
    {
        $driver = Env::get('DB_CONNECTION', 'mysql');
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $database = Env::get('DB_DATABASE');
        $username = Env::get('DB_USERNAME');
        $password = Env::get('DB_PASSWORD', '');

        if ($driver !== 'mysql') {
            throw new RuntimeException('Nur die Datenbankverbindung "mysql" wird in dieser Variante unterstützt.');
        }

        if ($database === null || $username === null) {
            throw new RuntimeException('Bitte prüfe deine .env-Datei. DB_DATABASE und DB_USERNAME werden benötigt.');
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('MySQL-Verbindung fehlgeschlagen: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
