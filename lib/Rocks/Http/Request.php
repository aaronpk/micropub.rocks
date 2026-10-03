<?php

declare(strict_types=1);

namespace Rocks\Http;

/**
 * An immutable snapshot of an incoming HTTP request.
 *
 * Built from superglobals in production, and from named arguments in tests.
 *
 * Query and form parameters are kept as PHP parsed them, arrays included,
 * since Micropub requests use `category[]` and similar.
 */
final class Request
{
    /**
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $post
     * @param array<string, string> $headers Keys are lowercased.
     * @param array<string, list<array{name: string, type: string, tmp_name: string, error: int, size: int}>> $files
     *        Uploaded files by field name. Always a list, so `photo` and
     *        `photo[]` are read the same way.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $post = [],
        public readonly array $headers = [],
        public readonly string $body = '',
        public readonly array $files = [],
        public readonly string $url = '',
        public readonly string $protocol = '1.1',
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }
        foreach (['CONTENT_TYPE' => 'content-type', 'CONTENT_LENGTH' => 'content-length'] as $server => $name) {
            if (isset($_SERVER[$server])) {
                $headers[$name] = (string) $_SERVER[$server];
            }
        }

        $secure = ($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off';
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return new self(
            method:   $method,
            path:     self::normalizePath($path),
            query:    $_GET,
            post:     $_POST,
            headers:  $headers,
            body:     (string) file_get_contents('php://input'),
            files:    self::normalizeFiles($_FILES),
            url:      ($secure ? 'https' : 'http') . '://' . $host . $uri,
            protocol: substr((string) ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1'), 5) ?: '1.1',
        );
    }

    /**
     * Flattens $_FILES into a list of files per field. PHP's own layout puts
     * the attribute first for array uploads (`$_FILES['photo']['name'][0]`).
     *
     * @return array<string, list<array{name: string, type: string, tmp_name: string, error: int, size: int}>>
     */
    private static function normalizeFiles(array $files): array
    {
        $out = [];
        foreach ($files as $field => $file) {
            if (!is_array($file['name'] ?? null)) {
                $out[$field] = [$file];
                continue;
            }
            $out[$field] = [];
            foreach (array_keys($file['name']) as $i) {
                if (is_array($file['name'][$i])) {
                    continue; // Deeper nesting isn't used by Micropub
                }
                $out[$field][] = [
                    'name'     => (string) $file['name'][$i],
                    'type'     => (string) $file['type'][$i],
                    'tmp_name' => (string) $file['tmp_name'][$i],
                    'error'    => (int) $file['error'][$i],
                    'size'     => (int) $file['size'][$i],
                ];
            }
        }

        return $out;
    }

    /** The first successfully uploaded file for a field, or null. */
    public function file(string $name): ?array
    {
        foreach ($this->files[$name] ?? [] as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
                return $file;
            }
        }

        return null;
    }

    /** Collapse a trailing slash so /hub/ and /hub are the same route. */
    private static function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    /** A query string parameter, or null if it is absent or not a single value. */
    public function query(string $name): ?string
    {
        return self::scalar($this->query[$name] ?? null);
    }

    /** A form body parameter, or null if it is absent or not a single value. */
    public function post(string $name): ?string
    {
        return self::scalar($this->post[$name] ?? null);
    }

    /** POST body first, then query string. */
    public function input(string $name): ?string
    {
        return $this->post($name) ?? $this->query($name);
    }

    /**
     * The body decoded as a JSON object, or an empty array if it isn't one.
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        $data = json_decode($this->body, true);

        return is_array($data) ? $data : [];
    }

    /** Whether the body was sent as JSON, which a cross-site form can't do without a CORS preflight. */
    public function isJson(): bool
    {
        return str_starts_with(strtolower((string) $this->header('content-type')), 'application/json');
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    private static function scalar(mixed $value): ?string
    {
        if (is_string($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }
}
