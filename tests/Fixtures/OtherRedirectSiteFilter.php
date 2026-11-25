<?php

namespace Ndx\SimpleRedirect\Tests\Fixtures;

use Statamic\Query\Scopes\Filter;

class OtherRedirectSiteFilter extends Filter
{
    protected static $handle = 'other_redirect_site';

    public function visibleTo($key)
    {
        return $key === 'redirects';
    }

    public function autoApply()
    {
        return ['site' => 'de'];
    }

    public function apply($query, $values)
    {
        $query->where('site', $values['site']);
    }
}
