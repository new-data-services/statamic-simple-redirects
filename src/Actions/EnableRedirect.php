<?php

namespace Ndx\SimpleRedirect\Actions;

use Ndx\SimpleRedirect\Contracts\Redirect;
use Ndx\SimpleRedirect\Facades\Redirect as RedirectFacade;
use Statamic\Actions\Action;

class EnableRedirect extends Action
{
    protected $confirm = false;

    protected $icon = 'eye';

    public static function title()
    {
        return __('Enable');
    }

    public function visibleTo($item)
    {
        return $item instanceof Redirect && ! $item->isEnabled();
    }

    public function visibleToBulk($items)
    {
        if ($items->whereInstanceOf(Redirect::class)->count() !== $items->count()) {
            return false;
        }

        return $items->filter(fn ($item) => ! $item->isEnabled())->isNotEmpty();
    }

    public function authorize($user, $item)
    {
        return $user->can('manage redirects');
    }

    public function run($items, $values)
    {
        $items->each(function ($item) {
            $item->enabled(true);
            RedirectFacade::save($item);
        });

        return trans_choice('Redirect enabled|Redirects enabled', $items->count());
    }
}
