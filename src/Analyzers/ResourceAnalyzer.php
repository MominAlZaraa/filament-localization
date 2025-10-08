<?php

namespace MominAlZaraa\FilamentLocalization\Analyzers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class ResourceAnalyzer
{
    protected array $formComponents = [
        'TextInput',
        'Textarea',
        'Select',
        'DatePicker',
        'DateTimePicker',
        'TimePicker',
        'Checkbox',
        'Toggle',
        'Radio',
        'FileUpload',
        'RichEditor',
        'MarkdownEditor',
        'ColorPicker',
        'KeyValue',
        'Repeater',
        'Builder',
        'TagsInput',
        'CheckboxList',
        'Hidden',
        'ViewField',
    ];

    protected array $tableColumns = [
        'TextColumn',
        'IconColumn',
        'ImageColumn',
        'ColorColumn',
        'CheckboxColumn',
        'ToggleColumn',
        'SelectColumn',
        'TextInputColumn',
    ];

    protected array $infolistEntries = [
        'TextEntry',
        'IconEntry',
        'ImageEntry',
        'ColorEntry',
        'KeyValueEntry',
        'RepeatableEntry',
    ];

    protected array $layoutComponents = [
        'Section',
        'Fieldset',
        'Grid',
        'Tabs',
        'Wizard',
        'Step',
        'Group',
    ];

    public function analyze(string $resourceClass, $panel): array
    {
        $reflection = new ReflectionClass($resourceClass);
        $filePath = $reflection->getFileName();

        if (! $filePath || ! File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);

        $analysis = [
            'resource_class' => $resourceClass,
            'resource_name' => class_basename($resourceClass),
            'file_path' => $filePath,
            'panel_id' => $panel->getId(),
            'fields' => [],
            'actions' => [],
            'columns' => [],
            'sections' => [],
            'tabs' => [],
            'filters' => [],
            'relation_managers' => [],
        ];

        // Analyze form fields
        $analysis['fields'] = $this->analyzeFormFields($content);

        // Analyze table columns
        $analysis['columns'] = $this->analyzeTableColumns($content);

        // Analyze actions
        $analysis['actions'] = $this->analyzeActions($content);

        // Analyze sections and layout components
        $analysis['sections'] = $this->analyzeSections($content);

        // Analyze filters
        $analysis['filters'] = $this->analyzeFilters($content);

        // Analyze relation managers
        $analysis['relation_managers'] = $this->analyzeRelationManagers($resourceClass);

        return $analysis;
    }

