<?php

use function Pest\Laravel\get;

it('can access an existing blog post', function () {
    $response = get('/blog/laravel-wordpress-migration');

    $response->assertStatus(200)
        ->assertViewIs('blog.show')
        ->assertViewHas('title')
        ->assertViewHas('content');
});

it('returns 404 for non-existent blog post', function () {
    $response = get('/blog/this-post-does-not-exist');

    $response->assertStatus(404);
});

it('blog post contains expected structure', function () {
    $response = get('/blog/laravel-wordpress-migration');

    $response->assertStatus(200)
        ->assertViewHas('title')
        ->assertViewHas('content')
        ->assertViewHas('publishedAt');
});
