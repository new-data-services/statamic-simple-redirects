<?php

namespace Ndx\SimpleRedirect\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ndx\SimpleRedirect\Data\RedirectTree;
use Statamic\Contracts\Git\ProvidesCommitMessage;

class RedirectTreeSaved implements ProvidesCommitMessage
{
    use Dispatchable;

    public function __construct(
        public RedirectTree $tree
    ) {}

    public function commitMessage(): string
    {
        return __('simple-redirects::messages.redirects_reordered');
    }
}
