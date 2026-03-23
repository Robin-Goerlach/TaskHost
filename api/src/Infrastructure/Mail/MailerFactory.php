<?php

declare(strict_types=1);

namespace TaskHost\Infrastructure\Mail;

use TaskHost\Infrastructure\Config\Env;
use TaskHost\Support\ApiException;

final class MailerFactory
{
    public static function create(string $projectRoot): MailerInterface
    {
        $transport = strtolower(Env::get('MAIL_TRANSPORT', 'file') ?? 'file');

        return match ($transport) {
            'file' => new FileMailer(Env::get('MAIL_FILE_DIR', $projectRoot . '/storage/mail') ?? ($projectRoot . '/storage/mail')),
            'native' => new NativeMailer(),
            'null' => new NullMailer(),
            default => throw new ApiException('MAIL_TRANSPORT ist ungültig. Erlaubt sind file, native oder null.', 500),
        };
    }
}
