<?php

declare(strict_types=1);

namespace Rocks\View;

/**
 * A database record as a template sees it: its columns, already escaped.
 *
 * Reading a column the record doesn't have gives null rather than a warning,
 * as it did when templates were handed Idiorm objects directly.
 */
final class Record
{
    /** @param array<string, mixed> $fields */
    public function __construct(private readonly array $fields)
    {
    }

    public function __get(string $name): mixed
    {
        return $this->fields[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->fields[$name]);
    }
}
