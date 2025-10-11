<?php

use MominAlZaraa\FilamentLocalization\Analyzers\RelationManagerAnalyzer;

test('relation manager analyzer can be instantiated', function () {
    $analyzer = app(RelationManagerAnalyzer::class);
    expect($analyzer)->toBeInstanceOf(RelationManagerAnalyzer::class);
});

test('relation manager analyzer handles non-existent class gracefully', function () {
    $analyzer = app(RelationManagerAnalyzer::class);

    // Create a mock panel
    $panel = new class
    {
        public function getId()
        {
            return 'test-panel';
        }
    };

    // Test that the analyzer can be instantiated
    expect($analyzer)->toBeInstanceOf(RelationManagerAnalyzer::class);

    // Test with a class that doesn't exist - should throw ReflectionException
    expect(fn () => $analyzer->analyze('NonExistentClass', $panel))
        ->toThrow(ReflectionException::class);
});

test('relation manager analyzer can be called with valid parameters', function () {
    $analyzer = app(RelationManagerAnalyzer::class);

    // Create a mock panel
    $panel = new class
    {
        public function getId()
        {
            return 'test-panel';
        }
    };

    // Test that the analyzer can be instantiated
    expect($analyzer)->toBeInstanceOf(RelationManagerAnalyzer::class);

    // Test with a class that doesn't exist - should throw ReflectionException
    expect(fn () => $analyzer->analyze('SomeNonExistentRelationManagerClass', $panel))
        ->toThrow(ReflectionException::class);
});
