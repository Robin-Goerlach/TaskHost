<?php

/**
 * Factory for the currently configured mail transport.
 *
 * Mail delivery is intentionally decoupled from the business logic so that
 * TaskHost can run with file-based mail capture on shared hosting or local test
 * setups without changing the surrounding services.
 *
 * @package TaskHost\Infrastructure\Mail
 */

declare(strict_types=1);

namespace TaskHost\Infrastructure\Mail;

use TaskHost\Infrastructure\Config\Env;
use TaskHost\Support\ApiException;

final class MailerFactory
{
    /**
     * Creates the configured mail transport.
     */
    public static function create(string $projectRoot): MailerInterface
    {
        $transport = strtolower(Env::get('MAIL_TRANSPORT', 'file') ?? 'file');
        $mailDirectory = Env::resolvePath(Env::get('MAIL_FILE_DIR'), $projectRoot . '/storage/mail');

        return match ($transport) {
            'file' => new FileMailer($mailDirectory),
            'native' => new NativeMailer(),
            'null' => new NullMailer(),
            default => throw new ApiException('MAIL_TRANSPORT ist ungültig. Erlaubt sind file, native oder null.', 500),
        };
    }
}
