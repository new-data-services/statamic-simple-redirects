<?php

use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Statamic\Facades\User;

uses(WithFileDriver::class);

beforeEach(function () {
    config()->set('statamic.editions.pro', true);
    config()->set('statamic.system.multisite', true);

    $this->user = tap(User::make()->email('test@example.com')->makeSuper())->save();
});

function listingColumns(): Collection
{
    $response = test()
        ->actingAs(test()->user)
        ->withHeader('X-Inertia', 'true')
        ->get(cp_route('simple-redirects.index'))
        ->assertOk();

    return collect($response->json('props.columns'));
}

it('hides the sites column by default', function () {
    $columns = listingColumns()->keyBy('field');

    expect($columns->get('sites')['visible'])->toBeFalse()
        ->and($columns->get('source')['visible'])->toBeTrue();
});

it('respects the visibility stored in the user preferences', function () {
    $this->user->setPreference('simple-redirects.columns', ['source', 'destination', 'sites', 'regex', 'status_code'])->save();

    expect(listingColumns()->firstWhere('field', 'sites')['visible'])->toBeTrue();
});

it('respects the column order stored in the user preferences', function () {
    $this->user->setPreference('simple-redirects.columns', ['status_code', 'source', 'destination'])->save();

    $columns = listingColumns();

    expect($columns->pluck('field')->take(3)->all())->toBe(['status_code', 'source', 'destination'])
        ->and($columns->firstWhere('field', 'regex')['visible'])->toBeFalse();
});
