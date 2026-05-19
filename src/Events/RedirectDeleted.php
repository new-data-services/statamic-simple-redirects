<?php

namespace Ndx\SimpleRedirect\Events;

use Ndx\SimpleRedirect\Contracts\Redirect;
use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;

class RedirectDeleted extends Event implements ProvidesCommitMessage
{
    public function __construct(
        public Redirect $redirect
    ) {}

    public function commitMessage(): string
    {
        return __('simple-redirects::messages.redirect_deleted');
    }
}
