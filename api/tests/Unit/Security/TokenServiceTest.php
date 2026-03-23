<?php

declare(strict_types=1);

namespace TaskHost\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use TaskHost\Security\TokenService;

final class TokenServiceTest extends TestCase
{
    public function testIssuePlainTokenCreates64CharacterHexString(): void
    {
        $service = new TokenService();

        $token = $service->issuePlainToken();

        self::assertSame(64, strlen($token));
        self::assertSame(1, preg_match('/^[a-f0-9]{64}$/', $token));
    }

    public function testIssuedTokensDifferAcrossCalls(): void
    {
        $service = new TokenService();

        self::assertNotSame($service->issuePlainToken(), $service->issuePlainToken());
    }

    public function testHashTokenUsesSha256(): void
    {
        $service = new TokenService();
        $plain = 'abc123';

        self::assertSame(hash('sha256', $plain), $service->hashToken($plain));
    }
}
