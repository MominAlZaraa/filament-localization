<?php

use MominAlZaraa\FilamentLocalization\Generators\ResourceModifier;
use MominAlZaraa\FilamentLocalization\Services\StatisticsService;

test('resource modifier can be instantiated', function () {
    $modifier = app(ResourceModifier::class);
    expect($modifier)->toBeInstanceOf(ResourceModifier::class);
});

test('resource modifier handles empty analysis gracefully', function () {
    $modifier = app(ResourceModifier::class);
    $analysis = [
        'file_path' => '/tmp/non-existent.php',
        'fields' => [],
        'columns' => [],
        'actions' => [],
        'sections' => [],
        'filters' => [],
    ];

    // Should not throw exception with empty analysis
    expect(fn () => $modifier->modify('TestResource', $analysis, null, false))
        ->not->toThrow(Exception::class);
});

test('statistics service can be instantiated', function () {
    $stats = app(StatisticsService::class);
    expect($stats)->toBeInstanceOf(StatisticsService::class);
});
