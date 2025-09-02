<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('does not show Admin link to guests', function () {
    $response = get('/');

    $response->assertStatus(200)
        ->assertDontSee('href="/admin"', false)
        ->assertDontSee('>Admin<', false);
});

it('shows Admin link when authenticated', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->get('/');

    $response->assertStatus(200)
        ->assertSee('href="/admin"', false)
        ->assertSee('>Admin<', false);
});
