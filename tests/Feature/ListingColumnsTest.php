<?php

use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Http\Controllers\RedirectController;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Statamic\CP\Column;
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

it('lets the listing-redirects hook add columns and row values', function () {
    $redirect = Redirect::make()->source('/old')->destination('/new');
    Redirect::save($redirect);

    RedirectController::hook('listing-redirects', function ($payload, $next) {
        $payload->columns->push(Column::make('hits')->label('Hits'));
        $payload->redirects = $payload->redirects->map(fn (array $row) => [...$row, 'hits' => 42]);

        return $next($payload);
    });

    $response = $this->actingAs($this->user)
        ->withHeader('X-Inertia', 'true')
        ->get(cp_route('simple-redirects.index'))
        ->assertOk();

    expect(collect($response->json('props.columns'))->pluck('field'))->toContain('hits')
        ->and($response->json('props.redirects.0'))->toMatchArray(['id' => $redirect->id(), 'hits' => 42]);
});

it('applies the user preferences to columns added by the listing-redirects hook', function () {
    $this->user->setPreference('simple-redirects.columns', ['hits', 'source'])->save();

    RedirectController::hook('listing-redirects', function ($payload, $next) {
        $payload->columns->push(Column::make('hits')->label('Hits'));

        return $next($payload);
    });

    expect(listingColumns()->pluck('field')->take(2)->all())->toBe(['hits', 'source']);
});
