<?php

namespace Ndx\SimpleRedirect\Events;

use Ndx\SimpleRedirect\Contracts\Redirect;
use Statamic\Events\Event;

class RedirectMatched extends Event
{
    public function __construct(
        public Redirect $redirect,
        public string $matchedUrl,
        public string $destination,
    ) {}
}
