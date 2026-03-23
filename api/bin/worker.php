<?php

declare(strict_types=1);

use TaskHost\Bootstrap;

require_once dirname(__DIR__) . '/src/Infrastructure/Autoloader.php';

$projectRoot = dirname(__DIR__);
$runtime = Bootstrap::createRuntime($projectRoot);
$services = $runtime['services'];

$command = $argv[1] ?? '';
if ($command === '') {
    printUsage();
    exit(1);
}

$options = parseOptions(array_slice($argv, 2));

switch ($command) {
    case 'reminders:enqueue':
        $limit = max(1, (int) ($options['limit'] ?? 100));
        $result = $services['reminder_dispatch']->enqueueDueEmailReminders($limit);
        fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        exit(0);

    case 'queue:work':
        $limit = max(1, (int) ($options['limit'] ?? 50));
        $queue = (string) ($options['queue'] ?? 'mail');
        $once = array_key_exists('once', $options);
        $sleepSeconds = max(1, (int) ($options['sleep'] ?? 10));

        if ($once) {
            $result = $services['queue_worker']->drain($queue, $limit);
            fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
            exit(0);
        }

        while (true) {
            $result = $services['queue_worker']->drain($queue, $limit);
            fwrite(STDOUT, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
            sleep($sleepSeconds);
        }

    case 'queue:drain':
        $limit = max(1, (int) ($options['limit'] ?? 50));
        $queue = (string) ($options['queue'] ?? 'mail');
        $result = $services['queue_worker']->drain($queue, $limit);
        fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        exit(0);

    default:
        fwrite(STDERR, "Unbekanntes Kommando: {$command}\n\n");
        printUsage();
        exit(1);
}

function parseOptions(array $args): array
{
    $options = [];
    foreach ($args as $arg) {
        if (!str_starts_with($arg, '--')) {
            continue;
        }

        $arg = substr($arg, 2);
        if (str_contains($arg, '=')) {
            [$key, $value] = explode('=', $arg, 2);
            $options[$key] = $value;
            continue;
        }

        $options[$arg] = true;
    }

    return $options;
}

function printUsage(): void
{
    fwrite(STDOUT, <<<TXT
TaskHost Worker

Verwendung:
  php bin/worker.php reminders:enqueue [--limit=100]
  php bin/worker.php queue:work [--queue=mail] [--limit=50] [--sleep=10] [--once]
  php bin/worker.php queue:drain [--queue=mail] [--limit=50]
TXT . PHP_EOL);
}
