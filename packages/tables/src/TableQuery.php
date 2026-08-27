<?php

namespace Invue\Tables;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TableQuery
{
    protected array $searchable = [];

    protected array $sortable = [];

    protected array $filterable = [];

    protected ?string $defaultSortColumn = null;

    protected string $defaultSortDirection = 'asc';

    protected int $defaultPerPage = 15;

    /** @var array<string, \Closure(\Illuminate\Database\Eloquent\Model): bool> */
    protected array $authorizationChecks = [];

    protected function __construct(protected BuilderContract $query) {}

    /**
     * Accepts a plain Eloquent `Builder` or a `Relation` (`$post->comments()`
     * — `Relation` implements this same contract) — a `Relation` already
     * carries its own parent-scoping `where` clause, so
     * `TableQuery::for($post->comments())` is the entire backend for a
     * relation manager's table. This is the "no PHP UI builder" boundary
     * again: TableQuery still never decides how the related records
     * render, only which ones the query returns.
     */
    public static function for(BuilderContract $query): static
    {
        return new static($query);
    }

    public function searchable(array $columns): static
    {
        $this->searchable = $columns;

        return $this;
    }

    public function sortable(array $columns): static
    {
        $this->sortable = $columns;

        return $this;
    }

    public function filterable(array $columns): static
    {
        $this->filterable = $columns;

        return $this;
    }

    public function defaultSort(string $column, string $direction = 'asc'): static
    {
        $this->defaultSortColumn = $column;
        $this->defaultSortDirection = $direction === 'desc' ? 'desc' : 'asc';

        return $this;
    }

    public function defaultPerPage(int $perPage): static
    {
        $this->defaultPerPage = $perPage;

        return $this;
    }

    /**
     * Annotates every serialized row with a `_can` map — the row-level
     * permission data an `ActionsColumn`'s `visible` (or a bulk action's)
     * reacts to. PHP stays the one place that knows about Policies/Gates;
     * Vue only ever reads the resulting booleans, same "data, not UI"
     * boundary every other Invue package keeps.
     *
     * @param  array<string, \Closure(\Illuminate\Database\Eloquent\Model): bool>  $checks
     */
    public function authorize(array $checks): static
    {
        $this->authorizationChecks = $checks;

        return $this;
    }

    /**
     * @return array{data: array, meta: array}
     */
    public function paginate(Request $request): array
    {
        $query = clone $this->query;

        $search = trim((string) $request->input('search', ''));

        if ($search !== '' && $this->searchable !== []) {
            $query->where(function (Builder $query) use ($search): void {
                foreach ($this->searchable as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $requestedSort = $request->input('sort');
        $sort = in_array($requestedSort, $this->sortable, true)
            ? $requestedSort
            : $this->defaultSortColumn;

        $requestedDirection = $request->input('direction');
        $direction = in_array($requestedDirection, ['asc', 'desc'], true) ? $requestedDirection : $this->defaultSortDirection;

        if ($sort !== null) {
            $query->orderBy($sort, $direction);
        }

        $filters = [];

        foreach ((array) $request->input('filters', []) as $column => $value) {
            if (! in_array($column, $this->filterable, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $filters[$column] = $value;

            if (is_array($value)) {
                $query->whereIn($column, $value);
            } elseif ($value === 'true' || $value === 'false') {
                $query->where($column, $value === 'true');
            } else {
                $query->where($column, $value);
            }
        }

        $perPage = (int) $request->input('per_page', $this->defaultPerPage);
        $perPage = $perPage > 0 ? $perPage : $this->defaultPerPage;

        $paginator = $query->paginate($perPage)->withQueryString();

        $data = $this->authorizationChecks === []
            ? $paginator->items()
            : array_map(
                fn (Model $model) => [...$model->toArray(), '_can' => $this->resolveCan($model)],
                $paginator->items(),
            );

        return [
            'data' => $data,
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'filters' => $filters,
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    protected function resolveCan(Model $model): array
    {
        return array_map(fn (\Closure $check) => (bool) $check($model), $this->authorizationChecks);
    }
}
