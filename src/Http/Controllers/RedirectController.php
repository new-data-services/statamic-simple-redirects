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
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;

class RedirectController extends CpController
{
    public function index(): Response
    {
        $this->authorize('manage redirects');

        $redirects = Redirect::ordered()->map(fn ($redirect) => [
            'id'          => $redirect->id(),
            'source'      => $redirect->source(),
            'destination' => $redirect->destination(),
            'regex'       => $redirect->isRegex(),
            'status_code' => $redirect->statusCode(),
            'enabled'     => $redirect->isEnabled(),
            'sites'       => $this->formatSitesForListing($redirect->sites()),
            'edit_url'    => cp_route('simple-redirects.edit', $redirect->id()),
        ])->values();

        $columns = array_values(array_filter([
            ['field' => 'source', 'label' => __('simple-redirects::fields.source.title'), 'visible' => true, 'defaultVisibility' => true, 'defaultOrder' => 1],
            ['field' => 'destination', 'label' => __('simple-redirects::fields.destination.title'), 'visible' => true, 'defaultVisibility' => true, 'defaultOrder' => 2],
            Site::multiEnabled() ? ['field' => 'sites', 'label' => __('simple-redirects::fields.sites.title'), 'visible' => false, 'defaultVisibility' => false, 'defaultOrder' => 3] : null,
            ['field' => 'regex', 'label' => __('simple-redirects::fields.regex.title'), 'visible' => true, 'defaultVisibility' => true, 'defaultOrder' => 4],
            ['field' => 'status_code', 'label' => __('Code'), 'visible' => true, 'defaultVisibility' => true, 'defaultOrder' => 5],
        ]));

        return Inertia::render('simple-redirects::Index', [
            'title'      => __('simple-redirects::messages.redirects'),
            'redirects'  => $redirects,
            'columns'    => $columns,
            'createUrl'  => cp_route('simple-redirects.create'),
            'reorderUrl' => cp_route('simple-redirects.reorder'),
            'actionUrl'  => cp_route('simple-redirects.actions.run'),
            'exportUrl'  => cp_route('simple-redirects.export'),
            'importUrl'  => cp_route('simple-redirects.import'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manage redirects');

        $blueprint = (new RedirectBlueprint)();
        $fields    = $blueprint->fields()->preProcess();

        return Inertia::render('simple-redirects::Publish', [
            'title'            => __('simple-redirects::messages.create_redirect'),
            'icon'             => 'moved',
            'blueprint'        => $blueprint->toPublishArray(),
            'values'           => $fields->values()->all(),
            'meta'             => $fields->meta()->all(),
            'submitUrl'        => cp_route('simple-redirects.store'),
            'listingUrl'       => cp_route('simple-redirects.index'),
            'createAnotherUrl' => cp_route('simple-redirects.create'),
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
            'sites'       => $redirect->sites(),
        ];

        $fields = $blueprint->fields()->addValues($values)->preProcess();

        return Inertia::render('simple-redirects::Publish', [
            'title'            => __('simple-redirects::messages.edit_redirect'),
            'icon'             => 'moved',
            'blueprint'        => $blueprint->toPublishArray(),
            'values'           => $fields->values()->all(),
            'meta'             => $fields->meta()->all(),
            'submitUrl'        => cp_route('simple-redirects.update', $id),
            'listingUrl'       => cp_route('simple-redirects.index'),
            'createAnotherUrl' => cp_route('simple-redirects.create'),
            'deleteUrl'        => cp_route('simple-redirects.destroy', $id),
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
            ->enabled($values['enabled'] ?? true)
            ->sites($values['sites'] ?? null);
    }

    protected function jsonResponse(RedirectContract $redirect): JsonResponse
    {
        return response()->json([
            'saved'    => true,
            'redirect' => cp_route('simple-redirects.edit', $redirect->id()),
            'data'     => [
                'id'     => $redirect->id(),
                'values' => [
                    'source'      => $redirect->source(),
                    'destination' => $redirect->destination(),
                    'regex'       => $redirect->isRegex(),
                    'status_code' => (string) $redirect->statusCode(),
                    'enabled'     => $redirect->isEnabled(),
                    'sites'       => $redirect->sites(),
                ],
                'extraValues' => [],
            ],
        ]);
    }

    protected function formatSitesForListing(?array $sites): string
    {
        $allSites = Site::all();

        if (empty($sites) || count($sites) === $allSites->count()) {
            return $allSites->map(fn ($site) => $site->name())->implode(', ');
        }

        return $allSites
            ->only($sites)
            ->map(fn ($site) => $site->name())
            ->implode(', ');
    }
}
