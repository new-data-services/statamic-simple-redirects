<?php

namespace Ndx\SimpleRedirect\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Ndx\SimpleRedirect\Contracts\Redirect as RedirectContract;
use Ndx\SimpleRedirect\Contracts\RedirectRepository as RedirectRepositoryContract;

/**
 * @method static Collection all()
 * @method static RedirectContract|null find(string $id)
 * @method static Collection enabled()
 * @method static Collection ordered()
 * @method static Collection orderedEnabled()
 * @method static bool save(RedirectContract $redirect)
 * @method static bool delete(RedirectContract $redirect)
 * @method static RedirectContract make()
 */
class Redirect extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RedirectRepositoryContract::class;
    }
}
