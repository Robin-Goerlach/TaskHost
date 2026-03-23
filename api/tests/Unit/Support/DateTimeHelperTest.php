<?php

declare(strict_types=1);

namespace TaskHost\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use TaskHost\Support\DateTimeHelper;

final class DateTimeHelperTest extends TestCase
{
    public function testNormalizeNullableReturnsNullForEmptyInput(): void
    {
        self::assertNull(DateTimeHelper::normalizeNullable(null));
        self::assertNull(DateTimeHelper::normalizeNullable('   '));
    }

    public function testNormalizeNullableConvertsToUtc(): void
    {
        $value = '2026-03-23T10:15:00+02:00';

        self::assertSame('2026-03-23 08:15:00', DateTimeHelper::normalizeNullable($value));
    }

    public function testNormalizeReturnsUtcTimestampString(): void
    {
        $normalized = DateTimeHelper::normalize('2026-03-23T10:15:00+02:00');

        self::assertSame('2026-03-23 08:15:00', $normalized);
    }
}
