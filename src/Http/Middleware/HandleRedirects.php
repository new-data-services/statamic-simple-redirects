<?php

namespace Ndx\SimpleRedirect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ndx\SimpleRedirect\Contracts\Redirect as RedirectContract;
use Ndx\SimpleRedirect\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        $url = $request->getRequestUri();

        $redirect = $this->findMatchingRedirect($url);

        if (! $redirect) {
            return $response;
        }

        $destination = $redirect->buildDestination($url);

        return redirect($destination, $redirect->statusCode());
    }

    protected function findMatchingRedirect(string $url): ?RedirectContract
    {
        foreach (Redirect::orderedEnabled() as $redirect) {
            if ($redirect->matches($url)) {
                return $redirect;
            }
        }

        return null;
    }
}
