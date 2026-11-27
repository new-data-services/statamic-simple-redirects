<?php

namespace Ndx\SimpleRedirect\Contracts;

use Statamic\Contracts\Query\Builder;

interface QueriesRedirects
{
    /**
     * Query over all redirects in match order.
     */
    public function query(): Builder;
}
