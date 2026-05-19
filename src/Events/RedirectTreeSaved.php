<?php

namespace Ndx\SimpleRedirect\Events;

use Ndx\SimpleRedirect\Data\RedirectTree;
use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;

class RedirectTreeSaved extends Event implements ProvidesCommitMessage
{
    public function __construct(
        public RedirectTree $tree
    ) {}

    public function commitMessage(): string
    {
        return __('simple-redirects::messages.redirects_reordered');
    }
}
