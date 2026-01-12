<?php

namespace Ndx\SimpleRedirect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Contracts\Redirect as RedirectContract;
use Ndx\SimpleRedirect\Data\RedirectTree;
use Ndx\SimpleRedirect\Facades\Redirect;
use Statamic\Facades\Site;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        $site = Site::current()->handle();
        $url  = $request->getPathInfo();

        $redirect = $this->findMatchingRedirect($url, $site);

        if (! $redirect) {
            return $response;
        }

        if ($redirect->statusCode() === 410) {
            return response('', 410);
        }

        return redirect($redirect->destination(), $redirect->statusCode());
    }

    protected function findMatchingRedirect(string $url, string $site): ?RedirectContract
    {
        $redirects = $this->getOrderedRedirects($site);

        foreach ($redirects as $redirect) {
            if ($redirect->matches($url)) {
                return $redirect;
            }
        }

        return null;
    }

    protected function getOrderedRedirects(string $site): Collection
    {
        $redirects = Redirect::findBySite($site);
        $tree      = RedirectTree::find($site);

        return collect($tree->tree())
            ->map(fn ($id) => $redirects->firstWhere('id', $id))
            ->filter()
            ->merge($redirects->whereNotIn('id', $tree->tree()))
            ->values();
    }
}