    protected function analyzeFormFields(string $content): array
    {
        $fields = [];

        foreach ($this->formComponents as $component) {
            // Match patterns like: TextInput::make('field_name')
            $pattern = "/{$component}::make\(['\"]([^'\"]+)['\"]\)/";

            preg_match_all($pattern, $content, $matches);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $fieldName) {
                    // Check if this field already has a label
                    $hasLabel = $this->hasLabel($content, $fieldName, $component);

                    $fields[] = [
                        'name' => $fieldName,
                        'component' => $component,
                        'has_label' => $hasLabel,
                        'default_label' => $this->generateLabel($fieldName),
                        'translation_key' => $this->generateTranslationKey($fieldName),
                    ];
                }
            }
        }

        return $fields;
    }

    protected function analyzeTableColumns(string $content): array
    {
        $columns = [];

        foreach ($this->tableColumns as $column) {
            // Match patterns like: TextColumn::make('field_name')
            $pattern = "/{$column}::make\(['\"]([^'\"]+)['\"]\)/";

            preg_match_all($pattern, $content, $matches);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $columnName) {
                    // Check if this column already has a label
                    $hasLabel = $this->hasLabel($content, $columnName, $column);

                    $columns[] = [
                        'name' => $columnName,
                        'component' => $column,
                        'has_label' => $hasLabel,
                        'default_label' => $this->generateLabel($columnName),
                        'translation_key' => $this->generateTranslationKey($columnName),
                    ];
                }
            }
        }

        return $columns;
    }

    protected function analyzeActions(string $content): array
    {
        $actions = [];

        // Match patterns like: Action::make('action_name')
        $pattern = "/Action::make\(['\"]([^'\"]+)['\"]\)/";

        preg_match_all($pattern, $content, $matches);

        if (! empty($matches[1])) {
            foreach ($matches[1] as $actionName) {
                // Check if this action already has a label
                $hasLabel = $this->hasLabel($content, $actionName, 'Action');

                $actions[] = [
                    'name' => $actionName,
                    'has_label' => $hasLabel,
                    'default_label' => $this->generateLabel($actionName),
                    'translation_key' => $this->generateTranslationKey($actionName),
                ];
            }
        }

        return $actions;
    }

    protected function analyzeSections(string $content): array
    {
        $sections = [];

        foreach ($this->layoutComponents as $component) {
            // Match patterns like: Section::make('Section Title')
            $pattern = "/{$component}::make\(['\"]([^'\"]+)['\"]\)/";

            preg_match_all($pattern, $content, $matches);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $sectionTitle) {
                    // Skip if it looks like a field name (snake_case)
                    if (Str::contains($sectionTitle, '_')) {
                        continue;
                    }

                    $sections[] = [
                        'title' => $sectionTitle,
                        'component' => $component,
                        'translation_key' => $this->generateTranslationKey(Str::snake($sectionTitle)),
                    ];
                }
            }
        }

        return $sections;
    }

    protected function analyzeFilters(string $content): array
    {
        $filters = [];

        // Match patterns like: SelectFilter::make('status')
        $pattern = "/(?:Select|Ternary|)Filter::make\(['\"]([^'\"]+)['\"]\)/";

        preg_match_all($pattern, $content, $matches);

        if (! empty($matches[1])) {
            foreach ($matches[1] as $filterName) {
                $hasLabel = $this->hasLabel($content, $filterName, 'Filter');

                $filters[] = [
                    'name' => $filterName,
                    'has_label' => $hasLabel,
                    'default_label' => $this->generateLabel($filterName),
                    'translation_key' => $this->generateTranslationKey($filterName),
                ];
            }
        }

        return $filters;
    }

    protected function analyzeRelationManagers(string $resourceClass): array
    {
        $relationManagers = [];

        try {
            $reflection = new ReflectionClass($resourceClass);

            if ($reflection->hasMethod('getRelations')) {
                $method = $reflection->getMethod('getRelations');
                $method->setAccessible(true);

                // Try to get relation managers (this might not work for all resources)
                // We'll need to parse the file content instead
                $filePath = $reflection->getFileName();
                $content = File::get($filePath);

                // Look for relation manager references
                preg_match_all("/([A-Za-z]+RelationManager)::class/", $content, $matches);

                if (! empty($matches[1])) {
                    foreach ($matches[1] as $relationManager) {
                        $relationManagers[] = [
                            'class' => $relationManager,
                            'name' => str_replace('RelationManager', '', $relationManager),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if we can't analyze relation managers
        }

        return $relationManagers;
    }

    protected function hasLabel(string $content, string $fieldName, string $component): bool
    {
        // Look for ->label() after the make() call for this specific field
        $pattern = "/{$component}::make\(['\"]".preg_quote($fieldName, '/')."['\"]\).*?->label\(/s";

        return preg_match($pattern, $content) === 1;
    }

    protected function generateLabel(string $fieldName): string
    {
        $strategy = config('filament-localization.label_generation', 'title_case');

        // Handle dot notation (e.g., 'patient_details.phone_number')
        // Take only the last part after the dot
        if (Str::contains($fieldName, '.')) {
            $parts = explode('.', $fieldName);
            $fieldName = end($parts);
        }

        // Remove common suffixes only if they're at the end with underscore
        $label = preg_replace('/_id$/', '', $fieldName);
        $label = preg_replace('/_at$/', '', $label);

        // Convert snake_case to words
        $label = str_replace('_', ' ', $label);

        return match ($strategy) {
            'title_case' => Str::title($label),
            'sentence_case' => Str::ucfirst($label),
            'keep_original' => $label,
            default => Str::title($label),
        };
    }

    protected function generateTranslationKey(string $fieldName): string
    {
        return Str::snake($fieldName);
    }
}
