<?php

namespace Ndx\SimpleRedirect\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ndx\SimpleRedirect\Data\RedirectTree;
use Ndx\SimpleRedirect\Events\RedirectTreeSaved;
use Statamic\Http\Controllers\CP\CpController;

class RedirectTreeController extends CpController
{
    public function update(Request $request): JsonResponse
    {
        $this->authorize('manage redirects');

        $tree = $request->input('tree', []);

        $redirectTree = RedirectTree::instance();
        $redirectTree->setTree($tree);
        $redirectTree->save();

        event(new RedirectTreeSaved($redirectTree));

        return response()->json([
            'message' => __('simple-redirects::messages.redirects_reordered'),
        ]);
    }
}
