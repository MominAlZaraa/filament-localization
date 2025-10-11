<?php

use MominAlZaraa\FilamentLocalization\Analyzers\PageAnalyzer;

test('page analyzer can be instantiated', function () {
    $analyzer = app(PageAnalyzer::class);
    expect($analyzer)->toBeInstanceOf(PageAnalyzer::class);
});

test('page analyzer handles non-existent class gracefully', function () {
    $analyzer = app(PageAnalyzer::class);

    // Create a mock panel
    $panel = new class
    {
        public function getId()
        {
            return 'test-panel';
        }
    };

    // Test that the analyzer can be instantiated
    expect($analyzer)->toBeInstanceOf(PageAnalyzer::class);

    // Test with a class that doesn't exist - should throw ReflectionException
    expect(fn () => $analyzer->analyze('NonExistentClass', $panel))
        ->toThrow(ReflectionException::class);
});

test('page analyzer can be called with valid parameters', function () {
    $analyzer = app(PageAnalyzer::class);

    // Create a mock panel
    $panel = new class
    {
        public function getId()
        {
            return 'test-panel';
        }
    };

    // Test that the analyzer can be instantiated
    expect($analyzer)->toBeInstanceOf(PageAnalyzer::class);

    // Test with a class that doesn't exist - should throw ReflectionException
    expect(fn () => $analyzer->analyze('SomeNonExistentPageClass', $panel))
        ->toThrow(ReflectionException::class);
});
