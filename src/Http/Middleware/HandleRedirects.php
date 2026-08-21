<?php

namespace Ndx\SimpleRedirect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ndx\SimpleRedirect\Contracts\Redirect as RedirectContract;
use Ndx\SimpleRedirect\Facades\Redirect;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Sites\Site as SiteInstance;
use Statamic\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        $uri         = $request->getRequestUri();
        $path        = Str::before($uri, '?');
        $queryString = Str::contains($uri, '?') ? Str::after($uri, '?') : '';

        $site       = Site::current();
        $candidates = $this->candidateUrls($request, $path, $queryString, $site);

        foreach (Redirect::orderedEnabled() as $redirect) {
            if (! $redirect->appliesToSite($site->handle())) {
                continue;
            }

            foreach ($candidates as $candidate) {
                if (! $redirect->matches($candidate)) {
                    continue;
                }

                return redirect(
                    $this->buildDestination($redirect, $candidate, $queryString, $site),
                    $redirect->statusCode()
                );
            }
        }

        return $response;
    }

    protected function candidateUrls(Request $request, string $path, string $queryString, SiteInstance $site): array
    {
        $urls = collect([
            $this->stripSitePrefix($path, $site),
            $path,
            $request->getSchemeAndHttpHost() . $path,
        ])->unique();

        if (! $queryString) {
            return $urls->all();
        }

        return $urls
            ->flatMap(fn (string $url) => [$url, $url . '?' . $queryString])
            ->all();
    }

    protected function buildDestination(RedirectContract $redirect, string $matchedUrl, string $queryString, SiteInstance $site): string
    {
        $destination = $this->prependSitePrefix($redirect->buildDestination($matchedUrl), $site);

        return $this->appendQueryString($destination, $matchedUrl, $queryString);
    }

    protected function stripSitePrefix(string $path, SiteInstance $site): string
    {
        $prefix = $this->sitePrefix($site);

        if (! $prefix || ! $this->hasPrefix($path, $prefix)) {
            return $path;
        }

        return URL::makeRelative(Str::removeLeft($path, $prefix));
    }

    protected function prependSitePrefix(string $destination, SiteInstance $site): string
    {
        $prefix = $this->sitePrefix($site);

        if (! $prefix || URL::isAbsolute($destination) || Str::startsWith($destination, '//')) {
            return $destination;
        }

        if ($this->isPrefixedForAnySite($destination)) {
            return $destination;
        }

        return $prefix . Str::ensureLeft($destination, '/');
    }

    protected function isPrefixedForAnySite(string $destination): bool
    {
        return Site::all()
            ->map(fn (SiteInstance $site) => $this->sitePrefix($site))
            ->filter()
            ->contains(fn (string $prefix) => $this->hasPrefix($destination, $prefix));
    }

    protected function appendQueryString(string $destination, string $matchedUrl, string $queryString): string
    {
        if (! $queryString || Str::contains($matchedUrl, '?') || Str::contains($destination, '?')) {
            return $destination;
        }

        return $destination . '?' . $queryString;
    }

    protected function sitePrefix(SiteInstance $site): string
    {
        return rtrim(parse_url($site->absoluteUrl(), PHP_URL_PATH) ?? '', '/');
    }

    protected function hasPrefix(string $path, string $prefix): bool
    {
        return $path === $prefix || Str::startsWith($path, $prefix . '/');
    }
}
