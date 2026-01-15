<?php

namespace Ndx\SimpleRedirect\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ndx\SimpleRedirect\Facades\Redirect;
use Statamic\Http\Controllers\CP\CpController;

class RedirectController extends CpController
{
    public function index()
    {
        $this->authorize('manage redirects');

        return view('simple-redirects::index', [
            'redirects' => Redirect::ordered(),
        ]);
    }

    public function create()
    {
        $this->authorize('manage redirects');

        return view('simple-redirects::create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage redirects');

        $validated = $request->validate([
            'source'      => 'required|string',
            'destination' => 'required|string',
            'type'        => 'required|in:exact,regex',
            'status_code' => 'required|in:301,302,410',
            'enabled'     => 'sometimes|boolean',
        ]);

        $redirect = Redirect::make()
            ->source($validated['source'])
            ->destination($validated['destination'])
            ->type($validated['type'])
            ->statusCode((int) $validated['status_code'])
            ->enabled($validated['enabled'] ?? true);

        Redirect::save($redirect);

        return redirect()
            ->cpRoute('simple-redirects.index')
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
            'enabled'     => 'sometimes|boolean',
        ]);

        $redirect
            ->source($validated['source'])
            ->destination($validated['destination'])
            ->type($validated['type'])
            ->statusCode((int) $validated['status_code'])
            ->enabled($validated['enabled'] ?? true);

        Redirect::save($redirect);

        return redirect()
            ->cpRoute('simple-redirects.index')
            ->with('success', __('simple-redirects::messages.redirect_updated'));
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('manage redirects');

        $redirect = Redirect::find($id);

        if (! $redirect) {
            abort(404);
        }

        Redirect::delete($redirect);

        return redirect()
            ->cpRoute('simple-redirects.index')
            ->with('success', __('simple-redirects::messages.redirect_deleted'));
    }
}
