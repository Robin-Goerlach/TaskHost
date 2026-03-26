<?php

/**
 * Creates the PDO connection used by TaskHost.
 *
 * The factory supports both MySQL/MariaDB and SQLite. For SQLite deployments
 * relative file paths are resolved against the API project root so that the
 * same .env file works consistently for CLI commands and HTTP requests.
 *
 * @package TaskHost\Infrastructure\Database
 */

declare(strict_types=1);

namespace TaskHost\Infrastructure\Database;

use PDO;
use PDOException;
use TaskHost\Infrastructure\Config\Env;

final class ConnectionFactory
{
    /**
     * Creates a configured PDO connection.
     */
    public static function create(): PDO
    {
        $dsn = Env::get('DB_DSN');
        if ($dsn === null || $dsn === '') {
            throw new PDOException('DB_DSN ist nicht gesetzt.');
        }

        $dsn = self::resolveDsn($dsn);
        $user = Env::get('DB_USER', '');
        $password = Env::get('DB_PASSWORD', '');

        $pdo = new PDO(
            $dsn,
            $user !== '' ? $user : null,
            $password !== '' ? $password : null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        if (str_starts_with($dsn, 'sqlite:')) {
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $pdo->exec('SET NAMES utf8mb4');
        }

        return $pdo;
    }

    /**
     * Resolves relative SQLite DSNs against the project root.
     */
    private static function resolveDsn(string $dsn): string
    {
        if (!str_starts_with($dsn, 'sqlite:')) {
            return $dsn;
        }

        $sqlitePath = substr($dsn, strlen('sqlite:'));
        if ($sqlitePath === '' || self::isAbsolutePath($sqlitePath) || $sqlitePath === ':memory:') {
            return $dsn;
        }

        return 'sqlite:' . Env::resolvePath($sqlitePath);
    }

    /**
     * Checks whether a filesystem path is absolute.
     */
    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('~^[A-Za-z]:\\\\~', $path) === 1;
    }
}
