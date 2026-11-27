<?php

namespace Ndx\SimpleRedirect\Query;

use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Data\Redirect;
use Statamic\Query\EloquentQueryBuilder;

class EloquentRedirectQueryBuilder extends EloquentQueryBuilder
{
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column === 'status_code' && is_string($operator) && strtolower($operator) === 'like') {
            return parent::where($column, '=', (int) trim((string) $value, '%'), $boolean);
        }

        return parent::where(...func_get_args());
    }

    public function whereJsonOverlaps($column, $values, $boolean = 'and')
    {
        return $this->where(function ($query) use ($column, $values) {
            foreach ((array) $values as $value) {
                $query->orWhereJsonContains($column, $value);
            }
        }, null, null, $boolean);
    }

    public function orWhereJsonOverlaps($column, $values)
    {
        return $this->whereJsonOverlaps($column, $values, 'or');
    }

    protected function transform($items, $columns = ['*']): Collection
    {
        return Collection::make($items)->map(fn ($model) => Redirect::fromModel($model));
    }

    protected function column($column)
    {
        return $column === 'is_enabled' ? 'enabled' : $column;
    }
}
