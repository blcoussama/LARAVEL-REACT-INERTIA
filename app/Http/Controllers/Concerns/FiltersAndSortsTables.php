<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * Filtering and sorting of the index tables, driven by the query string.
 *
 * Column names cannot be bound as SQL parameters, so the sort column is
 * checked against an explicit allow-list and the direction is normalised:
 * an unknown value silently falls back to the default instead of reaching
 * the database (no sorting on "password", no HTTP 500 on a bad direction).
 */
trait FiltersAndSortsTables
{
    /**
     * @param  list<string>  $sortable
     */
    protected function applySort(Builder $query, array $sortable, string $default = 'created_at'): void
    {
        $field = request()->query('sort_field');
        $direction = request()->query('sort_direction');

        if (! is_string($field) || ! in_array($field, $sortable, true)) {
            $field = $default;
        }

        $direction = is_string($direction) && strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($field, $direction);

        // Stable order between pages when several rows share the same value.
        if ($field !== 'id') {
            $query->orderBy('id', $direction);
        }
    }

    /**
     * "LIKE" filter on a text column; ignored when the parameter is missing,
     * empty or not a plain string (e.g. ?name[]=x).
     */
    protected function applyContainsFilter(Builder $query, string $parameter, ?string $column = null): void
    {
        $value = $this->stringQuery($parameter);

        if ($value !== null) {
            $query->where($column ?? $parameter, 'like', '%'.$value.'%');
        }
    }

    /**
     * Exact-match filter restricted to a list of allowed values.
     *
     * @param  list<string>  $allowed
     */
    protected function applyEnumFilter(Builder $query, string $parameter, array $allowed, ?string $column = null): void
    {
        $value = $this->stringQuery($parameter);

        if ($value !== null && in_array($value, $allowed, true)) {
            $query->where($column ?? $parameter, $value);
        }
    }

    protected function stringQuery(string $parameter): ?string
    {
        $value = request()->query($parameter);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }

    /**
     * Query string sent back to the page (only scalar values).
     *
     * @return array<string, string>|null
     */
    protected function tableQueryParams(): ?array
    {
        $params = array_filter(request()->query(), 'is_string');

        return $params ?: null;
    }
}
