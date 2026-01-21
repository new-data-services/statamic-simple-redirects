<?php

namespace Ndx\SimpleRedirect\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Contracts\Redirect;
use Ndx\SimpleRedirect\Contracts\RedirectRepository as RedirectRepositoryContract;
use Ndx\SimpleRedirect\Data\Redirect as RedirectData;
use Ndx\SimpleRedirect\Events\RedirectDeleted;
use Ndx\SimpleRedirect\Events\RedirectSaved;
use Ndx\SimpleRedirect\Models\Redirect as RedirectModel;
use Statamic\Facades\Blink;

class EloquentRedirectRepository implements RedirectRepositoryContract
{
    public function all(): Collection
    {
        return $this->query()
            ->get()
            ->map(fn (RedirectModel $model) => RedirectData::fromModel($model));
    }

    public function find(string $id): ?Redirect
    {
        return Blink::once("simple-redirect-{$id}", function () use ($id) {
            $model = $this->query()->find($id);

            return $model ? RedirectData::fromModel($model) : null;
        });
    }

    public function enabled(): Collection
    {
        return $this->query()
            ->where('enabled', true)
            ->get()
            ->map(fn (RedirectModel $model) => RedirectData::fromModel($model));
    }

    public function ordered(): Collection
    {
        return $this->query()
            ->orderBy('order')
            ->get()
            ->map(fn (RedirectModel $model) => RedirectData::fromModel($model));
    }

    public function orderedEnabled(): Collection
    {
        return Blink::once('simple-redirects-ordered-enabled', function () {
            return $this->query()
                ->where('enabled', true)
                ->orderBy('order')
                ->get()
                ->map(fn (RedirectModel $model) => RedirectData::fromModel($model));
        });
    }

    public function save(Redirect $redirect): bool
    {
        if ($redirect->order() === null) {
            $maxOrder = $this->query()->max('order') ?? -1;
            $redirect->order($maxOrder + 1);
        }

        $model = $redirect->toModel();
        $model->save();

        $redirect->model($model->fresh());

        Blink::forget("simple-redirect-{$redirect->id()}");
        Blink::forget('simple-redirects-ordered-enabled');

        event(new RedirectSaved($redirect));

        return true;
    }

    public function delete(Redirect $redirect): bool
    {
        $this->query()->where('id', $redirect->id())->delete();

        Blink::forget("simple-redirect-{$redirect->id()}");
        Blink::forget('simple-redirects-ordered-enabled');

        event(new RedirectDeleted($redirect));

        return true;
    }

    public function make(): Redirect
    {
        return new RedirectData;
    }

    public function reorder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            $this->query()->where('id', $id)->update(['order' => $order]);
        }

        Blink::forget('simple-redirects-ordered-enabled');
    }

    protected function query(): Builder
    {
        return RedirectModel::query();
    }
}
