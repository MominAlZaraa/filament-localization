<?php

namespace MominAlZaraa\FilamentLocalization\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MominAlZaraa\FilamentLocalization\Services\StatisticsService;

class ResourceModifier
{
    protected StatisticsService $statistics;

    public function __construct(?StatisticsService $statistics = null)
    {
        $this->statistics = $statistics ?? app(StatisticsService::class);
    }

    public function modify(string $resourceClass, array $analysis, $panel, bool $force = false): void
    {
        $filePath = $analysis['file_path'];

        if (! File::exists($filePath)) {
            return;
        }

        // Create backup if enabled
        if (config('filament-localization.backup', true)) {
            $this->createBackup($filePath);
        }

        $content = File::get($filePath);
        $originalContent = $content;

        // Modify resource labels (navigation and model labels)
        $content = $this->modifyResourceLabels($content, $analysis, $panel, $force);

        // Group fields and columns by file
        $resourceFields = [];
        $resourceColumns = [];
        $schemaFileFields = [];
        $schemaFileColumns = [];

        foreach ($analysis['fields'] as $field) {
            if (isset($field['schema_file'])) {
                $schemaFileFields[$field['schema_file']][] = $field;
            } else {
                $resourceFields[] = $field;
            }
        }

        foreach ($analysis['columns'] as $column) {
            if (isset($column['schema_file'])) {
                $schemaFileColumns[$column['schema_file']][] = $column;
            } else {
                $resourceColumns[] = $column;
            }
        }

        // Modify form fields in resource file
        $content = $this->modifyFields($content, $resourceFields, $analysis, $panel, $force);

        // Modify table columns in resource file
        $content = $this->modifyColumns($content, $resourceColumns, $analysis, $panel, $force);

        // Modify actions
        $content = $this->modifyActions($content, $analysis['actions'], $analysis, $panel, $force);

        // Modify sections
        $content = $this->modifySections($content, $analysis['sections'], $analysis, $panel, $force);

        // Modify filters
        $content = $this->modifyFilters($content, $analysis['filters'], $analysis, $panel, $force);

        // In force mode, update existing translation keys to use correct panel reference
        if ($force) {
            $content = $this->updateTranslationKeysToCorrectPanel($content, $analysis, $panel);
        }

        // Only write if content changed
        if ($content !== $originalContent) {
            File::put($filePath, $content);
            $this->statistics->incrementFilesModified();
        }

        // Modify schema files
        $this->modifySchemaFiles($schemaFileFields, $schemaFileColumns, $analysis, $panel, $force);
    }

    protected function modifyFields(string $content, array $fields, array $analysis, $panel, bool $force = false): string
    {
        foreach ($fields as $field) {
            // Skip if preserve existing labels is enabled and field has a label
            if ($field['has_label'] && config('filament-localization.preserve_existing_labels', false)) {
                continue;
            }

            $component = $field['component'];
            $fieldName = $field['name'];
            $translationKey = $this->buildTranslationKey($analysis, $panel, $field['translation_key']);

            // If field already has a label, we need to replace it
            if ($field['has_label']) {
                // Match ->label(...) for this specific field and replace it
                // We need to find the make() call first, then find the label after it
                $escapedFieldName = preg_quote($fieldName, '/');

                // Pattern: Find make('field') followed by ->label() anywhere after it
                // Use a more robust pattern that handles nested parentheses in label
                $pattern = "/({$component}::make\(['\"]".$escapedFieldName."['\"]\)(?:.*?))->label\((?:[^()]*|\([^()]*\))*\)/s";
                $replacement = "$1->label(__('$translationKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                // If replacement worked, use it
                if ($newContent !== $content) {
                    $content = $newContent;
                    $this->statistics->incrementFieldsLocalized();
                }
            } else {
                // Add label after make()
                // Pattern to match just the make() call
                $escapedFieldName = preg_quote($fieldName, '/');
                $pattern = "/({$component}::make\(['\"]".$escapedFieldName."['\"]\))/";
                $replacement = "$1\n                    ->label(__('$translationKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                // If replacement worked, use it
                if ($newContent !== $content) {
                    $content = $newContent;
                    $this->statistics->incrementFieldsLocalized();
                }
            }

            // Handle descriptions - only modify if they already exist
            if ($field['has_description']) {
                $escapedFieldName = preg_quote($fieldName, '/');
                $descriptionKey = $this->buildTranslationKey($analysis, $panel, $field['translation_key'].'_description');

                // Pattern: Find make('field') followed by ->description() anywhere after it
                $pattern = "/({$component}::make\(['\"]".$escapedFieldName."['\"]\)(?:.*?))->description\((?:[^()]*|\([^()]*\))*\)/s";
                $replacement = "$1->description(__('$descriptionKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                // If replacement worked, use it
                if ($newContent !== $content) {
                    $content = $newContent;
                    $this->statistics->incrementFieldsLocalized();
                }
            }

            // Handle Select options and default values
            if ($component === 'Select' && ! empty($field['select_options'])) {
                // Check if all options already have proper translations
                $allOptionsHaveTranslations = true;
                foreach ($field['select_options'] as $option) {
                    if (! $option['is_translation']) {
                        $allOptionsHaveTranslations = false;
                        break;
                    }
                }

                // Only modify if not all options have translations
                if (! $allOptionsHaveTranslations) {
                    $content = $this->modifySelectOptions($content, $field, $analysis, $panel, $force);
                }
            }

            // Handle default values for any field
            if ($field['has_default']) {
                $content = $this->modifyDefaultValue($content, $field, $analysis, $panel, $force);
            }
        }

        return $content;
    }

