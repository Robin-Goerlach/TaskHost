<?php

/**
 * Normalized HTTP request model for TaskHost.
 *
 * The request class strips the externally visible service base path from the
 * incoming URI. This allows the same API code to run below /taskhost,
 * /taskhost_old or any other service path without changing the registered
 * route patterns.
 *
 * @package TaskHost\Http
 */

declare(strict_types=1);

namespace TaskHost\Http;

final class Request
{
    private array $attributes = [];

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers,
        public readonly mixed $body,
        public readonly array $files = [],
        public readonly string $basePath = ''
    ) {
    }

    /**
     * Builds a request object from PHP globals.
     *
     * @param string $basePath Service base path such as /taskhost.
     */
    public static function fromGlobals(string $basePath = ''): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $normalizedBasePath = self::normalizeBasePath($basePath);
        $normalizedPath = self::normalizeRequestPath($path, $normalizedBasePath);

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $query = $_GET ?? [];
        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';

        $body = null;
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $body = $raw !== '' ? json_decode($raw, true) : [];
            if (!is_array($body)) {
                $body = [];
            }
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $body = $_POST;
            if ($body === [] && !str_contains($contentType, 'multipart/form-data')) {
                parse_str(file_get_contents('php://input') ?: '', $parsed);
                if (is_array($parsed) && $parsed !== []) {
                    $body = $parsed;
                }
            }
        }

        return new self($method, $normalizedPath, $query, $headers, $body ?? [], $_FILES ?? [], $normalizedBasePath);
    }

    /**
     * Returns one request input value.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        if (!is_array($this->body)) {
            return $default;
        }

        return $this->body[$key] ?? $default;
    }

    /**
     * Returns the bearer token from the Authorization header.
     */
    public function bearerToken(): ?string
    {
        $header = $this->headers['Authorization'] ?? $this->headers['authorization'] ?? null;
        if ($header === null) {
            return null;
        }

        if (!preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    /**
     * Adds a computed attribute to the request.
     */
    public function withAttribute(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;

        return $clone;
    }

    /**
     * Reads a computed attribute.
     */
    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Normalizes a service base path.
     */
    private static function normalizeBasePath(string $basePath): string
    {
        $basePath = trim($basePath);
        if ($basePath === '' || $basePath === '/') {
            return '';
        }

        return '/' . trim($basePath, '/');
    }

    /**
     * Removes the externally visible service path from the raw request URI.
     */
    private static function normalizeRequestPath(string $path, string $basePath): string
    {
        if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        if ($path === '') {
            $path = '/';
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path;
    }
}
