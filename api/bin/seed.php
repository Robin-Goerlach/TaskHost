<?php

declare(strict_types=1);

use TaskHost\Infrastructure\Config\Env;
use TaskHost\Infrastructure\Database\ConnectionFactory;
use TaskHost\Support\DateTimeHelper;

require_once dirname(__DIR__) . '/src/Infrastructure/Autoloader.php';

$projectRoot = dirname(__DIR__);
Env::load($projectRoot);

$pdo = ConnectionFactory::create();

$existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$existsStmt->execute(['email' => 'alice@example.com']);

if ($existsStmt->fetch()) {
    fwrite(STDOUT, "Seed bereits vorhanden.\n");
    exit(0);
}

$pdo->beginTransaction();

try {
    $now = DateTimeHelper::nowUtc();
    $passwordHash = password_hash('ChangeMe123!', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password_hash, display_name, timezone, created_at)
         VALUES (:email, :password_hash, :display_name, :timezone, :created_at)'
    );
    $stmt->execute([
        'email' => 'alice@example.com',
        'password_hash' => $passwordHash,
        'display_name' => 'Alice Demo',
        'timezone' => 'Europe/Berlin',
        'created_at' => $now,
    ]);

    $userId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare(
        'INSERT INTO task_lists (owner_user_id, folder_id, title, color, is_archived, is_default, position, created_at, updated_at)
         VALUES (:owner_user_id, NULL, :title, :color, 0, 1, 0, :created_at, :updated_at)'
    );
    $stmt->execute([
        'owner_user_id' => $userId,
        'title' => 'Inbox',
        'color' => '#2d6cdf',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $listId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare(
        'INSERT INTO tasks
         (list_id, created_by_user_id, assignee_user_id, title, due_at, completed_at, is_starred, position, recurrence_type, recurrence_interval, created_at, updated_at)
         VALUES
         (:list_id, :created_by_user_id, NULL, :title, :due_at, NULL, 1, 0, NULL, 1, :created_at, :updated_at)'
    );
    $stmt->execute([
        'list_id' => $listId,
        'created_by_user_id' => $userId,
        'title' => 'TaskHost API ausprobieren',
        'due_at' => (new DateTimeImmutable('tomorrow', new DateTimeZone('Europe/Berlin')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $pdo->commit();
    fwrite(STDOUT, "Seed erfolgreich erstellt.\n");
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Seed fehlgeschlagen: {$e->getMessage()}\n");
    exit(1);
}
