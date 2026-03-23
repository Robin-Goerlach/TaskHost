<?php

declare(strict_types=1);

use TaskHost\Infrastructure\Config\Env;

require_once dirname(__DIR__) . '/src/Infrastructure/Autoloader.php';

$projectRoot = dirname(__DIR__);
$options = parseArguments($argv);

if ($options['help']) {
    printUsage();
    exit(0);
}

$envPath = $projectRoot . '/.env';
$envExamplePath = $projectRoot . '/.env.example';

writeLine('TaskHost API installer');
writeLine(str_repeat('=', 24));

if ((!is_file($envPath) || $options['forceCopyEnv']) && is_file($envExamplePath)) {
    if (!copy($envExamplePath, $envPath)) {
        fail('Konnte .env nicht aus .env.example erzeugen.');
    }

    writeOk('.env wurde aus .env.example erzeugt.');
} elseif (is_file($envPath)) {
    writeOk('.env vorhanden.');
} else {
    fail('.env fehlt und .env.example konnte nicht gefunden werden.');
}

Env::load($projectRoot);

$directories = [
    $projectRoot . '/storage',
    resolvePath($projectRoot, Env::get('UPLOAD_DIR', 'storage/uploads') ?? 'storage/uploads'),
    resolvePath($projectRoot, Env::get('MAIL_FILE_DIR', 'storage/mail') ?? 'storage/mail'),
];

foreach ($directories as $directory) {
    ensureDirectory($directory);
}

validateCoreConfiguration($projectRoot);

if (!$options['skipDoctor']) {
    runCommand([PHP_BINARY, $projectRoot . '/bin/doctor.php'], 'doctor');
}

if ($options['migrate']) {
    $command = [PHP_BINARY, $projectRoot . '/bin/migrate.php'];
    if ($options['legacyUpgrade']) {
        $command[] = '--legacy-upgrade';
    }
    runCommand($command, 'migrate');
}

if ($options['seed']) {
    runCommand([PHP_BINARY, $projectRoot . '/bin/seed.php'], 'seed');
}

writeLine('');
writeOk('Installer abgeschlossen.');
writeLine('Nächste Schritte:');
writeLine('  1. Backend starten: php -S 127.0.0.1:8080 -t public');
writeLine('  2. Optional Reminder/Mail verarbeiten: php bin/worker.php reminders:enqueue --limit=100');
writeLine('  3. Optional Mail-Queue abarbeiten: php bin/worker.php queue:drain --queue=mail --limit=50');

function parseArguments(array $argv): array
{
    return [
        'help' => in_array('--help', $argv, true) || in_array('-h', $argv, true),
        'migrate' => in_array('--migrate', $argv, true),
        'seed' => in_array('--seed', $argv, true),
        'skipDoctor' => in_array('--skip-doctor', $argv, true),
        'legacyUpgrade' => in_array('--legacy-upgrade', $argv, true),
        'forceCopyEnv' => in_array('--force-copy-env', $argv, true),
    ];
}

function printUsage(): void
{
    writeLine('Verwendung: php bin/install.php [optionen]');
    writeLine('');
    writeLine('Optionen:');
    writeLine('  --migrate           Führt nach der Grundprüfung die Migration aus.');
    writeLine('  --seed              Führt nach der Migration den Seeder aus.');
    writeLine('  --legacy-upgrade    Hängt an migrate.php das Legacy-Upgrade an.');
    writeLine('  --skip-doctor       Überspringt den Aufruf von doctor.php.');
    writeLine('  --force-copy-env    Überschreibt .env mit .env.example.');
    writeLine('  -h, --help          Zeigt diese Hilfe an.');
}

function validateCoreConfiguration(string $projectRoot): void
{
    $dsn = Env::get('DB_DSN', '');
    if ($dsn === '') {
        fail('DB_DSN ist nicht gesetzt.');
    }

    writeOk('DB_DSN ist gesetzt.');

    $mailTransport = strtolower(Env::get('MAIL_TRANSPORT', 'file') ?? 'file');
    if (!in_array($mailTransport, ['file', 'native', 'null'], true)) {
        fail('MAIL_TRANSPORT muss file, native oder null sein.');
    }
    writeOk('MAIL_TRANSPORT ist gültig: ' . $mailTransport);

    $fromAddress = Env::get('MAIL_FROM_ADDRESS', '');
    if ($fromAddress === '' || filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false) {
        fail('MAIL_FROM_ADDRESS ist ungültig.');
    }
    writeOk('MAIL_FROM_ADDRESS ist gültig.');

    if (str_starts_with($dsn, 'sqlite:')) {
        $sqlitePath = substr($dsn, 7);
        $resolved = resolvePath($projectRoot, $sqlitePath);
        ensureDirectory(dirname($resolved));
        writeOk('SQLite-Datei wird unter ' . $resolved . ' erwartet.');
    }
}

function resolvePath(string $projectRoot, string $path): string
{
    if ($path === '') {
        return $projectRoot;
    }

    if ($path[0] === '/' || preg_match('/^[A-Za-z]:\\/', $path) === 1) {
        return $path;
    }

    return $projectRoot . '/' . ltrim($path, '/');
}

function ensureDirectory(string $path): void
{
    if (!is_dir($path) && !mkdir($concurrentDirectory = $path, 0775, true) && !is_dir($concurrentDirectory)) {
        fail('Verzeichnis konnte nicht erstellt werden: ' . $path);
    }

    if (!is_writable($path)) {
        fail('Verzeichnis ist nicht beschreibbar: ' . $path);
    }

    writeOk('Verzeichnis bereit: ' . $path);
}

function runCommand(array $command, string $label): void
{
    writeLine('');
    writeLine('> Starte ' . $label . ': ' . implode(' ', $command));

    $process = proc_open(
        $command,
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        dirname(__DIR__)
    );

    if (!is_resource($process)) {
        fail('Konnte Prozess nicht starten: ' . $label);
    }

    $stdout = stream_get_contents($pipes[1]) ?: '';
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($stdout !== '') {
        writeLine(trim($stdout));
    }
    if ($stderr !== '') {
        writeLine(trim($stderr));
    }

    if ($exitCode !== 0) {
        fail('Schritt fehlgeschlagen: ' . $label . ' (Exit-Code ' . $exitCode . ')');
    }

    writeOk('Schritt erfolgreich: ' . $label);
}

function writeOk(string $message): void
{
    writeLine('[OK] ' . $message);
}

function fail(string $message): void
{
    fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
    exit(1);
}

function writeLine(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}
