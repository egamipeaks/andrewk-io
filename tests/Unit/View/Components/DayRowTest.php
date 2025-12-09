<?php

use App\View\Components\Filament\TimeTracking\DayRow;
use Carbon\Carbon;

it('detects today correctly', function () {
    $today = now();
    $component = new DayRow($today->year, $today->month, $today->day);

    expect($component->isToday())->toBeTrue();
});

it('detects non-today correctly', function () {
    $yesterday = now()->subDay();
    $component = new DayRow($yesterday->year, $yesterday->month, $yesterday->day);

    expect($component->isToday())->toBeFalse();
});

it('returns amber classes for today row', function () {
    $today = now();
    $component = new DayRow($today->year, $today->month, $today->day);

    $classes = $component->getRowClasses();

    expect($classes)->toContain('bg-amber-50')
        ->and($classes)->toContain('dark:bg-amber-900/20');
});

it('returns amber classes for today day cell', function () {
    $today = now();
    $component = new DayRow($today->year, $today->month, $today->day);

    $classes = $component->getDayCellClasses();

    expect($classes)->toContain('bg-amber-50')
        ->and($classes)->toContain('dark:bg-amber-900/20');
});

it('returns gray classes for weekend row when not today', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-10')); // Wednesday

    $saturday = Carbon::parse('2025-12-13');
    $component = new DayRow($saturday->year, $saturday->month, $saturday->day);

    $classes = $component->getRowClasses();

    expect($classes)->toContain('bg-gray-100')
        ->and($classes)->not->toContain('bg-amber-50');

    Carbon::setTestNow();
});

it('returns white classes for regular weekday row when not today', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-10')); // Wednesday

    $monday = Carbon::parse('2025-12-15');
    $component = new DayRow($monday->year, $monday->month, $monday->day);

    $classes = $component->getRowClasses();

    expect($classes)->toContain('bg-white')
        ->and($classes)->not->toContain('bg-amber-50')
        ->and($classes)->not->toContain('bg-gray-100');

    Carbon::setTestNow();
});

it('prioritizes today highlighting over weekend', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-13')); // Saturday

    $component = new DayRow(2025, 12, 13);

    expect($component->isWeekend())->toBeTrue()
        ->and($component->isToday())->toBeTrue();

    $classes = $component->getRowClasses();

    expect($classes)->toContain('bg-amber-50')
        ->and($classes)->not->toContain('bg-gray-100');

    Carbon::setTestNow();
});
