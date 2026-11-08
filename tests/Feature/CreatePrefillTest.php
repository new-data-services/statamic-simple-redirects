<?php

use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Statamic\Facades\User;

uses(WithFileDriver::class);

beforeEach(function () {
    // The CP refuses to serve more than one user without Pro, and the test environment already has one.
    config()->set('statamic.editions.pro', true);

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
