<?php

namespace Invue\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TableQuery
{
    protected array $searchable = [];

    protected array $sortable = [];

    protected array $filterable = [];

    protected ?string $defaultSortColumn = null;

    protected string $defaultSortDirection = 'asc';

    protected int $defaultPerPage = 15;

    protected function __construct(protected Builder $query) {}

    public static function for(Builder $query): static
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

        return [
            'data' => $paginator->items(),
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
}
