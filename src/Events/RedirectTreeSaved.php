<?php

namespace Ndx\SimpleRedirect\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ndx\SimpleRedirect\Data\RedirectTree;

class RedirectTreeSaved
{
    use Dispatchable;

    public function __construct(
        public RedirectTree $tree
    ) {}
}
