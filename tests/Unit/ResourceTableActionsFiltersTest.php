<?php

use Illuminate\Support\Facades\File;
use MominAlZaraa\FilamentLocalization\Analyzers\ResourceAnalyzer;
use MominAlZaraa\FilamentLocalization\Generators\ResourceModifier;
use MominAlZaraa\FilamentLocalization\Generators\TranslationFileGenerator;

function mockPanel(string $id = 'admin'): object
{
    return new class($id)
    {
        public function __construct(private string $id) {}

        public function getId(): string
        {
            return $this->id;
        }
    };
}

test('resource analyzer detects named and unnamed table actions', function () {
    $analyzer = app(ResourceAnalyzer::class);
    $method = new ReflectionMethod(ResourceAnalyzer::class, 'analyzeActions');
    $method->setAccessible(true);

    $php = <<<'PHP'
return $table
    ->actions([
        EditAction::make(),
        DeleteAction::make(),
        Action::make('approve')
            ->label('Approve'),
    ])
    ->bulkActions([
        BulkAction::make('export_selected')
            ->label('Export Selected'),
    ]);
PHP;

    $actions = $method->invoke($analyzer, $php);
    $keys = array_map(fn (array $a) => ($a['component'] ?? '').'::'.($a['name'] ?? ''), $actions);

    expect($keys)->toContain('EditAction::edit')
        ->and($keys)->toContain('DeleteAction::delete')
        ->and($keys)->toContain('Action::approve')
        ->and($keys)->toContain('BulkAction::export_selected');

    $edit = collect($actions)->first(fn (array $a) => ($a['component'] ?? '') === 'EditAction');
    expect($edit['unnamed_make'] ?? false)->toBeTrue()
        ->and($edit['has_label'])->toBeFalse();

    $approve = collect($actions)->first(fn (array $a) => ($a['name'] ?? '') === 'approve');
    expect($approve['has_label'])->toBeTrue();
});

test('resource analyzer detects table filters with correct component for label detection', function () {
    $analyzer = app(ResourceAnalyzer::class);
    $method = new ReflectionMethod(ResourceAnalyzer::class, 'analyzeFilters');
    $method->setAccessible(true);

    $php = <<<'PHP'
return $table
    ->filters([
        SelectFilter::make('status')
            ->label('Status'),
        TernaryFilter::make('is_active'),
        TrashedFilter::make(),
    ]);
PHP;

    $filters = $method->invoke($analyzer, $php);

    $status = collect($filters)->first(fn (array $f) => $f['name'] === 'status');
    expect($status['component'])->toBe('SelectFilter')
        ->and($status['has_label'])->toBeTrue();

    $active = collect($filters)->first(fn (array $f) => $f['name'] === 'is_active');
    expect($active['component'])->toBe('TernaryFilter')
        ->and($active['has_label'])->toBeFalse();

    $trashed = collect($filters)->first(fn (array $f) => ($f['component'] ?? '') === 'TrashedFilter');
    expect($trashed['unnamed_make'] ?? false)->toBeTrue()
        ->and($trashed['name'])->toBe('trashed');
});

test('resource modifier localizes table actions and filters in a file', function () {
    $panel = mockPanel('admin');
    $modifier = app(ResourceModifier::class);
    $modifyActions = new ReflectionMethod(ResourceModifier::class, 'modifyActions');
    $modifyActions->setAccessible(true);
    $modifyFilters = new ReflectionMethod(ResourceModifier::class, 'modifyFilters');
    $modifyFilters->setAccessible(true);

    $analysis = [
        'resource_name' => 'OrderResource',
    ];

    $php = <<<'PHP'
return $table
    ->filters([
        SelectFilter::make('status'),
    ])
    ->actions([
        EditAction::make(),
        Action::make('approve'),
    ]);
PHP;

    $actions = [
        [
            'name' => 'edit',
            'component' => 'EditAction',
            'has_label' => false,
            'translation_key' => 'edit',
            'unnamed_make' => true,
        ],
        [
            'name' => 'approve',
            'component' => 'Action',
            'has_label' => false,
            'translation_key' => 'approve',
        ],
    ];

    $filters = [
        [
            'name' => 'status',
            'component' => 'SelectFilter',
            'has_label' => false,
            'translation_key' => 'status',
        ],
    ];

    $content = $modifyFilters->invoke($modifier, $php, $filters, $analysis, $panel, false);
    $content = $modifyActions->invoke($modifier, $content, $actions, $analysis, $panel, false);

    expect($content)
        ->toContain("SelectFilter::make('status')")
        ->toContain("->label(__('filament/admin/order_resource.status'))")
        ->toContain('EditAction::make()')
        ->toContain("->label(__('filament/admin/order_resource.edit'))")
        ->toContain("Action::make('approve')")
        ->toContain("->label(__('filament/admin/order_resource.approve'))");
});

test('translation file generator includes action and filter keys', function () {
    $generator = app(TranslationFileGenerator::class);
    $method = new ReflectionMethod(TranslationFileGenerator::class, 'buildTranslations');
    $method->setAccessible(true);

    $analysis = [
        'resource_name' => 'OrderResource',
        'static_properties' => [],
        'fields' => [],
        'columns' => [],
        'sections' => [],
        'infolist_entries' => [],
        'navigation_group' => null,
        'actions' => [
            [
                'name' => 'edit',
                'has_label' => false,
                'default_label' => 'Edit',
                'translation_key' => 'edit',
            ],
        ],
        'filters' => [
            [
                'name' => 'status',
                'has_label' => false,
                'default_label' => 'Status',
                'translation_key' => 'status',
            ],
        ],
    ];

    $translations = $method->invoke($generator, $analysis);

    expect($translations)->toHaveKey('edit', 'Edit')
        ->and($translations)->toHaveKey('status', 'Status');
});

test('resource modifier updates separate table schema files for actions and filters', function () {
    $panel = mockPanel('admin');
    $modifier = app(ResourceModifier::class);
    $method = new ReflectionMethod(ResourceModifier::class, 'modifySchemaFiles');
    $method->setAccessible(true);

    $schemaPath = sys_get_temp_dir().'/filament-localization-table-schema-'.uniqid().'.php';
    File::put($schemaPath, <<<'PHP'
<?php

return $table
    ->filters([
        SelectFilter::make('status'),
    ])
    ->actions([
        EditAction::make(),
    ]);
PHP);

    $analysis = [
        'resource_name' => 'ProductResource',
    ];

    $schemaFileFilters = [
        $schemaPath => [
            [
                'name' => 'status',
                'component' => 'SelectFilter',
                'has_label' => false,
                'translation_key' => 'status',
            ],
        ],
    ];

    $schemaFileActions = [
        $schemaPath => [
            [
                'name' => 'edit',
                'component' => 'EditAction',
                'has_label' => false,
                'translation_key' => 'edit',
                'unnamed_make' => true,
            ],
        ],
    ];

    config(['filament-localization.backup' => false]);

    $method->invoke(
        $modifier,
        [],
        [],
        $schemaFileActions,
        $schemaFileFilters,
        $analysis,
        $panel,
        false
    );

    $content = File::get($schemaPath);

    expect($content)
        ->toContain("->label(__('filament/admin/product_resource.status'))")
        ->toContain("->label(__('filament/admin/product_resource.edit'))");

    File::delete($schemaPath);
});
