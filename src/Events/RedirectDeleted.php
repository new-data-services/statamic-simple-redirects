<?php

namespace Ndx\SimpleRedirect\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ndx\SimpleRedirect\Contracts\Redirect;

class RedirectDeleted
{
    use Dispatchable;

    public function __construct(
        public Redirect $redirect
    ) {}
}
