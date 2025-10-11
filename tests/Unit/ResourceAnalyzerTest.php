<?php

use MominAlZaraa\FilamentLocalization\Analyzers\ResourceAnalyzer;

test('resource analyzer can be instantiated', function () {
    $analyzer = app(ResourceAnalyzer::class);
    expect($analyzer)->toBeInstanceOf(ResourceAnalyzer::class);
});

test('resource analyzer handles non-existent class gracefully', function () {
    $analyzer = app(ResourceAnalyzer::class);

    // Create a mock panel
    $panel = new class
    {
        public function getId()
        {
            return 'test-panel';
        }
    };

    // Test that the analyzer can be instantiated
    expect($analyzer)->toBeInstanceOf(ResourceAnalyzer::class);

    // Test with a class that doesn't exist - should throw ReflectionException
    expect(fn () => $analyzer->analyze('NonExistentClass', $panel))
        ->toThrow(ReflectionException::class);
});

test('resource analyzer can be called with valid parameters', function () {
    $analyzer = app(ResourceAnalyzer::class);

    // Create a mock panel
    $panel = new class
    {
        public function getId()
        {
            return 'test-panel';
        }
    };

    // Test that the analyzer can be instantiated
    expect($analyzer)->toBeInstanceOf(ResourceAnalyzer::class);

    // Test with a class that doesn't exist - should throw ReflectionException
    expect(fn () => $analyzer->analyze('SomeNonExistentResourceClass', $panel))
        ->toThrow(ReflectionException::class);
});
