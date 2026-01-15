<?php

namespace Ndx\SimpleRedirect;

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
            ->bootConfig()
            ->bootTranslations()
            ->bootPermissions()
            ->bootStache()
            ->bootNavigation()
            ->bootViews()
            ->bootMiddleware();
    }

    protected function bootConfig(): self
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/redirects.php', 'statamic.redirects');

        $this->publishes([
            __DIR__ . '/../config/redirects.php' => config_path('statamic/redirects.php'),
        ], 'simple-redirects-config');

        return $this;
    }

    protected function bootTranslations(): self
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
                ->icon('direction')
                ->can('manage redirects');
        });

        return $this;
    }

    protected function bootViews(): self
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'simple-redirects');

        return $this;
    }

    protected function bootMiddleware(): self
    {
        $this->app['router']->pushMiddlewareToGroup('web', HandleRedirects::class);

        return $this;
    }
}
