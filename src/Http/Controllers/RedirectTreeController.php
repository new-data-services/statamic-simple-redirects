<?php

namespace Ndx\SimpleRedirect\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ndx\SimpleRedirect\Data\RedirectTree;
use Ndx\SimpleRedirect\Events\RedirectTreeSaved;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;

class RedirectTreeController extends CpController
{
    public function update(Request $request): JsonResponse
    {
        $this->authorize('manage redirects');

        $site = $request->input('site', Site::default()->handle());
        $tree = $request->input('tree', []);

        $redirectTree = RedirectTree::find($site);
        $redirectTree->setTree($tree);
        $redirectTree->save();

        event(new RedirectTreeSaved($redirectTree));

        return response()->json([
            'message' => __('simple-redirects::messages.redirects_reordered'),
        ]);
    }
}
