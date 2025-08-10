<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects to login when accessing admin panel without authentication', function () {
    $response = get('/admin');
    
    $response->assertRedirect('/admin/login');
});

it('can access admin login page', function () {
    $response = get('/admin/login');
    
    $response->assertStatus(200);
});

it('returns 403 when accessing admin panel as regular user', function () {
    $user = User::factory()->create();
    
    $response = actingAs($user)->get('/admin');
    
    $response->assertStatus(403);
});

it('returns 403 when accessing clients resource as regular user', function () {
    $user = User::factory()->create();
    
    $response = actingAs($user)->get('/admin/clients');
    
    $response->assertStatus(403);
});

it('returns 403 when accessing invoices resource as regular user', function () {
    $user = User::factory()->create();
    
    $response = actingAs($user)->get('/admin/invoices');
    
    $response->assertStatus(403);
});