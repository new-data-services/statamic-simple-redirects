<?php

namespace Ndx\SimpleRedirect\Data;

use Statamic\Data\AbstractAugmented;

class AugmentedRedirect extends AbstractAugmented
{
    public function keys(): array
    {
        return ['id', 'source', 'destination', 'regex', 'status_code', 'enabled', 'sites'];
    }
}
