<?php

namespace Ndx\SimpleRedirect;

use Ndx\SimpleRedirect\Actions\DeleteRedirect;
use Ndx\SimpleRedirect\Actions\DisableRedirect;
use Ndx\SimpleRedirect\Actions\EnableRedirect;
use Ndx\SimpleRedirect\Contracts\RedirectRepository;
use Ndx\SimpleRedirect\Contracts\RedirectTreeRepository;
use Ndx\SimpleRedirect\Events\RedirectDeleted;
use Ndx\SimpleRedirect\Events\RedirectSaved;
use Ndx\SimpleRedirect\Events\RedirectTreeSaved;
use Ndx\SimpleRedirect\Http\Middleware\HandleRedirects;
use Ndx\SimpleRedirect\Repositories\EloquentRedirectRepository;
use Ndx\SimpleRedirect\Repositories\FileRedirectRepository;
use Ndx\SimpleRedirect\Repositories\FileRedirectTreeRepository;
use Ndx\SimpleRedirect\Stache\RedirectsStore;
use Ndx\SimpleRedirect\Stache\RedirectsTreeStore;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Git;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'cp' => __DIR__ . '/../routes/cp.php',
    ];

    protected $vite = [
        'hotFile' => __DIR__ . '/../dist/vite.hot',
        'input'   => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'dist',
    ];

    protected $middlewareGroups = [
        'web' => [
            HandleRedirects::class,
        ],
    ];

    protected $actions = [
        EnableRedirect::class,
        DisableRedirect::class,
        DeleteRedirect::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/redirects.php', 'statamic.redirects');

        $this->registerRepositories();
    }

    public function bootAddon(): void
    {
        if (! config('statamic.redirects.enabled', true)) {
            return;
        }

        $this
            ->bootRedirectConfig()
            ->bootRedirectTranslations()
            ->bootPermissions()
            ->bootStorage()
            ->bootNavigation()
            ->bootGitListeners();
    }

    protected function registerRepositories(): void
    {
        if (config('statamic.redirects.driver', 'file') === 'eloquent') {
            $this->app->singleton(RedirectRepository::class, EloquentRedirectRepository::class);

            return;
        }

        $this->app->singleton(RedirectRepository::class, function ($app) {
            return new FileRedirectRepository($app['stache']);
        });

        $this->app->singleton(RedirectTreeRepository::class, function ($app) {
            return new FileRedirectTreeRepository($app['stache']);
        });
    }

    protected function bootRedirectConfig(): self
    {
        $this->publishes([
            __DIR__ . '/../config/redirects.php' => config_path('statamic/redirects.php'),
        ], 'simple-redirects-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/create_redirects_table.php.stub' => database_path('migrations/' . date('Y_m_d_His') . '_create_redirects_table.php'),
        ], 'simple-redirects-migrations');

        return $this;
    }

    protected function bootRedirectTranslations(): self
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'simple-redirects');

        $this->publishes([
            __DIR__ . '/../lang' => app()->langPath() . '/vendor/simple-redirects',
        ], 'simple-redirects-translations');

        return $this;
    }

    protected function bootPermissions(): self
    {
        Permission::register('manage redirects')
            ->label(__('simple-redirects::permissions.manage_redirects'))
            ->description(__('simple-redirects::permissions.manage_redirects_desc'));

        return $this;
    }

    protected function bootStorage(): self
    {
        if (config('statamic.redirects.driver', 'file') !== 'eloquent') {
            $this->bootStache();
        }

        return $this;
    }

    protected function bootStache(): void
    {
        $stores = config('statamic.redirects.stores', []);

        $redirectsStore = new RedirectsStore;
        $redirectsStore->directory($stores['redirects'] ?? base_path('content/redirects'));

        $treeStore = new RedirectsTreeStore;
        $treeStore->directory($stores['redirects-tree'] ?? base_path('content/trees/redirects'));

        app('stache')->registerStore($redirectsStore);
        app('stache')->registerStore($treeStore);
    }

    protected function bootNavigation(): self
    {
        Nav::extend(function ($nav) {
            $nav->tools(__('simple-redirects::messages.redirects'))
                ->route('simple-redirects.index')
                ->icon('moved')
                ->can('manage redirects');
        });

        return $this;
    }

    protected function bootGitListeners(): self
    {
        if (config('statamic.git.enabled', false)) {
            Git::listen(RedirectSaved::class);
            Git::listen(RedirectDeleted::class);
            Git::listen(RedirectTreeSaved::class);
        }

        return $this;
    }
}
