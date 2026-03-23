<?php

declare(strict_types=1);

namespace TaskHost\Http;

final class Response
{
    public function __construct(
        private readonly int $statusCode = 200,
        private readonly array $headers = [],
        private readonly string $body = ''
    ) {
    }

    public static function json(array $payload, int $statusCode = 200, array $headers = []): self
    {
        return new self(
            $statusCode,
            array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
        );
    }

    public static function noContent(): self
    {
        return new self(204, [], '');
    }

    public static function binary(string $body, string $contentType, string $fileName): self
    {
        return new self(200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . addslashes($fileName) . '"',
            'Content-Length' => (string) strlen($body),
        ], $body);
    }

    public function send(array $corsHeaders = []): void
    {
        http_response_code($this->statusCode);

        foreach (array_merge($corsHeaders, $this->headers) as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }
}
