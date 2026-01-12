<?php

namespace Ndx\SimpleRedirect\Data;

use Statamic\Data\AbstractAugmented;

class AugmentedRedirect extends AbstractAugmented
{
    public function keys()
    {
        return ['id', 'source', 'destination', 'type', 'status_code', 'site'];
    }
}
