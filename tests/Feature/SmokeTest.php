<?php

use function Pest\Laravel\get;

it('can access the homepage', function () {
    $response = get('/');
    
    $response->assertStatus(200);
});

it('can access the work page', function () {
    $response = get('/work');
    
    $response->assertStatus(200);
});

it('returns 404 for non-existent blog post', function () {
    $response = get('/blog/non-existent-post');
    
    $response->assertStatus(404);
});

it('homepage contains expected content', function () {
    $response = get('/');
    
    $response->assertStatus(200)
        ->assertSee('Andrew', false);
});

it('work page contains expected content', function () {
    $response = get('/work');
    
    $response->assertStatus(200)
        ->assertViewIs('work');
});