<?php

use Illuminate\Testing\TestResponse;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Statamic\Facades\User;

uses(WithFileDriver::class);

beforeEach(function () {
    $this->user = tap(User::make()->email('test@example.com')->makeSuper())->save();

    $this->redirects = collect(range(1, 3))->map(function (int $number) {
        $redirect = Redirect::make()->source("/page-{$number}")->destination("/new-{$number}");

        Redirect::save($redirect);

        return $redirect->id();
    })->all();
});

function postOrder(?array $order, int $page = 1, int $perPage = 3): TestResponse
{
    $payload = $order === null ? [] : ['order' => $order, 'page' => $page, 'perPage' => $perPage];

    return test()
        ->actingAs(test()->user)
        ->postJson(cp_route('simple-redirects.reorder'), $payload);
}

function addRedirects(int $count): array
{
    return collect(range(1, $count))->map(function (int $number) {
        $redirect = Redirect::make()->source("/more-{$number}")->destination("/new-more-{$number}");

        Redirect::save($redirect);

        return $redirect->id();
    })->all();
}

it('saves a complete order', function () {
    [$first, $second, $third] = $this->redirects;

    postOrder([$third, $first, $second])->assertOk();

    expect(Redirect::ordered()->map->id()->values()->all())->toBe([$third, $first, $second]);
});

it('rejects an order that misses a redirect', function () {
    [$first, $second] = $this->redirects;

    $response = postOrder([$second, $first])->assertStatus(409);

    expect($response->json('message'))->toBe(__('simple-redirects::messages.reorder_out_of_date'))
        ->and(Redirect::ordered()->map->id()->values()->all())->toBe($this->redirects);
});

it('rejects an order with unknown or duplicate ids', function () {
    [$first, $second, $third] = $this->redirects;

    postOrder([$first, $second, $third, 'unknown'])->assertStatus(409);
    postOrder([$first, $first, $second, $third])->assertStatus(409);
});

it('requires an order', function () {
    postOrder(null)->assertStatus(422);
});

it('reorders only the requested page', function () {
    [$first, $second, $third] = $this->redirects;
    [$fourth, $fifth]         = addRedirects(2);

    postOrder([$fourth, $third], page: 2, perPage: 2)->assertOk();

    expect(Redirect::ordered()->map->id()->values()->all())->toBe([$first, $second, $fourth, $third, $fifth]);
});

it('rejects a page that is out of date', function () {
    [, , $third]     = $this->redirects;
    [, $fifth]       = addRedirects(2);

    postOrder([$fifth, $third], page: 2, perPage: 2)->assertStatus(409);
});

it('clamps per page like the listing', function () {
    config()->set('statamic.cp.pagination_size_options', [2]);

    [$first, $second, $third] = $this->redirects;
    [$fourth]                 = addRedirects(1);

    postOrder([$fourth, $third], page: 2, perPage: 1000)->assertOk();

    expect(Redirect::ordered()->map->id()->values()->all())->toBe([$first, $second, $fourth, $third]);
});

it('requires page and per page', function () {
    test()->actingAs(test()->user)
        ->postJson(cp_route('simple-redirects.reorder'), ['order' => $this->redirects])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['page', 'perPage']);
});
