<?php

namespace MominAlZaraa\FilamentLocalization\Analyzers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class RelationManagerAnalyzer
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

    protected array $actionComponents = [
        'Action',
        'CreateAction',
        'EditAction',
        'DeleteAction',
        'ViewAction',
        'BulkAction',
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

    public function analyze(string $relationManagerClass, $panel): array
    {
        $reflection = new ReflectionClass($relationManagerClass);
        $filePath = $reflection->getFileName();

        if (! $filePath || ! File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);

        $analysis = [
            'relation_manager_class' => $relationManagerClass,
            'relation_manager_name' => class_basename($relationManagerClass),
            'file_path' => $filePath,
            'panel_id' => $panel->getId(),
            'fields' => [],
            'columns' => [],
            'actions' => [],
            'sections' => [],
            'filters' => [],
            'has_custom_content' => false,
        ];

        // Check if this relation manager has custom content that needs localization
        $analysis['has_custom_content'] = $this->hasCustomContent($content);

        if (!$analysis['has_custom_content']) {
            return $analysis;
        }

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

        return $analysis;
    }

    protected function hasCustomContent(string $content): bool
    {
        // Check for common patterns that indicate custom content
        $patterns = [
            // Form fields
            '/TextInput::make\(/',
            '/Textarea::make\(/',
            '/Select::make\(/',
            '/DatePicker::make\(/',
            '/Checkbox::make\(/',
            
            // Table columns
            '/TextColumn::make\(/',
            '/IconColumn::make\(/',
            '/ImageColumn::make\(/',
            '/CheckboxColumn::make\(/',
            
            // Actions
            '/Action::make\(/',
            '/CreateAction::make\(/',
            '/EditAction::make\(/',
            '/DeleteAction::make\(/',
            
            // Custom labels and titles
            '/->label\([\'"][^\'"]+[\'"]\)/',
            '/->title\([\'"][^\'"]+[\'"]\)/',
            '/->heading\([\'"][^\'"]+[\'"]\)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    protected function analyzeFormFields(string $content): array
    {
        $fields = [];
        $foundFields = [];

        foreach ($this->formComponents as $component) {
            // Match patterns like: TextInput::make('field_name')
            $pattern = "/(?<![:\w]){$component}::make\(['\"]([^'\"]+)['\"]\)/";

            preg_match_all($pattern, $content, $matches);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $fieldName) {
                    // Skip if this field+component combination was already found
                    $key = "{$component}::{$fieldName}";
                    if (isset($foundFields[$key])) {
                        continue;
                    }

                    $foundFields[$key] = true;

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
            $pattern = "/(?<![:\w]){$column}::make\(['\"]([^'\"]+)['\"]\)/";

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

        foreach ($this->actionComponents as $component) {
            // Match patterns like: Action::make('action_name')
            $pattern = "/(?<![:\w]){$component}::make\(['\"]([^'\"]+)['\"]\)/";

            preg_match_all($pattern, $content, $matches);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $actionName) {
                    // Check if this action already has a label
                    $hasLabel = $this->hasLabel($content, $actionName, $component);

                    $actions[] = [
                        'name' => $actionName,
                        'component' => $component,
                        'has_label' => $hasLabel,
                        'default_label' => $this->generateLabel($actionName),
                        'translation_key' => $this->generateTranslationKey($actionName),
                    ];
                }
            }
        }

        return $actions;
    }

    protected function analyzeSections(string $content): array
    {
        $sections = [];

        foreach ($this->layoutComponents as $component) {
            // Match patterns like: Section::make('Section Title')
            $pattern = "/(?<![:\w]){$component}::make\(['\"]([^'\"]+)['\"]\)/";

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

    protected function hasLabel(string $content, string $fieldName, string $component): bool
    {
        // Look for ->label() after the make() call for this specific field
        $makePattern = "/(?<![:\w]){$component}::make\(['\"]".preg_quote($fieldName, '/')."['\"]\)/";

        if (! preg_match($makePattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $startPos = $matches[0][1] + strlen($matches[0][0]);

        // Find the next make() call or closing bracket/paren that ends the field
        $endPattern = "/\n\s*(?:\w+::make\(|[\]\}]\))/";

        if (preg_match($endPattern, $content, $endMatches, PREG_OFFSET_CAPTURE, $startPos)) {
            $endPos = $endMatches[0][1];
        } else {
            $endPos = strlen($content);
        }

        // Extract just this field's definition
        $fieldContent = substr($content, $startPos, $endPos - $startPos);

        // Check if this field's content contains ->label(
        return str_contains($fieldContent, '->label(');
    }

    protected function generateLabel(string $fieldName): string
    {
        $strategy = config('filament-localization.label_generation', 'title_case');

        // Handle dot notation (e.g., 'patient_details.phone_number')
        if (Str::contains($fieldName, '.')) {
            $parts = explode('.', $fieldName);
            $fieldName = end($parts);
        }

        // Remove common suffixes
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
