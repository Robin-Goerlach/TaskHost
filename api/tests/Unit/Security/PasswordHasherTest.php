<?php

declare(strict_types=1);

namespace TaskHost\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use TaskHost\Security\PasswordHasher;

final class PasswordHasherTest extends TestCase
{
    public function testHashProducesNonPlainValueAndCanBeVerified(): void
    {
        $hasher = new PasswordHasher();
        $plain = 'ChangeMe123!';

        $hash = $hasher->hash($plain);

        self::assertNotSame($plain, $hash);
        self::assertTrue($hasher->verify($plain, $hash));
    }

    public function testVerifyFailsForWrongPassword(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('CorrectHorseBatteryStaple');

        self::assertFalse($hasher->verify('wrong-password', $hash));
    }
}
