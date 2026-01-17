<?php

namespace Ndx\SimpleRedirect\Http\Controllers;

use Ndx\SimpleRedirect\Facades\Redirect;
use Statamic\Http\Controllers\CP\ActionController;

class RedirectActionController extends ActionController
{
    protected function getSelectedItems($items, $context)
    {
        return $items->map(fn ($id) => Redirect::find($id))->filter();
    }
}
