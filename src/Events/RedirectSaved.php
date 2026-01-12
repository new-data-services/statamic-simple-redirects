<?php

namespace Ndx\SimpleRedirect\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ndx\SimpleRedirect\Contracts\Redirect;

class RedirectSaved
{
    use Dispatchable;

    public function __construct(
        public Redirect $redirect
    ) {}
}
