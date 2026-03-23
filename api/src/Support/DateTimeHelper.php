<?php

declare(strict_types=1);

namespace TaskHost\Support;

final class DateTimeHelper
{
    public static function nowUtc(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    public static function normalizeNullable(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return (new \DateTimeImmutable($value))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }

    public static function normalize(string $value): string
    {
        return self::normalizeNullable($value) ?? self::nowUtc();
    }
}
