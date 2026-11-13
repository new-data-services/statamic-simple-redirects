<?php

namespace Ndx\SimpleRedirect\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Ndx\SimpleRedirect\Blueprints\RedirectBlueprint;
use Ndx\SimpleRedirect\Contracts\Redirect as RedirectContract;
use Ndx\SimpleRedirect\Contracts\RedirectRepository;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Http\Resources\Redirects;
use Statamic\CP\Column;
use Statamic\CP\Columns;
use Statamic\Extensions\Pagination\LengthAwarePaginator;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\ItemQueryBuilder;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\Statamic;
use Statamic\Support\Traits\Hookable;

class RedirectController extends CpController
{
    use Hookable, QueriesFilters;

    public function index(FilteredRequest $request): Response|JsonResponse
    {
        $this->authorize('manage redirects');

        if ($request->wantsJson()) {
            return $this->listing($request);
        }

        return Inertia::render('simple-redirects::Index', [
            'title'          => __('simple-redirects::messages.redirects'),
            'listingUrl'     => cp_route('simple-redirects.index'),
            'filters'        => Scope::filters('redirects'),
            'redirectsCount' => Redirect::all()->count(),
            'createUrl'      => cp_route('simple-redirects.create'),
            'reorderUrl'     => cp_route('simple-redirects.reorder'),
            'actionUrl'      => cp_route('simple-redirects.actions.run'),
            'exportUrl'      => cp_route('simple-redirects.export'),
            'importUrl'      => cp_route('simple-redirects.import'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manage redirects');

        $values = is_array($request->values) ? $request->values : [];

        $values = $this->runHooksWith('creating-redirect', [
            'redirect' => Redirect::make(),
            'values'   => $values,
        ])->values ?? $values;

        $blueprint = (new RedirectBlueprint)();
        $fields    = $blueprint->fields()->addValues($values)->preProcess();

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

        $order = $request->validate(['order' => 'required|array'])['order'];

        if (! $this->isCompleteOrder($order)) {
            abort(409, __('simple-redirects::messages.reorder_out_of_date'));
        }

        app(RedirectRepository::class)->reorder($order);

        return response()->json(['success' => true]);
    }

    protected function isCompleteOrder(array $order): bool
    {
        $submitted = collect($order)->map(fn ($id) => (string) $id)->sort()->values()->all();
        $existing  = Redirect::all()->map(fn (RedirectContract $redirect) => (string) $redirect->id())->sort()->values()->all();

        return $submitted === $existing;
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

    protected function listing(FilteredRequest $request): JsonResponse
    {
        $reordering         = $request->boolean('reordering');
        $redirects          = Redirect::ordered()->values();
        $search             = $request->input('search');
        $activeFilterBadges = [];

        if (! $reordering) {
            $query              = (new ItemQueryBuilder)->withItems($redirects);
            $activeFilterBadges = $this->queryFilters($query, $request->filters);
            $redirects          = $query->get()->values();
        }

        if (! $reordering && filled($search)) {
            $redirects = $redirects->filter(fn (RedirectContract $redirect) => $this->matchesSearch($redirect, $search))->values();
        }

        $perPage = $reordering ? max(1, $redirects->count()) : $this->perPage($request);
        $page    = $reordering ? 1 : max(1, (int) $request->input('page', 1));

        $payload = $this->runHooksWith('listing-redirects', [
            'redirects' => $redirects->forPage($page, $perPage)->map(fn (RedirectContract $redirect) => $this->toListingRow($redirect))->values(),
            'columns'   => $this->listingColumns(),
        ]);

        $payload->columns->setPreferred('simple-redirects.columns');

        $paginator = new LengthAwarePaginator($payload->redirects->values(), $redirects->count(), $perPage, $page);

        return (new Redirects($paginator))
            ->columns($payload->columns)
            ->additional(['meta' => ['activeFilterBadges' => $activeFilterBadges]])
            ->response();
    }

    protected function matchesSearch(RedirectContract $redirect, string $search): bool
    {
        return Str::contains($redirect->source(), $search, ignoreCase: true)
            || Str::contains($redirect->destination(), $search, ignoreCase: true);
    }

    protected function perPage(Request $request): int
    {
        return Statamic::cpPerPage($request->input('perPage')) ?? config('statamic.cp.pagination_size');
    }

    protected function toListingRow(RedirectContract $redirect): array
    {
        return [
            'id'          => $redirect->id(),
            'source'      => $redirect->source(),
            'destination' => $redirect->destination(),
            'regex'       => $redirect->isRegex(),
            'status_code' => $redirect->statusCode(),
            'enabled'     => $redirect->isEnabled(),
            'sites'       => $this->formatSitesForListing($redirect->sites()),
            'edit_url'    => cp_route('simple-redirects.edit', $redirect->id()),
        ];
    }

    protected function listingColumns(): Columns
    {
        return new Columns(array_values(array_filter([
            Column::make('source')->label(__('simple-redirects::fields.source.title'))->defaultOrder(1),
            Column::make('destination')->label(__('simple-redirects::fields.destination.title'))->defaultOrder(2),
            Site::multiEnabled() ? Column::make('sites')->label(__('simple-redirects::fields.sites.title'))->visible(false)->defaultVisibility(false)->defaultOrder(3) : null,
            Column::make('regex')->label(__('simple-redirects::fields.regex.title'))->defaultOrder(4),
            Column::make('status_code')->label(__('Code'))->defaultOrder(5),
        ])));
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
