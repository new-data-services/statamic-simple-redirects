<?php

namespace Ndx\SimpleRedirect\Events;

use Illuminate\Http\Request;
use Ndx\SimpleRedirect\Contracts\Redirect;
use Statamic\Events\Event;

class RedirectMatched extends Event
{
    public function __construct(
        public Redirect $redirect,
        public Request $request,
        public string $matchedUrl,
        public string $destination,
    ) {}
}
