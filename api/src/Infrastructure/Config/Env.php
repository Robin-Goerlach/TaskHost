<?php

/**
 * TaskHost configuration loader and environment helper.
 *
 * This helper keeps the deployment model intentionally simple. The application
 * reads its configuration from a project local .env file and also keeps the
 * resolved project root in memory so that relative filesystem paths can be
 * expanded consistently.
 *
 * This is especially important for the shared-hosting deployment model where
 * the application lives below a path such as /taskhost and no dedicated public
 * webroot exists.
 *
 * @package TaskHost\Infrastructure\Config
 */

declare(strict_types=1);

namespace TaskHost\Infrastructure\Config;

final class Env
{
    private static ?string $projectRoot = null;

    /**
     * Loads key/value pairs from the project local .env file.
     *
     * @param string $projectRoot Absolute project root of the deployed API.
     */
    public static function load(string $projectRoot): void
    {
        self::$projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        $_ENV['TASKHOST_PROJECT_ROOT'] = self::$projectRoot;
        putenv('TASKHOST_PROJECT_ROOT=' . self::$projectRoot);

        $envPath = self::$projectRoot . '/.env';
        if (!is_file($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');

            $key = trim($key);
            $value = trim($value);

            if ($value !== '' && (
                ($value[0] === '"' && str_ends_with($value, '"')) ||
                ($value[0] === "'" && str_ends_with($value, "'"))
            )) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    /**
     * Returns the configured project root.
     */
    public static function projectRoot(): string
    {
        return self::$projectRoot
            ?? (string) ($_ENV['TASKHOST_PROJECT_ROOT'] ?? getenv('TASKHOST_PROJECT_ROOT') ?: getcwd() ?: '.');
    }

    /**
     * Returns a string environment variable.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return (string) $value;
    }

    /**
     * Returns a boolean environment variable.
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Returns an integer environment variable.
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);
        if ($value === null || !is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }

    /**
     * Resolves a path against the project root.
     *
     * Relative paths are intentionally supported because shared-hosting setups
     * often work with project-local directories such as storage/uploads.
     */
    public static function resolvePath(?string $path, ?string $fallback = null): string
    {
        $candidate = trim((string) ($path ?? ''));
        if ($candidate === '') {
            $candidate = trim((string) ($fallback ?? ''));
        }

        if ($candidate === '') {
            return self::projectRoot();
        }

        if (self::isAbsolutePath($candidate)) {
            return $candidate;
        }

        return self::projectRoot() . DIRECTORY_SEPARATOR . ltrim($candidate, '/\\');
    }

    /**
     * Checks whether the given path is absolute.
     */
    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('~^[A-Za-z]:\\\\~', $path) === 1;
    }
}