    protected function modifySelectOptions(string $content, array $field, array $analysis, $panel, bool $force = false): string
    {
        $fieldName = $field['name'];
        $component = $field['component'];
        $escapedFieldName = preg_quote($fieldName, '/');

        // Find the Select component and its options
        $pattern = "/({$component}::make\(['\"]".$escapedFieldName."['\"]\)(?:.*?))->options\s*\(\s*\[(.*?)\]\s*\)/s";

        if (preg_match($pattern, $content, $matches)) {
            $beforeOptions = $matches[1];
            $optionsContent = $matches[2];

            // Build new options array with translation keys
            $newOptions = [];
            foreach ($field['select_options'] as $option) {
                $key = $option['key'];
                // Use the translation_key from the option (which now handles boolean fields correctly)
                $optionKey = $field['translation_key'].'.'.$option['translation_key'];
                $translationKey = $this->buildTranslationKey($analysis, $panel, $optionKey);

                if ($option['is_translation']) {
                    // Already has translation, update to new key
                    $newOptions[] = "'{$key}' => __('{$translationKey}')";
                } else {
                    // Plain value, add translation
                    $newOptions[] = "'{$key}' => __('{$translationKey}')";
                }
            }

            $newOptionsContent = '['.implode(', ', $newOptions).']';
            $replacement = $beforeOptions.'->options('.$newOptionsContent.')';

            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    protected function modifyDefaultValue(string $content, array $field, array $analysis, $panel, bool $force = false): string
    {
        $fieldName = $field['name'];
        $component = $field['component'];
        $escapedFieldName = preg_quote($fieldName, '/');
        $defaultKey = $this->buildTranslationKey($analysis, $panel, $field['translation_key'].'_default');

        // Pattern: Find make('field') followed by ->default() anywhere after it
        $pattern = "/({$component}::make\(['\"]".$escapedFieldName."['\"]\)(?:.*?))->default\((?:[^()]*|\([^()]*\))*\)/s";
        $replacement = "$1->default(__('$defaultKey'))";

        $newContent = preg_replace($pattern, $replacement, $content, 1);

        // If replacement worked, use it
        if ($newContent !== $content) {
            $content = $newContent;
            $this->statistics->incrementFieldsLocalized();
        }

        return $content;
    }

    protected function modifyColumns(string $content, array $columns, array $analysis, $panel, bool $force = false): string
    {
        foreach ($columns as $column) {
            // Skip if preserve existing labels is enabled and column has a label
            if ($column['has_label'] && config('filament-localization.preserve_existing_labels', false)) {
                continue;
            }

            $component = $column['component'];
            $columnName = $column['name'];
            $translationKey = $this->buildTranslationKey($analysis, $panel, $column['translation_key']);

            // If column already has a label, we need to replace it
            if ($column['has_label']) {
                $escapedColumnName = preg_quote($columnName, '/');

                // Pattern: Find make('column') followed by ->label() anywhere after it
                $pattern = "/({$component}::make\(['\"]".$escapedColumnName."['\"]\)(?:.*?))->label\((?:[^()]*|\([^()]*\))*\)/s";
                $replacement = "$1->label(__('$translationKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                if ($newContent !== $content) {
                    $content = $newContent;
                    $this->statistics->incrementColumnsLocalized();
                }
            } else {
                // Add label after make()
                $escapedColumnName = preg_quote($columnName, '/');
                $pattern = "/({$component}::make\(['\"]".$escapedColumnName."['\"]\))/";
                $replacement = "$1\n                    ->label(__('$translationKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                if ($newContent !== $content) {
                    $content = $newContent;
                    $this->statistics->incrementColumnsLocalized();
                }
            }
        }

        return $content;
    }

