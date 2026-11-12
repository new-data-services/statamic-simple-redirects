<?php

namespace Ndx\SimpleRedirect\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Statamic\CP\Columns;
use Statamic\Http\Resources\CP\Concerns\HasRequestedColumns;

class Redirects extends ResourceCollection
{
    use HasRequestedColumns;

    protected Columns $columns;

    public function columns(Columns $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return $this->collection->all();
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'columns' => $this->visibleColumns(),
            ],
        ];
    }
}
