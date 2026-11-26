<?php

namespace Ndx\SimpleRedirect\Contracts;

use Illuminate\Support\Collection;

interface FindsRedirectCandidates
{
    /**
     * Enabled redirects in match order. Superset of all matches for $urls.
     *
     * @param  array<int, string>  $urls
     * @return Collection<int, Redirect>
     */
    public function orderedEnabledCandidates(array $urls): Collection;
}