    protected function modifyActions(string $content, array $actions, array $analysis, $panel, bool $force = false): string
    {
        foreach ($actions as $action) {
            // Skip if preserve existing labels is enabled and action has a label
            if ($action['has_label'] && config('filament-localization.preserve_existing_labels', false)) {
                continue;
            }

            $actionName = $action['name'];
            $translationKey = $this->buildTranslationKey($analysis, $panel, $action['translation_key']);

            // If action already has a label, we need to replace it
            if ($action['has_label']) {
                $escapedActionName = preg_quote($actionName, '/');

                // Pattern: Find make('action') followed by ->label() anywhere after it
                $pattern = "/(Action::make\(['\"]".$escapedActionName."['\"]\)(?:.*?))->label\((?:[^()]*|\([^()]*\))*\)/s";
                $replacement = "$1->label(__('$translationKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                if ($newContent !== $content) {
                    $content = $newContent;
                    $this->statistics->incrementActionsLocalized();
                }
            } else {
                // Add label after make()
                $escapedActionName = preg_quote($actionName, '/');
                $pattern = "/(Action::make\(['\"]".$escapedActionName."['\"]\))/";
                $replacement = "$1\n                    ->label(__('$translationKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                if ($newContent !== $content) {
                    $content = $newContent;
                    $this->statistics->incrementActionsLocalized();
                }
            }
        }

        return $content;
    }

    protected function modifySections(string $content, array $sections, array $analysis, $panel, bool $force = false): string
    {
        // Group sections by their original key to handle duplicates properly
        $sectionsByOriginalKey = [];
        foreach ($sections as $section) {
            $originalKey = $section['original_translation_key'] ?? $section['title'];
            $sectionsByOriginalKey[$originalKey][] = $section;
        }

        foreach ($sectionsByOriginalKey as $originalKey => $sectionGroup) {
            foreach ($sectionGroup as $index => $section) {
                $component = $section['component'];
                $title = $section['title'];
                $hasTranslation = $section['has_translation'] ?? false;
                $translationKey = $this->buildTranslationKey($analysis, $panel, $section['translation_key']);

                // Replace hardcoded section titles with translation keys
                $pattern = "/{$component}::make\(['\"]".preg_quote($title, '/')."['\"]\)/";
                $replacement = "{$component}::make(__('$translationKey'))";

                // Use limit of 1 to replace only one occurrence at a time
                $content = preg_replace($pattern, $replacement, $content, 1);

                // Handle descriptions for layout components - only modify if they already exist
                if (isset($section['has_description']) && $section['has_description']) {
                    $descriptionKey = $this->buildTranslationKey($analysis, $panel, $section['translation_key'].'_description');

                    if ($hasTranslation) {
                        // Update existing description with translation key
                        $escapedOriginalKey = preg_quote($originalKey, '/');
                        $pattern = "/{$component}::make\(__\(['\"]".$escapedOriginalKey."['\"]\)\)(?:.*?)->description\((?:[^()]*|\([^()]*\))*\)/s";
                        $replacement = "{$component}::make(__('$translationKey'))->description(__('$descriptionKey'))";
                    } else {
                        // Update hardcoded title with description
                        $pattern = "/{$component}::make\(['\"]".preg_quote($title, '/')."['\"]\)(?:.*?)->description\((?:[^()]*|\([^()]*\))*\)/s";
                        $replacement = "{$component}::make(__('$translationKey'))->description(__('$descriptionKey'))";
                    }

                    // Use limit of 1 to replace only one occurrence at a time
                    $content = preg_replace($pattern, $replacement, $content, 1);
                }
            }
        }

        return $content;
    }

    protected function modifyFilters(string $content, array $filters, array $analysis, $panel, bool $force = false): string
    {
        foreach ($filters as $filter) {
            // Skip if preserve existing labels is enabled and filter has a label
            if ($filter['has_label'] && config('filament-localization.preserve_existing_labels', false)) {
                continue;
            }

            $filterName = $filter['name'];
            $translationKey = $this->buildTranslationKey($analysis, $panel, $filter['translation_key']);

            // If filter already has a label, we need to replace it
            if ($filter['has_label']) {
                $escapedFilterName = preg_quote($filterName, '/');

                // Pattern: Find make('filter') followed by ->label() anywhere after it
                $pattern = "/((?:Select|Ternary|)Filter::make\(['\"]".$escapedFilterName."['\"]\)(?:.*?))->label\((?:[^()]*|\([^()]*\))*\)/s";
                $replacement = "$1->label(__('$translationKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                if ($newContent !== $content) {
                    $content = $newContent;
                }
            } else {
                // Add label after make()
                $escapedFilterName = preg_quote($filterName, '/');
                $pattern = "/((?:Select|Ternary|)Filter::make\(['\"]".$escapedFilterName."['\"]\))/";
                $replacement = "$1\n                    ->label(__('$translationKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                if ($newContent !== $content) {
                    $content = $newContent;
                }
            }
        }

        return $content;
    }

    protected function buildTranslationKey(array $analysis, $panel, string $key): string
    {
        $prefix = config('filament-localization.translation_key_prefix', 'filament');
        $structure = config('filament-localization.structure', 'panel-based');

        return match ($structure) {
            'flat' => "{$prefix}.{$key}",
            'nested' => "{$prefix}/".Str::snake($analysis['resource_name']).".{$key}",
            'panel-based' => "{$prefix}/{$panel->getId()}/".Str::snake($analysis['resource_name']).".{$key}",
            default => "{$prefix}/{$panel->getId()}/".Str::snake($analysis['resource_name']).".{$key}",
        };
    }

    protected function createBackup(string $filePath): void
    {
        $backupPath = $filePath.'.backup.'.date('Y-m-d-H-i-s');
        File::copy($filePath, $backupPath);
    }

    protected function modifySchemaFiles(array $schemaFileFields, array $schemaFileColumns, array $analysis, $panel, bool $force = false): void
    {
        // Get all unique schema files
        $schemaFiles = array_unique(array_merge(
            array_keys($schemaFileFields),
            array_keys($schemaFileColumns)
        ));

        foreach ($schemaFiles as $schemaFilePath) {
            if (! File::exists($schemaFilePath)) {
                continue;
            }

            // Create backup if enabled
            if (config('filament-localization.backup', true)) {
                $this->createBackup($schemaFilePath);
            }

            $content = File::get($schemaFilePath);
            $originalContent = $content;

            // Modify fields in this schema file
            if (isset($schemaFileFields[$schemaFilePath])) {
                $content = $this->modifyFields($content, $schemaFileFields[$schemaFilePath], $analysis, $panel, $force);
            }

            // Modify columns in this schema file
            if (isset($schemaFileColumns[$schemaFilePath])) {
                $content = $this->modifyColumns($content, $schemaFileColumns[$schemaFilePath], $analysis, $panel, $force);
            }

            // Only write if content changed
            if ($content !== $originalContent) {
                File::put($schemaFilePath, $content);
                $this->statistics->incrementFilesModified();
            }
        }
    }

    protected function modifyResourceLabels(string $content, array $analysis, $panel, bool $force = false): string
    {
        $resourceName = $analysis['resource_name'];

        // Generate translation keys for resource labels
        $navigationLabelKey = $this->buildTranslationKey($analysis, $panel, 'navigation_label');
        $modelLabelKey = $this->buildTranslationKey($analysis, $panel, 'model_label');
        $pluralModelLabelKey = $this->buildTranslationKey($analysis, $panel, 'plural_model_label');

        // Check if resource already has these labels and if they have hardcoded values
        $hasNavigationLabel = preg_match('/protected\s+static\s+\?string\s+\$navigationLabel\s*=/', $content);
        $hasModelLabel = preg_match('/protected\s+static\s+\?string\s+\$modelLabel\s*=/', $content);
        $hasPluralModelLabel = preg_match('/protected\s+static\s+\?string\s+\$pluralModelLabel\s*=/', $content);

        // Check if labels have hardcoded values (not null)
        $hasHardcodedNavigationLabel = preg_match('/protected\s+static\s+\?string\s+\$navigationLabel\s*=\s*[\'"][^\'"]+[\'"]/', $content);
        $hasHardcodedModelLabel = preg_match('/protected\s+static\s+\?string\s+\$modelLabel\s*=\s*[\'"][^\'"]+[\'"]/', $content);
        $hasHardcodedPluralModelLabel = preg_match('/protected\s+static\s+\?string\s+\$pluralModelLabel\s*=\s*[\'"][^\'"]+[\'"]/', $content);

        // Find the position after the $model property to insert labels
        $pattern = '/(protected\s+static\s+\?string\s+\$model\s*=\s*[^;]+;)/';

        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[0][1] + strlen($matches[0][0]);

            $labelsToAdd = [];

            // Add/update navigation label if it has hardcoded values or doesn't exist, or if force mode is enabled
            if (! $hasNavigationLabel || $hasHardcodedNavigationLabel || $force) {
                if ($hasNavigationLabel) {
                    // Remove existing navigation label first (whether hardcoded or null)
                    $content = preg_replace('/protected\s+static\s+\?string\s+\$navigationLabel\s*=\s*[^;]+;\s*/', '', $content);
                }
                $labelsToAdd[] = "\n\n    protected static ?string \$navigationLabel = null;";
            }

            // Add/update model label if it has hardcoded values or doesn't exist, or if force mode is enabled
            if (! $hasModelLabel || $hasHardcodedModelLabel || $force) {
                if ($hasModelLabel) {
                    // Remove existing model label first (whether hardcoded or null)
                    $content = preg_replace('/protected\s+static\s+\?string\s+\$modelLabel\s*=\s*[^;]+;\s*/', '', $content);
                }
                $labelsToAdd[] = "\n\n    protected static ?string \$modelLabel = null;";
            }

            // Add/update plural model label if it has hardcoded values or doesn't exist, or if force mode is enabled
            if (! $hasPluralModelLabel || $hasHardcodedPluralModelLabel || $force) {
                if ($hasPluralModelLabel) {
                    // Remove existing plural model label first (whether hardcoded or null)
                    $content = preg_replace('/protected\s+static\s+\?string\s+\$pluralModelLabel\s*=\s*[^;]+;\s*/', '', $content);
                }
                $labelsToAdd[] = "\n\n    protected static ?string \$pluralModelLabel = null;";
            }

            if (! empty($labelsToAdd)) {
                $content = substr_replace($content, implode('', $labelsToAdd), $insertPosition, 0);
            }
        }

        // Now handle the getter methods - always update them if we're localizing
        $content = $this->updateResourceMethods($content, $navigationLabelKey, $modelLabelKey, $pluralModelLabelKey, $force);

        return $content;
    }

    protected function updateResourceMethods(string $content, string $navigationLabelKey, string $modelLabelKey, string $pluralModelLabelKey, bool $force = false): string
    {
        // Update or add getNavigationLabel method
        $content = $this->updateOrAddMethod($content, 'getNavigationLabel', $navigationLabelKey, $force);

        // Update or add getModelLabel method
        $content = $this->updateOrAddMethod($content, 'getModelLabel', $modelLabelKey, $force);

        // Update or add getPluralModelLabel method
        $content = $this->updateOrAddMethod($content, 'getPluralModelLabel', $pluralModelLabelKey, $force);

        return $content;
    }

    protected function updateOrAddMethod(string $content, string $methodName, string $translationKey, bool $force = false): string
    {
        $methodPattern = '/public\s+static\s+function\s+'.$methodName.'\s*\([^)]*\)\s*:\s*string\s*\{[^}]*\}/s';

        $newMethod = "    public static function {$methodName}(): string\n    {\n        return __('{$translationKey}');\n    }";

        if (preg_match($methodPattern, $content)) {
            // Replace existing method when force is enabled or when method doesn't already use translations
            if ($force || ! preg_match('/function\s+'.$methodName.'\s*\([^)]*\)\s*:\s*string\s*\{[^}]*__\s*\(/s', $content)) {
                $content = preg_replace($methodPattern, $newMethod, $content);
            }
        } else {
            // Method doesn't exist, add it before the closing brace
            $pattern = '/(\n\s*}\s*)$/';
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertPosition = $matches[0][1];
                $content = substr_replace($content, "\n".$newMethod."\n", $insertPosition, 0);
            }
        }

        return $content;
    }

    protected function updateTranslationKeysToCorrectPanel(string $content, array $analysis, $panel): string
    {
        $resourceName = Str::snake($analysis['resource_name']);
        $currentPanelId = $panel->getId();
        $prefix = config('filament-localization.translation_key_prefix', 'filament');

        // Look for translation keys that reference other panels
        $possiblePanels = config('filament-localization.other_panel_ids', ['admin', 'Admin']);

        foreach ($possiblePanels as $otherPanel) {
            if (strtolower($otherPanel) === strtolower($currentPanelId)) {
                continue; // Skip current panel
            }

            // Pattern to match translation keys with other panel references
            // This pattern matches __('filament/other_panel/...')
            $pattern = "/__\(['\"]".preg_quote($prefix, '/')."\/(?i:".preg_quote($otherPanel, '/').")\/([^'\"]+)['\"]\)/";
            $replacement = "__('$prefix/$currentPanelId/\$1')";

            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }
}
