<?php

namespace Ndx\SimpleRedirect\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Ndx\SimpleRedirect\Blueprints\RedirectBlueprint;
use Ndx\SimpleRedirect\Contracts\QueriesRedirects;
use Ndx\SimpleRedirect\Contracts\Redirect as RedirectContract;
use Ndx\SimpleRedirect\Contracts\RedirectRepository;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Http\Resources\Redirects;
use Statamic\Contracts\Query\Builder;
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
            'columns'        => $this->listingColumns()->setPreferred('simple-redirects.columns')->values(),
            'filters'        => $this->listingFilters(),
            'redirectsCount' => $this->redirectQuery()->count(),
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

        $validated = $request->validate([
            'order'   => 'required|array',
            'page'    => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        $order   = collect($validated['order'])->map(fn ($id) => (string) $id);
        $current = Redirect::ordered()->map(fn (RedirectContract $redirect) => (string) $redirect->id())->values();
        $perPage = $this->perPage($request);
        $offset  = ($validated['page'] - 1) * $perPage;

        if (! $this->isSameSet($order, $current->slice($offset, $perPage))) {
            abort(409, __('simple-redirects::messages.reorder_out_of_date'));
        }

        $current->splice($offset, $order->count(), $order->all());

        app(RedirectRepository::class)->reorder($current->all());

        return response()->json(['success' => true]);
    }

    protected function isSameSet(Collection $submitted, Collection $existing): bool
    {
        return $submitted->sort()->values()->all() === $existing->sort()->values()->all();
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
        $query              = $this->redirectQuery();
        $search             = $request->input('search');
        $activeFilterBadges = [];

        if (! $request->boolean('reordering')) {
            $filters            = collect($request->filters)->only($this->listingFilters()->map->handle()->all());
            $activeFilterBadges = $this->queryFilters($query, $filters);

            if (filled($search)) {
                $query->where(fn ($query) => $query
                    ->where('source', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%"));
            }
        }

        $redirects = $query->paginate($this->perPage($request), ['*'], 'page', max(1, (int) $request->input('page', 1)));

        $payload = $this->runHooksWith('listing-redirects', [
            'redirects' => $redirects->getCollection()->map(fn (RedirectContract $redirect) => $this->toListingRow($redirect))->values(),
            'columns'   => $this->listingColumns(),
        ]);

        $payload->columns->setPreferred('simple-redirects.columns');

        $paginator = new LengthAwarePaginator($payload->redirects->values(), $redirects->total(), $redirects->perPage(), $redirects->currentPage());

        return (new Redirects($paginator))
            ->columns($payload->columns)
            ->additional(['meta' => ['activeFilterBadges' => $activeFilterBadges]])
            ->response();
    }

    protected function redirectQuery(): Builder
    {
        $repository = app(RedirectRepository::class);

        if (! $repository instanceof QueriesRedirects) {
            return (new ItemQueryBuilder)->withItems($repository->ordered()->values());
        }

        return $repository->query();
    }

    protected function listingFilters(): Collection
    {
        return Scope::filters('simple-redirects');
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
