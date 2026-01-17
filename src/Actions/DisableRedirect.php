<?php

namespace Ndx\SimpleRedirect\Actions;

use Ndx\SimpleRedirect\Contracts\Redirect;
use Ndx\SimpleRedirect\Facades\Redirect as RedirectFacade;
use Statamic\Actions\Action;

class DisableRedirect extends Action
{
    protected $confirm = false;

    protected $icon = 'eye-slash';

    public static function title()
    {
        return __('Disable');
    }

    public function visibleTo($item)
    {
        return $item instanceof Redirect && $item->isEnabled();
    }

    public function visibleToBulk($items)
    {
        return $items->filter(fn ($item) => $item->isEnabled())->isNotEmpty();
    }

    public function authorize($user, $item)
    {
        return $user->can('manage redirects');
    }

    public function run($items, $values)
    {
        $items->each(function ($item) {
            $item->enabled(false);
            RedirectFacade::save($item);
        });

        return trans_choice('Redirect disabled|Redirects disabled', $items->count());
    }
}
