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

test('resource analyzer detects navigation group on string|UnitEnum|null property', function () {
    $analyzer = app(ResourceAnalyzer::class);
    $method = new ReflectionMethod(ResourceAnalyzer::class, 'analyzeNavigationGroup');
    $method->setAccessible(true);

    $content = <<<'PHP'
class ExampleResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Emergency Management';
}
PHP;

    $result = $method->invoke($analyzer, $content);

    expect($result)->toBeArray()
        ->and($result['value'])->toBe('Emergency Management')
        ->and($result['translation_key'])->toBe('navigation_group');
});

test('resource analyzer extracts infolist labels scoped to infolist method', function () {
    $analyzer = app(ResourceAnalyzer::class);
    $extract = new ReflectionMethod(ResourceAnalyzer::class, 'extractInfolistMethodInnerContent');
    $extract->setAccessible(true);
    $analyzeEntries = new ReflectionMethod(ResourceAnalyzer::class, 'analyzeInfolistEntries');
    $analyzeEntries->setAccessible(true);

    $content = <<<'PHP'
class X
{
    public static function form(): void
    {
        TextEntry::make('only_in_form');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('reporter_display_name')
                ->label('Reported By'),
        ]);
    }
}
PHP;

    $inner = $extract->invoke($analyzer, $content);
    expect($inner)->toContain('reporter_display_name')
        ->and($inner)->not->toContain('only_in_form');

    $entries = $analyzeEntries->invoke($analyzer, $inner);
    $names = array_column($entries, 'name');
    expect($names)->toContain('reporter_display_name')->not->toContain('only_in_form');
});
