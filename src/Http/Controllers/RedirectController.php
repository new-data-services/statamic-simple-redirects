<?php

namespace Ndx\SimpleRedirect\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Ndx\SimpleRedirect\Blueprints\RedirectBlueprint;
use Ndx\SimpleRedirect\Contracts\Redirect as RedirectContract;
use Ndx\SimpleRedirect\Contracts\RedirectRepository;
use Ndx\SimpleRedirect\Facades\Redirect;
use Statamic\Http\Controllers\CP\CpController;

class RedirectController extends CpController
{
    public function index(): Response
    {
        $this->authorize('manage redirects');

        $redirects = Redirect::ordered()->map(fn ($r) => [
            'id'          => $r->id(),
            'source'      => $r->source(),
            'destination' => $r->destination(),
            'regex'       => $r->isRegex(),
            'status_code' => $r->statusCode(),
            'enabled'     => $r->isEnabled(),
            'edit_url'    => cp_route('simple-redirects.edit', $r->id()),
        ])->values();

        return Inertia::render('simple-redirects::Index', [
            'title'      => __('Redirects'),
            'redirects'  => $redirects,
            'columns'    => [
                ['field' => 'source', 'label' => __('Source'), 'width' => '40%'],
                ['field' => 'destination', 'label' => __('Destination'), 'width' => '40%'],
                ['field' => 'regex', 'label' => '', 'width' => '10%'],
                ['field' => 'status_code', 'label' => __('Code'), 'width' => '10%'],
            ],
            'createUrl'  => cp_route('simple-redirects.create'),
            'reorderUrl' => cp_route('simple-redirects.reorder'),
            'actionUrl'  => cp_route('simple-redirects.actions.run'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manage redirects');

        $blueprint = (new RedirectBlueprint)();
        $fields    = $blueprint->fields()->preProcess();

        return Inertia::render('simple-redirects::Publish', [
            'title'            => __('Create Redirect'),
            'icon'             => 'moved',
            'blueprint'        => $blueprint->toPublishArray(),
            'values'           => $fields->values()->all(),
            'meta'             => $fields->meta()->all(),
            'submitUrl'        => cp_route('simple-redirects.store'),
            'listingUrl'       => cp_route('simple-redirects.index'),
            'createAnotherUrl' => cp_route('simple-redirects.create'),
            'editUrlTemplate'  => str_replace('__ID__', '{id}', cp_route('simple-redirects.edit', '__ID__')),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage redirects');

        $values   = $this->processFormData($request);
        $redirect = $this->hydrateRedirect(Redirect::make(), $values);

        Redirect::save($redirect);

        return $this->jsonResponse($redirect);
    }

    public function edit(string $id): Response
    {
        $this->authorize('manage redirects');

        $redirect = Redirect::find($id);

        if (! $redirect) {
            abort(404);
        }

        $blueprint = (new RedirectBlueprint)();

        $values = [
            'source'      => $redirect->source(),
            'destination' => $redirect->destination(),
            'regex'       => $redirect->isRegex(),
            'status_code' => (string) $redirect->statusCode(),
            'enabled'     => $redirect->isEnabled(),
        ];

        $fields = $blueprint->fields()->addValues($values)->preProcess();

        return Inertia::render('simple-redirects::Publish', [
            'title'            => __('Edit Redirect'),
            'icon'             => 'moved',
            'blueprint'        => $blueprint->toPublishArray(),
            'values'           => $fields->values()->all(),
            'meta'             => $fields->meta()->all(),
            'submitUrl'        => cp_route('simple-redirects.update', $id),
            'listingUrl'       => cp_route('simple-redirects.index'),
            'createAnotherUrl' => cp_route('simple-redirects.create'),
            'editUrlTemplate'  => str_replace('__ID__', '{id}', cp_route('simple-redirects.edit', '__ID__')),
            'isCreating'       => false,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->authorize('manage redirects');

        $redirect = Redirect::find($id);

        if (! $redirect) {
            abort(404);
        }

        $values = $this->processFormData($request);
        $this->hydrateRedirect($redirect, $values);

        Redirect::save($redirect);

        return $this->jsonResponse($redirect);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->authorize('manage redirects');

        $redirect = Redirect::find($id);

        if (! $redirect) {
            abort(404);
        }

        Redirect::delete($redirect);

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('manage redirects');

        $order = $request->input('order', []);

        app(RedirectRepository::class)->reorder($order);

        return response()->json(['success' => true]);
    }

    protected function processFormData(Request $request): array
    {
        $blueprint = (new RedirectBlueprint)();
        $fields    = $blueprint->fields()->addValues($request->all());
        $fields->validate();

        return $fields->process()->values()->all();
    }

    protected function hydrateRedirect(RedirectContract $redirect, array $values): RedirectContract
    {
        return $redirect
            ->regex($values['regex'] ?? false)
            ->source($values['source'])
            ->destination($values['destination'] ?? '')
            ->statusCode((int) $values['status_code'])
            ->enabled($values['enabled'] ?? true);
    }

    protected function jsonResponse(RedirectContract $redirect): JsonResponse
    {
        return response()->json([
            'saved' => true,
            'data'  => [
                'id'     => $redirect->id(),
                'values' => [
                    'source'      => $redirect->source(),
                    'destination' => $redirect->destination(),
                    'regex'       => $redirect->isRegex(),
                    'status_code' => (string) $redirect->statusCode(),
                    'enabled'     => $redirect->isEnabled(),
                ],
                'extraValues' => [],
            ],
        ]);
    }
}
