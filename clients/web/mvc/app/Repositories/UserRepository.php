<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Storage;

class UserRepository
{
    public function __construct(private Storage $storage)
    {
    }

    public function create(array $data): int
    {
        return $this->storage->transaction(function (array &$db) use ($data): int {
            $id = (int) $db['meta']['user_auto_id'];
            $db['meta']['user_auto_id']++;

            $db['users'][] = [
                'id' => $id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password_hash' => $data['password_hash'],
                'created_at' => date('c'),
            ];

            return $id;
        });
    }

    public function findByEmail(string $email): ?array
    {
        return $this->storage->transaction(function (array &$db) use ($email): ?array {
            foreach ($db['users'] as $user) {
                if (($user['email'] ?? '') === $email) {
                    return $user;
                }
            }

            return null;
        });
    }

    public function findById(int $id): ?array
    {
        return $this->storage->transaction(function (array &$db) use ($id): ?array {
            foreach ($db['users'] as $user) {
                if ((int) ($user['id'] ?? 0) === $id) {
                    return $user;
                }
            }

            return null;
        });
    }
}
