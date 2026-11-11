<?php

use Illuminate\Support\Facades\Cache;
use Ndx\SimpleRedirect\Http\Controllers\RedirectController;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Statamic\Facades\User;

uses(WithFileDriver::class);

beforeEach(function () {
    $this->user = tap(User::make()->email('test@example.com')->makeSuper())->save();
});

function createFormValues(array $query = []): array
{
    return test()
        ->actingAs(test()->user)
        ->withHeader('X-Inertia', 'true')
        ->get(cp_route('simple-redirects.create', $query))
        ->assertOk()
        ->json('props.values');
}

it('prefills fields from values[], like statamic entries', function () {
    expect(createFormValues(['values' => ['source' => '/alte-preisliste']])['source'])->toBe('/alte-preisliste');
});

it('keeps a query string inside the prefilled source', function () {
    expect(createFormValues(['values' => ['source' => '/a?b=c&d']])['source'])->toBe('/a?b=c&d');
});

it('leaves the form untouched without values', function () {
    expect(createFormValues()['source'] ?? null)->toBeEmpty();
});

it('ignores values that are not an array', function () {
    expect(createFormValues(['values' => 'foo'])['source'] ?? null)->toBeEmpty();
});

it('ignores values for unknown fields', function () {
    expect(createFormValues(['values' => ['source' => '/old', 'unknown' => 'foo']]))->not->toHaveKey('unknown');
});

it('passes the prefilled values to the creating-redirect hook', function () {
    RedirectController::hook('creating-redirect', function ($payload, $next) {
        Cache::put('hook-values', $payload->values);

        return $next($payload);
    });

    createFormValues(['values' => ['source' => '/old']]);

    expect(Cache::get('hook-values'))->toBe(['source' => '/old']);
});

it('lets the creating-redirect hook change values', function () {
    RedirectController::hook('creating-redirect', function ($payload, $next) {
        $payload->values = [...$payload->values, 'destination' => '/new', 'status_code' => '302'];

        return $next($payload);
    });

    expect(createFormValues(['values' => ['source' => '/old']]))->toMatchArray([
        'source'      => '/old',
        'destination' => '/new',
        'status_code' => '302',
        'enabled'     => true,
    ]);
});
