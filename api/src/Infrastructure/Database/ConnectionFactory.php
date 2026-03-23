<?php

declare(strict_types=1);

namespace TaskHost\Infrastructure\Database;

use PDO;
use PDOException;
use TaskHost\Infrastructure\Config\Env;

final class ConnectionFactory
{
    public static function create(): PDO
    {
        $dsn = Env::get('DB_DSN');
        if ($dsn === null || $dsn === '') {
            throw new PDOException('DB_DSN ist nicht gesetzt.');
        }

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
            $pdo->exec("SET NAMES utf8mb4");
        }

        return $pdo;
    }
}
