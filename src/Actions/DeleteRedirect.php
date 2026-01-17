<?php

namespace Ndx\SimpleRedirect\Actions;

use Ndx\SimpleRedirect\Contracts\Redirect;
use Ndx\SimpleRedirect\Facades\Redirect as RedirectFacade;
use Statamic\Actions\Action;

class DeleteRedirect extends Action
{
    protected $dangerous = true;

    protected $icon = 'trash';

    public static function title()
    {
        return __('Delete');
    }

    public function visibleTo($item)
    {
        return $item instanceof Redirect;
    }

    public function authorize($user, $item)
    {
        return $user->can('manage redirects');
    }

    public function buttonText()
    {
        /** @translation */
        return 'Delete|Delete :count redirects?';
    }

    public function confirmationText()
    {
        /** @translation */
        return 'Are you sure you want to delete this?|Are you sure you want to delete these :count redirects?';
    }

    public function run($items, $values)
    {
        $items->each(fn ($item) => RedirectFacade::delete($item));

        return trans_choice('Redirect deleted|Redirects deleted', $items->count());
    }
}
