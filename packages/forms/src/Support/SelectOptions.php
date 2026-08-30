<?php

namespace Invue\Forms\Support;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Support\Str;

/**
 * Turns a query or a backed enum into the `[{ value, label }]` shape
 * Select's `options` prop — and a remote `url` endpoint's JSON response —
 * both expect. The entire backend a Select needs, same "no PHP UI
 * builder" boundary Invue\Tables\TableQuery keeps: this shapes data, it
 * never decides how Select renders it.
 */
class SelectOptions
{
    /**
     * For a small "lookup" table (roles, categories, statuses) you're happy
     * to preload in full — no limit is applied, so pass an already-limited
     * or already-paginated query for anything larger.
     *
     * @return list<array{value: int|string, label: string}>
     */
    public static function fromQuery(BuilderContract $query, string $valueColumn = 'id', string $labelColumn = 'name'): array
    {
        return $query->get([$valueColumn, $labelColumn])
            ->map(fn ($row) => ['value' => $row->{$valueColumn}, 'label' => (string) $row->{$labelColumn}])
            ->all();
    }

    /**
     * The whole backend for a Select's `url` prop — searches `$columns` with
     * a `LIKE`, capped at `$limit` (a remote-search endpoint with no cap is
     * the same "preload the world" mistake `fromQuery` leaves you free to
     * make on a query you control yourself; this one defaults away from it):
     *
     * Route::get('/users/search', fn (Request $request) => SelectOptions::search(
     *     User::query(), $request->query('search'), ['name', 'email'],
     * ));
     *
     * @param  list<string>  $columns
     * @return list<array{value: int|string, label: string}>
     */
    public static function search(
        BuilderContract $query,
        ?string $term,
        array $columns,
        string $valueColumn = 'id',
        string $labelColumn = 'name',
        int $limit = 50,
    ): array {
        if ($term !== null && $term !== '') {
            $query->where(function (BuilderContract $query) use ($term, $columns): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$term}%");
                }
            });
        }

        return static::fromQuery($query->limit($limit), $valueColumn, $labelColumn);
    }

    /**
     * A `label()` method on the enum wins when present — matches the
     * convention `spatie/laravel-typescript-transformer` itself documents
     * for enums that need a human-readable name alongside the value it
     * transforms to TypeScript. Falls back to a headline of the case name
     * (`InProgress` -> 'In Progress') for a plain enum with no `label()`.
     *
     * @param  class-string  $enumClass  a string- or int-backed enum
     * @return list<array{value: int|string, label: string}>
     */
    public static function fromEnum(string $enumClass): array
    {
        return array_map(
            fn ($case) => [
                'value' => $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : Str::headline($case->name),
            ],
            $enumClass::cases(),
        );
    }
}
