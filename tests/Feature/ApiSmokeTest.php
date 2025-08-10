<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\get;

it('returns error when accessing protected api route without authentication', function () {
    $response = get('/api/user', ['Accept' => 'application/json']);
    
    $response->assertStatus(401);
});

it('can access protected api route when authenticated', function () {
    $user = User::factory()->create();
    
    Sanctum::actingAs($user);
    
    $response = get('/api/user');
    
    $response->assertStatus(200)
        ->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
});