<?php

namespace Ndx\SimpleRedirect\Stache\Indexes;

use Ndx\SimpleRedirect\Data\Redirect;
use Statamic\Stache\Indexes\Value;

class LookupKey extends Value
{
    public function getItemValue($item): ?string
    {
        if ($item->isPattern() || $item->source() === null) {
            return null;
        }

        return Redirect::lookupKey($item->source());
    }
}
