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

test('relation manager analyzer collects table empty state strings', function () {
    $analyzer = app(RelationManagerAnalyzer::class);
    $method = new ReflectionMethod(RelationManagerAnalyzer::class, 'analyzeTableMessages');
    $method->setAccessible(true);

    $php = <<<'PHP'
return $table
    ->emptyStateHeading('No records')
    ->emptyStateDescription('Create one to begin.');
PHP;

    $messages = $method->invoke($analyzer, $php);

    expect($messages)->toHaveCount(2)
        ->and($messages[0]['type'])->toBe('empty_state_heading')
        ->and($messages[0]['value'])->toBe('No records')
        ->and($messages[1]['type'])->toBe('empty_state_description')
        ->and($messages[1]['value'])->toBe('Create one to begin.');
});

test('relation manager analyzer collects KeyValue key and value labels', function () {
    $analyzer = app(RelationManagerAnalyzer::class);
    $method = new ReflectionMethod(RelationManagerAnalyzer::class, 'analyzeKeyValueAuxiliaryLabels');
    $method->setAccessible(true);

    $php = <<<'PHP'
KeyValue::make('custom_attributes')
    ->label('Attributes')
    ->keyLabel('Name')
    ->valueLabel('Value'),
PHP;

    $rows = $method->invoke($analyzer, $php);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['translation_key'])->toBe('custom_attributes_key_label')
        ->and($rows[0]['value'])->toBe('Name')
        ->and($rows[1]['translation_key'])->toBe('custom_attributes_value_label')
        ->and($rows[1]['value'])->toBe('Value');
});

test('relation manager analyzer collects modal copy on actions', function () {
    $analyzer = app(RelationManagerAnalyzer::class);
    $method = new ReflectionMethod(RelationManagerAnalyzer::class, 'analyzeActionModalCopy');
    $method->setAccessible(true);

    $php = <<<'PHP'
CreateAction::make()
    ->modalHeading('Add person')
    ->modalDescription('Fill the form below.');
PHP;

    $rows = $method->invoke($analyzer, $php);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['translation_key'])->toBe('modal_heading')
        ->and($rows[0]['value'])->toBe('Add person')
        ->and($rows[1]['translation_key'])->toBe('modal_description')
        ->and($rows[1]['value'])->toBe('Fill the form below.');
});
