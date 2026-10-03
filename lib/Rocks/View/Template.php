<?php

declare(strict_types=1);

namespace Rocks\View;

use ORM;
use RuntimeException;
use Throwable;

/**
 * Plain PHP templates with escape-by-default semantics.
 *
 * Values passed to render() are HTML-escaped recursively before the template
 * sees them, so `<?= $name ?>` is safe without ceremony. To emit markup,
 * wrap it in Raw.
 *
 * Escaping here is for HTML text and quoted attribute contexts. A value
 * interpolated into a URL still needs rawurlencode, and one interpolated into
 * inline JavaScript still needs json_encode — escaping is not a substitute for
 * getting the context right.
 */
final class Template
{
    public function __construct(private readonly string $directory)
    {
    }

    /** @param array<string, mixed> $vars */
    public function render(string $name, array $vars = []): string
    {
        return $this->renderFile($name, $this->escape($vars));
    }

    /** @param array<string, mixed> $vars */
    private function renderFile(string $name, array $escaped): string
    {
        if (preg_match('/^[a-z0-9_\-\/]+$/i', $name) !== 1 || str_contains($name, '..')) {
            throw new RuntimeException(sprintf('Unsafe template name "%s".', $name));
        }

        $file = $this->directory . '/' . $name . '.php';
        if (!is_file($file)) {
            throw new RuntimeException(sprintf('Template "%s" not found.', $name));
        }

        // Give templates a renderer so they can compose partials.
        $escaped['view'] = $this;

        ob_start();
        try {
            (static function (string $__file, array $__vars): void {
                extract($__vars, EXTR_SKIP);
                require $__file;
            })($file, $escaped);
        } catch (Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        return (string) ob_get_clean();
    }

    /**
     * Renders a partial from inside another template, like Plates' insert().
     *
     * The values are not escaped again: they come from the including
     * template, whose own variables were already escaped by render(), and
     * literal strings written in a template are trusted markup.
     *
     * @param array<string, mixed> $vars
     */
    public function partial(string $name, array $vars = []): Raw
    {
        return new Raw($this->renderFile($name, $vars));
    }

    private function escape(mixed $value): mixed
    {
        if ($value instanceof Raw) {
            return $value->html;
        }

        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        }

        if (is_array($value)) {
            return array_map($this->escape(...), $value);
        }

        // Database records become plain objects with escaped values, so
        // `$client->name` stays safe in templates. Views only read their
        // properties.
        if ($value instanceof ORM) {
            return new Record(array_map($this->escape(...), $value->as_array()));
        }

        // int, float, bool, null and other objects (e.g. Template) pass through.
        return $value;
    }
}
