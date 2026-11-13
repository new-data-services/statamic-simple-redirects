<?php

namespace Ndx\SimpleRedirect\Scopes;

use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Blueprints\RedirectBlueprint;
use Statamic\Query\Scopes\Filters\Fields;

class RedirectFields extends Fields
{
    public function visibleTo($key)
    {
        return $key === 'redirects';
    }

    protected function getBlueprints(): Collection
    {
        return collect([(new RedirectBlueprint)()]);
    }

    protected function getFields(): Collection
    {
        return parent::getFields()->only(['source', 'destination', 'status_code', 'regex']);
    }
}
