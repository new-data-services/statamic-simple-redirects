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

function postOrder(?array $order): TestResponse
{
    return test()
        ->actingAs(test()->user)
        ->postJson(cp_route('simple-redirects.reorder'), $order === null ? [] : ['order' => $order]);
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
