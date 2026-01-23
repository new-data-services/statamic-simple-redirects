<?php

namespace Ndx\SimpleRedirect\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ndx\SimpleRedirect\Contracts\Redirect;
use Statamic\Contracts\Git\ProvidesCommitMessage;

class RedirectSaved implements ProvidesCommitMessage
{
    use Dispatchable;

    public function __construct(
        public Redirect $redirect
    ) {}

    public function commitMessage(): string
    {
        return __('simple-redirects::messages.redirect_saved');
    }
}
