<?php

namespace Ndx\SimpleRedirect;

use Ndx\SimpleRedirect\Actions\DeleteRedirect;
use Ndx\SimpleRedirect\Actions\DisableRedirect;
use Ndx\SimpleRedirect\Actions\EnableRedirect;
use Ndx\SimpleRedirect\Contracts\RedirectRepository;
use Ndx\SimpleRedirect\Http\Middleware\HandleRedirects;
use Ndx\SimpleRedirect\Repositories\FileRedirectRepository;
use Ndx\SimpleRedirect\Stache\RedirectStore;
use Statamic\Facades\CP\Nav;
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
        $this->app->singleton(RedirectRepository::class, function ($app) {
            return new FileRedirectRepository($app['stache']);
        });
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
            ->bootStache()
            ->bootNavigation();
    }

    protected function bootRedirectConfig(): self
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/redirects.php', 'statamic.redirects');

        $this->publishes([
            __DIR__ . '/../config/redirects.php' => config_path('statamic/redirects.php'),
        ], 'simple-redirects-config');

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
            ->label(__('simple-redirects::permissions.manage_redirects'));

        return $this;
    }

    protected function bootStache(): self
    {
        $store = new RedirectStore;
        $store->directory(config('statamic.redirects.path', base_path('content/redirects')));

        app('stache')->registerStore($store);

        return $this;
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
}
