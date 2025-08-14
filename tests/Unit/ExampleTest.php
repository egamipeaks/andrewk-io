<?php

it('performs basic math correctly', function () {
    expect(2 + 2)->toBe(4);
});

it('can concatenate strings', function () {
    expect('Hello' . ' ' . 'World')->toBe('Hello World');
});