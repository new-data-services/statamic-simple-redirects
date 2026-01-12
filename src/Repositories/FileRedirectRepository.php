<?php

namespace Ndx\SimpleRedirect\Repositories;

use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Contracts\Redirect;
use Ndx\SimpleRedirect\Contracts\RedirectRepository as RedirectRepositoryContract;
use Ndx\SimpleRedirect\Data\Redirect as RedirectData;
use Ndx\SimpleRedirect\Data\RedirectTree;
use Ndx\SimpleRedirect\Events\RedirectDeleted;
use Ndx\SimpleRedirect\Events\RedirectSaved;
use Statamic\Facades\File;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;
use Symfony\Component\Finder\Finder;

class FileRedirectRepository implements RedirectRepositoryContract
{
    protected string $path;

    public function __construct()
    {
        $this->path = config('statamic.redirects.path', base_path('content/redirects'));
    }

    public function all(): Collection
    {
        if (! File::exists($this->path)) {
            return collect();
        }

        $files = Finder::create()->files()->in($this->path)->name('*.md')->sortByName();

        return collect($files)
            ->map(function ($file) {
                $id       = $file->getFilenameWithoutExtension();
                $contents = File::get($file->getPathname());
                $data     = YAML::parse($contents);

                $redirect = new RedirectData;
                $redirect->id($id);

                if (isset($data['source'])) {
                    $redirect->source($data['source']);
                }

                if (isset($data['destination'])) {
                    $redirect->destination($data['destination']);
                }

                if (isset($data['type'])) {
                    $redirect->type($data['type']);
                }

                if (isset($data['status_code'])) {
                    $redirect->statusCode($data['status_code']);
                }

                if (isset($data['site'])) {
                    $redirect->site($data['site']);
                }

                return $redirect;
            })
            ->values();
    }

    public function find(string $id): ?Redirect
    {
        $path = $this->path($id);

        if (! File::exists($path)) {
            return null;
        }

        $contents = File::get($path);
        $data     = YAML::parse($contents);

        $redirect = new RedirectData;
        $redirect->id($id);

        if (isset($data['source'])) {
            $redirect->source($data['source']);
        }

        if (isset($data['destination'])) {
            $redirect->destination($data['destination']);
        }

        if (isset($data['type'])) {
            $redirect->type($data['type']);
        }

        if (isset($data['status_code'])) {
            $redirect->statusCode($data['status_code']);
        }

        if (isset($data['site'])) {
            $redirect->site($data['site']);
        }

        return $redirect;
    }

    public function findBySite(string $site): Collection
    {
        return $this->all()->filter(function (Redirect $redirect) use ($site) {
            return $redirect->site() === $site;
        });
    }

    public function save(Redirect $redirect): bool
    {
        $this->ensureDirectoryExists();

        $path     = $this->path($redirect->id());
        $contents = YAML::dump($redirect->fileData());

        File::put($path, $contents);

        Stache::store('redirects')->clear();

        $tree = RedirectTree::find($redirect->site());
        $tree->append($redirect->id());
        $tree->save();

        event(new RedirectSaved($redirect));

        return true;
    }

    public function delete(Redirect $redirect): bool
    {
        $path = $this->path($redirect->id());

        if (File::exists($path)) {
            File::delete($path);
        }
        $tree = RedirectTree::find($redirect->site());
        $tree->remove($redirect->id());
        $tree->save();

        Stache::store('redirects')->clear();

        event(new RedirectDeleted($redirect));

        return true;
    }

    public function make(): Redirect
    {
        return new RedirectData;
    }

    protected function path(string $id): string
    {
        return "{$this->path}/{$id}.md";
    }

    protected function ensureDirectoryExists(): void
    {
        if (! File::exists($this->path)) {
            File::makeDirectory($this->path, 0755, true);
        }
    }
}
