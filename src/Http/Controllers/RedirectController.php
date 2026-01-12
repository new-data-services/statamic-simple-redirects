<?php

namespace Ndx\SimpleRedirect\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ndx\SimpleRedirect\Data\RedirectTree;
use Ndx\SimpleRedirect\Facades\Redirect;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;

class RedirectController extends CpController
{
    public function index(Request $request)
    {
        $this->authorize('manage redirects');

        $site = $request->input('site', Site::default()->handle());

        $redirects = Redirect::findBySite($site);
        $tree      = RedirectTree::find($site);

        $orderedRedirects = collect($tree->tree())
            ->map(fn ($id) => $redirects->firstWhere('id', $id))
            ->filter()
            ->merge($redirects->whereNotIn('id', $tree->tree()))
            ->values();

        return view('simple-redirects::index', [
            'redirects'   => $orderedRedirects,
            'sites'       => Site::all(),
            'currentSite' => $site,
        ]);
    }

    public function create()
    {
        $this->authorize('manage redirects');

        return view('simple-redirects::create', [
            'sites' => Site::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage redirects');

        $validated = $request->validate([
            'source'      => 'required|string',
            'destination' => 'required|string',
            'type'        => 'required|in:exact,regex',
            'status_code' => 'required|in:301,302,410',
            'site'        => 'required|string',
        ]);

        $redirect = Redirect::make()
            ->source($validated['source'])
            ->destination($validated['destination'])
            ->type($validated['type'])
            ->statusCode((int) $validated['status_code'])
            ->site($validated['site']);

        Redirect::save($redirect);

        return redirect()
            ->cpRoute('simple-redirects.index', ['site' => $validated['site']])
            ->with('success', __('simple-redirects::messages.redirect_created'));
    }

    public function edit(string $id)
    {
        $this->authorize('manage redirects');

        $redirect = Redirect::find($id);

        if (! $redirect) {
            abort(404);
        }

        return view('simple-redirects::edit', [
            'redirect' => $redirect,
            'sites'    => Site::all(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorize('manage redirects');

        $redirect = Redirect::find($id);

        if (! $redirect) {
            abort(404);
        }

        $validated = $request->validate([
            'source'      => 'required|string',
            'destination' => 'required|string',
            'type'        => 'required|in:exact,regex',
            'status_code' => 'required|in:301,302,410',
            'site'        => 'required|string',
        ]);

        $redirect
            ->source($validated['source'])
            ->destination($validated['destination'])
            ->type($validated['type'])
            ->statusCode((int) $validated['status_code'])
            ->site($validated['site']);

        Redirect::save($redirect);

        return redirect()
            ->cpRoute('simple-redirects.index', ['site' => $validated['site']])
            ->with('success', __('simple-redirects::messages.redirect_updated'));
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('manage redirects');

        $redirect = Redirect::find($id);

        if (! $redirect) {
            abort(404);
        }

        $site = $redirect->site();

        Redirect::delete($redirect);

        return redirect()
            ->cpRoute('simple-redirects.index', ['site' => $site])
            ->with('success', __('simple-redirects::messages.redirect_deleted'));
    }
}
