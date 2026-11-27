<?php

namespace Ndx\SimpleRedirect\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Contracts\FindsRedirectCandidates;
use Ndx\SimpleRedirect\Contracts\QueriesRedirects;
use Ndx\SimpleRedirect\Contracts\Redirect;
use Ndx\SimpleRedirect\Contracts\RedirectRepository as RedirectRepositoryContract;
use Ndx\SimpleRedirect\Data\Redirect as RedirectData;
use Ndx\SimpleRedirect\Events\RedirectDeleted;
use Ndx\SimpleRedirect\Events\RedirectSaved;
use Ndx\SimpleRedirect\Models\Redirect as RedirectModel;
use Ndx\SimpleRedirect\Query\EloquentRedirectQueryBuilder;
use Statamic\Facades\Blink;

class EloquentRedirectRepository implements FindsRedirectCandidates, QueriesRedirects, RedirectRepositoryContract
{
    public function all(): Collection
    {
        return $this->modelQuery()
            ->get()
            ->map(fn (RedirectModel $model) => RedirectData::fromModel($model));
    }

    public function find(string $id): ?Redirect
    {
        return Blink::once("simple-redirect-{$id}", function () use ($id) {
            $model = $this->modelQuery()->find($id);

            return $model ? RedirectData::fromModel($model) : null;
        });
    }

    public function enabled(): Collection
    {
        return $this->modelQuery()
            ->where('enabled', true)
            ->get()
            ->map(fn (RedirectModel $model) => RedirectData::fromModel($model));
    }

    public function ordered(): Collection
    {
        return $this->modelQuery()
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(fn (RedirectModel $model) => RedirectData::fromModel($model));
    }

    public function orderedEnabled(): Collection
    {
        return Blink::once('simple-redirects-ordered-enabled', function () {
            return $this->modelQuery()
                ->where('enabled', true)
                ->orderBy('order')
                ->orderBy('id')
                ->get()
                ->map(fn (RedirectModel $model) => RedirectData::fromModel($model));
        });
    }

    public function orderedEnabledCandidates(array $urls): Collection
    {
        $query = $this->modelQuery();

        $lookupKeys = collect($urls)
            ->map(fn (string $url) => RedirectData::lookupKey($url))
            ->flatMap(fn (string $lookupKey) => [$lookupKey, ltrim($lookupKey, '/')])
            ->unique()
            ->values();

        $source = $query->getQuery()->getGrammar()->wrap('source');

        return $query
            ->where('enabled', true)
            ->where(fn (Builder $query) => $query
                ->where('regex', true)
                ->orWhere('source', 'like', '%*%')
                ->orWhereIn(new Expression("lower({$source})"), $lookupKeys))
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(fn (RedirectModel $model) => RedirectData::fromModel($model));
    }

    public function save(Redirect $redirect): bool
    {
        if ($redirect->order() === null) {
            $maxOrder = $this->modelQuery()->max('order') ?? -1;
            $redirect->order($maxOrder + 1);
        }

        $model = $redirect->toModel();
        $model->save();

        $redirect->model($model);

        Blink::forget("simple-redirect-{$redirect->id()}");
        Blink::forget('simple-redirects-ordered-enabled');

        RedirectSaved::dispatch($redirect);

        return true;
    }

    public function delete(Redirect $redirect): bool
    {
        $this->modelQuery()->where('id', $redirect->id())->delete();

        Blink::forget("simple-redirect-{$redirect->id()}");
        Blink::forget('simple-redirects-ordered-enabled');

        RedirectDeleted::dispatch($redirect);

        return true;
    }

    public function make(): Redirect
    {
        return new RedirectData;
    }

    public function reorder(array $ids): void
    {
        $currentOrder = $this->modelQuery()->pluck('order', 'id');

        $this->modelQuery()->getConnection()->transaction(function () use ($ids, $currentOrder) {
            foreach ($ids as $order => $id) {
                if ($currentOrder->get($id) === $order) {
                    continue;
                }

                $this->modelQuery()->where('id', $id)->update(['order' => $order]);
            }
        });

        Blink::forget('simple-redirects-ordered-enabled');
    }

    public function query(): EloquentRedirectQueryBuilder
    {
        return app(EloquentRedirectQueryBuilder::class)->orderBy('order')->orderBy('id');
    }

    protected function modelQuery(): Builder
    {
        return RedirectModel::query();
    }
}
