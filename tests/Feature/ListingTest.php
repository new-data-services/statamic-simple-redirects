<?php

use Illuminate\Testing\TestResponse;
use Ndx\SimpleRedirect\Contracts\RedirectRepository;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Ndx\SimpleRedirect\Tests\Fixtures\DelegatingRedirectRepository;
use Statamic\Facades\User;

uses(WithFileDriver::class);

beforeEach(function () {
    $this->user = tap(User::make()->email('test@example.com')->makeSuper())->save();
});

function createListingRedirects(int $count): void
{
    foreach (range(1, $count) as $number) {
        Redirect::save(Redirect::make()->source("/page-{$number}")->destination("/new-{$number}"));
    }
}

function fetchListing(array $parameters = []): TestResponse
{
    return test()
        ->actingAs(test()->user)
        ->getJson(cp_route('simple-redirects.index', $parameters))
        ->assertOk();
}

it('returns the first page in tree order', function () {
    createListingRedirects(30);

    $response = fetchListing(['perPage' => 10]);

    expect($response->json('data'))->toHaveCount(10)
        ->and($response->json('data.0.source'))->toBe('/page-1')
        ->and($response->json('meta.total'))->toBe(30)
        ->and($response->json('meta.last_page'))->toBe(3)
        ->and($response->json('meta.current_page'))->toBe(1);
});

it('returns the requested page', function () {
    createListingRedirects(30);

    $response = fetchListing(['page' => 2, 'perPage' => 10]);

    expect($response->json('data.0.source'))->toBe('/page-11');
});

it('searches source and destination case-insensitively and keeps tree order', function () {
    Redirect::save(Redirect::make()->source('/Alpha')->destination('/x'));
    Redirect::save(Redirect::make()->source('/beta')->destination('/ALPHA-new'));
    Redirect::save(Redirect::make()->source('/gamma')->destination('/y'));

    $response = fetchListing(['search' => 'alpha', 'perPage' => 10]);

    expect(collect($response->json('data'))->pluck('source')->all())->toBe(['/Alpha', '/beta'])
        ->and($response->json('meta.total'))->toBe(2);
});

it('ignores search but keeps pagination while reordering', function () {
    createListingRedirects(30);

    $response = fetchListing(['reordering' => 1, 'search' => 'page-1', 'perPage' => 10, 'page' => 2]);

    expect($response->json('data'))->toHaveCount(10)
        ->and($response->json('data.0.source'))->toBe('/page-11')
        ->and($response->json('meta.total'))->toBe(30)
        ->and($response->json('meta.last_page'))->toBe(3);
});

it('clamps per page to the configured options', function () {
    config()->set('statamic.cp.pagination_size_options', [10, 25]);

    createListingRedirects(3);

    expect(fetchListing(['perPage' => 1000])->json('meta.per_page'))->toBe(25)
        ->and(fetchListing(['perPage' => 0])->json('meta.per_page'))->toBe(1);
});

it('returns an empty page beyond the last page', function () {
    createListingRedirects(5);

    $response = fetchListing(['perPage' => 10, 'page' => 3]);

    expect($response->json('data'))->toBeEmpty()
        ->and($response->json('meta.last_page'))->toBe(1);
});

it('includes the columns in the meta', function () {
    createListingRedirects(1);

    $fields = collect(fetchListing()->json('meta.columns'))->pluck('field');

    expect($fields)->toContain('source', 'destination', 'regex', 'status_code');
});

it('passes the redirect count to the page', function () {
    createListingRedirects(3);

    $response = $this->actingAs($this->user)
        ->withHeader('X-Inertia', 'true')
        ->get(cp_route('simple-redirects.index'))
        ->assertOk();

    expect($response->json('props.redirectsCount'))->toBe(3)
        ->and($response->json('props.listingUrl'))->toBe(cp_route('simple-redirects.index'))
        ->and($response->json('props'))->not->toHaveKey('redirects');
});

it('passes the columns to the page', function () {
    $response = $this->actingAs($this->user)
        ->withHeader('X-Inertia', 'true')
        ->get(cp_route('simple-redirects.index'))
        ->assertOk();

    expect(collect($response->json('props.columns'))->pluck('field')->all())->toBe(['source', 'destination', 'regex', 'status_code']);
});

it('forbids users without the manage redirects permission', function () {
    $user = tap(User::make()->email('editor@example.com'))->save();

    $this->actingAs($user)
        ->getJson(cp_route('simple-redirects.index'))
        ->assertForbidden();
});

it('lists redirects from repositories without query support', function () {
    app()->instance(RedirectRepository::class, new DelegatingRedirectRepository(app(RedirectRepository::class)));
    Redirect::clearResolvedInstances();

    createListingRedirects(30);

    $page = fetchListing(['perPage' => 10, 'page' => 2]);

    expect($page->json('data.0.source'))->toBe('/page-11')
        ->and($page->json('meta.total'))->toBe(30)
        ->and(fetchListing(['search' => 'PAGE-3'])->json('meta.total'))->toBe(2);
});
