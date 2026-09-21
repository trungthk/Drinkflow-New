<?php

declare(strict_types=1);

namespace App\Exports\Concerns;

/**
 * Prevents CSV formula injection: spreadsheet applications execute cells that begin with = + - @ (or a tab/CR),
 * so user-influenced text is prefixed with an apostrophe, which they render as a literal text marker.
 */
trait NeutralizesFormulas
{
    /**
     * Neutralize a single exported value.
     *
     * @param mixed $value Raw cell value.
     * @return mixed The value, prefixed with an apostrophe when it could be interpreted as a formula.
     */
    protected static function neutralizeCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    /**
     * Neutralize every value of an exported row.
     *
     * @param array<int|string, mixed> $row Raw row values.
     * @return array<int|string, mixed> Row safe to write into a CSV file.
     */
    protected static function neutralizeRow(array $row): array
    {
        return array_map(static fn (mixed $value): mixed => self::neutralizeCell($value), $row);
    }
}
