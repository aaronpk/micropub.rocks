<?php

declare(strict_types=1);

namespace Rocks\Http;

/**
 * An immutable HTTP response. Headers may repeat (the tests send two Link
 * headers, one for rel=self and one for rel=hub), so values are stored as lists.
 *
 * A response made without a content-type header gets PHP's default
 * (text/html with the configured default_charset) when sent.
 */
final class Response
{
    /** @param array<string, list<string>> $headers */
    private function __construct(
        public readonly int $status,
        public readonly string $body,
        private readonly array $headers = [],
    ) {
    }

    /** @param array<string, string> $headers */
    public static function make(int $status = 200, string $body = '', array $headers = []): self
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower($name)] = [$value];
        }

        return new self($status, $body, $normalized);
    }

    public static function text(string $body, int $status = 200): self
    {
        return self::make($status, $body, ['content-type' => 'text/plain; charset=utf-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return self::make($status, self::encodeJson($data), ['content-type' => 'application/json']);
    }

    /** Replaces the body with JSON, keeping this response's status and other headers (e.g. CORS). */
    public function withJson(mixed $data): self
    {
        return $this->withHeader('content-type', 'application/json')->withBody(self::encodeJson($data));
    }

    /** Encoded the same way the old Diactoros JsonResponse did. */
    private static function encodeJson(mixed $data): string
    {
        return json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return self::make($status, '', ['location' => $location]);
    }

    public function withStatus(int $status): self
    {
        return new self($status, $this->body, $this->headers);
    }

    public function withBody(string $body): self
    {
        return new self($this->status, $body, $this->headers);
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[strtolower($name)] = [$value];

        return new self($this->status, $this->body, $headers);
    }

    public function withAddedHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[strtolower($name)][] = $value;

        return new self($this->status, $this->body, $headers);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }

    /** @return array<string, list<string>> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $values) {
                $first = true;
                foreach ($values as $value) {
                    header($name . ': ' . $value, $first);
                    $first = false;
                }
            }
        }

        echo $this->body;
    }
}
