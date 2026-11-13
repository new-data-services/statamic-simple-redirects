<?php

use Illuminate\Testing\TestResponse;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Sites\Site as SiteInstance;

uses(WithFileDriver::class);

beforeEach(function () {
    $this->user = tap(User::make()->email('test@example.com')->makeSuper())->save();
});

function fetchFilteredListing(array $filters, array $parameters = []): TestResponse
{
    return test()
        ->actingAs(test()->user)
        ->getJson(cp_route('simple-redirects.index', [
            ...$parameters,
            'filters' => base64_encode(json_encode($filters)),
        ]))
        ->assertOk();
}

function listedSources(TestResponse $response): array
{
    return collect($response->json('data'))->pluck('source')->all();
}

function enableMultisite(): void
{
    config()->set('statamic.editions.pro', true);
    config()->set('statamic.system.multisite', true);

    Site::setSites(collect([
        'en' => new SiteInstance('en', ['name' => 'English', 'url' => '/', 'locale' => 'en']),
        'de' => new SiteInstance('de', ['name' => 'German', 'url' => '/de/', 'locale' => 'de']),
        'fr' => new SiteInstance('fr', ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr']),
    ]));
}

it('filters by status before paginating and returns a badge', function () {
    foreach (range(1, 6) as $number) {
        Redirect::save(Redirect::make()->source("/page-{$number}")->destination('/new')->enabled($number % 2 === 0));
    }

    $response = fetchFilteredListing(['redirect_status' => ['status' => 'disabled']], ['perPage' => 2, 'page' => 2]);

    expect(listedSources($response))->toBe(['/page-5'])
        ->and($response->json('meta.total'))->toBe(3)
        ->and($response->json('meta.activeFilterBadges'))->toBe(['redirect_status' => 'Disabled']);
});

it('filters by redirect fields', function (array $values, array $expectedSources) {
    Redirect::save(Redirect::make()->source('/blog/old')->destination('/news')->statusCode(301));
    Redirect::save(Redirect::make()->source('/shop')->destination('/store')->statusCode(302));
    Redirect::save(Redirect::make()->source('/blog/(.*)')->destination('/news/$1')->regex(true)->statusCode(302));

    $response = fetchFilteredListing(['redirect_fields' => $values]);

    expect(listedSources($response))->toBe($expectedSources);
})->with([
    'source contains' => [['source' => ['operator' => 'like', 'value' => 'blog']], ['/blog/old', '/blog/(.*)']],
    'destination is'  => [['destination' => ['operator' => '=', 'value' => '/store']], ['/shop']],
    'status code is'  => [['status_code' => ['operator' => '=', 'value' => '302']], ['/shop', '/blog/(.*)']],
    'regex is true'   => [['regex' => ['value' => 'true']], ['/blog/(.*)']],
    'regex is false'  => [['regex' => ['value' => 'false']], ['/blog/old', '/shop']],
    'combined'        => [[
        'source'      => ['operator' => 'like', 'value' => 'blog'],
        'status_code' => ['operator' => '=', 'value' => '301'],
    ], ['/blog/old']],
]);

it('filters by site and keeps redirects that apply to all sites', function () {
    enableMultisite();

    Redirect::save(Redirect::make()->source('/all')->destination('/new'));
    Redirect::save(Redirect::make()->source('/none-selected')->destination('/new')->sites([]));
    Redirect::save(Redirect::make()->source('/en')->destination('/new')->sites(['en']));
    Redirect::save(Redirect::make()->source('/de')->destination('/new')->sites(['de']));
    Redirect::save(Redirect::make()->source('/fr')->destination('/new')->sites(['fr']));

    $response = fetchFilteredListing(['redirect_site' => ['sites' => ['de', 'fr']]]);

    expect(listedSources($response))->toBe(['/all', '/none-selected', '/de', '/fr'])
        ->and($response->json('meta.activeFilterBadges'))->toBe(['redirect_site' => 'German, French']);
});

it('ignores filters while reordering', function () {
    Redirect::save(Redirect::make()->source('/enabled')->destination('/new'));
    Redirect::save(Redirect::make()->source('/disabled')->destination('/new')->enabled(false));

    $response = fetchFilteredListing(['redirect_status' => ['status' => 'enabled']], ['reordering' => 1]);

    expect(listedSources($response))->toBe(['/enabled', '/disabled']);
});

it('offers the site filter only on multisite', function (bool $multisite, array $expectedFilters) {
    if ($multisite) {
        enableMultisite();
    }

    Redirect::save(Redirect::make()->source('/old')->destination('/new'));

    $response = $this->actingAs($this->user)
        ->withHeader('X-Inertia', 'true')
        ->get(cp_route('simple-redirects.index'))
        ->assertOk();

    expect(collect($response->json('props.filters'))->pluck('handle')->sort()->values()->all())->toBe($expectedFilters);
})->with([
    'single site' => [false, ['redirect_fields', 'redirect_status']],
    'multisite'   => [true, ['redirect_fields', 'redirect_site', 'redirect_status']],
]);
