<?php

namespace MominAlZaraa\FilamentLocalization\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MominAlZaraa\FilamentLocalization\Services\StatisticsService;

class ResourceModifier
{
    protected StatisticsService $statistics;

    public function __construct()
    {
        $this->statistics = app(StatisticsService::class);
    }

    public function modify(string $resourceClass, array $analysis, $panel): void
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
        $content = $this->modifyResourceLabels($content, $analysis, $panel);

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
        $content = $this->modifyFields($content, $resourceFields, $analysis, $panel);

        // Modify table columns in resource file
        $content = $this->modifyColumns($content, $resourceColumns, $analysis, $panel);

        // Modify actions
        $content = $this->modifyActions($content, $analysis['actions'], $analysis, $panel);

        // Modify sections
        $content = $this->modifySections($content, $analysis['sections'], $analysis, $panel);

        // Modify filters
        $content = $this->modifyFilters($content, $analysis['filters'], $analysis, $panel);

        // Only write if content changed
        if ($content !== $originalContent) {
            File::put($filePath, $content);
            $this->statistics->incrementFilesModified();
        }

        // Modify schema files
        $this->modifySchemaFiles($schemaFileFields, $schemaFileColumns, $analysis, $panel);
    }

    protected function modifyFields(string $content, array $fields, array $analysis, $panel): string
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
        }

        return $content;
    }

    protected function modifyColumns(string $content, array $columns, array $analysis, $panel): string
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

    protected function modifyActions(string $content, array $actions, array $analysis, $panel): string
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

    protected function modifySections(string $content, array $sections, array $analysis, $panel): string
    {
        foreach ($sections as $section) {
            $component = $section['component'];
            $title = $section['title'];
            $translationKey = $this->buildTranslationKey($analysis, $panel, $section['translation_key']);

            // Replace hardcoded section titles with translation keys
            $pattern = "/({$component}::make\(['\"])".preg_quote($title, '/').("['\"]\))/");
            $replacement = "$1' . __('$translationKey') . '$2";

            // Better approach: replace the entire make call
            $pattern = "/{$component}::make\(['\"]".preg_quote($title, '/')."['\"]\)/";
            $replacement = "{$component}::make(__('$translationKey'))";

            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    protected function modifyFilters(string $content, array $filters, array $analysis, $panel): string
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

    protected function modifySchemaFiles(array $schemaFileFields, array $schemaFileColumns, array $analysis, $panel): void
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
                $content = $this->modifyFields($content, $schemaFileFields[$schemaFilePath], $analysis, $panel);
            }

            // Modify columns in this schema file
            if (isset($schemaFileColumns[$schemaFilePath])) {
                $content = $this->modifyColumns($content, $schemaFileColumns[$schemaFilePath], $analysis, $panel);
            }

            // Only write if content changed
            if ($content !== $originalContent) {
                File::put($schemaFilePath, $content);
                $this->statistics->incrementFilesModified();
            }
        }
    }

    protected function modifyResourceLabels(string $content, array $analysis, $panel): string
    {
        $resourceName = $analysis['resource_name'];

        // Generate translation keys for resource labels
        $navigationLabelKey = $this->buildTranslationKey($analysis, $panel, 'navigation_label');
        $modelLabelKey = $this->buildTranslationKey($analysis, $panel, 'model_label');
        $pluralModelLabelKey = $this->buildTranslationKey($analysis, $panel, 'plural_model_label');

        // Check if resource already has these labels
        $hasNavigationLabel = preg_match('/protected\s+static\s+\?string\s+\$navigationLabel\s*=/', $content);
        $hasModelLabel = preg_match('/protected\s+static\s+\?string\s+\$modelLabel\s*=/', $content);
        $hasPluralModelLabel = preg_match('/protected\s+static\s+\?string\s+\$pluralModelLabel\s*=/', $content);

        // Find the position after the $model property to insert labels
        $pattern = '/(protected\s+static\s+\?string\s+\$model\s*=\s*[^;]+;)/';

        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[0][1] + strlen($matches[0][0]);

            $labelsToAdd = [];

            // Add navigation label if it doesn't exist
            if (! $hasNavigationLabel) {
                $labelsToAdd[] = "\n\n    protected static ?string \$navigationLabel = null;";
            }

            // Add model label if it doesn't exist
            if (! $hasModelLabel) {
                $labelsToAdd[] = "\n\n    protected static ?string \$modelLabel = null;";
            }

            // Add plural model label if it doesn't exist
            if (! $hasPluralModelLabel) {
                $labelsToAdd[] = "\n\n    protected static ?string \$pluralModelLabel = null;";
            }

            if (! empty($labelsToAdd)) {
                $content = substr_replace($content, implode('', $labelsToAdd), $insertPosition, 0);
            }
        }

        // Now add the getNavigationLabel() and getModelLabel() methods
        // Find the end of the class (before the closing brace)
        $pattern = '/(\n\s*}\s*)$/';

        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[0][1];

            $methods = [];

            // Check if getNavigationLabel already exists
            if (! preg_match('/public\s+static\s+function\s+getNavigationLabel\s*\(/', $content)) {
                $methods[] = "\n    public static function getNavigationLabel(): string\n    {\n        return __('$navigationLabelKey');\n    }\n";
            }

            // Check if getModelLabel already exists
            if (! preg_match('/public\s+static\s+function\s+getModelLabel\s*\(/', $content)) {
                $methods[] = "\n    public static function getModelLabel(): string\n    {\n        return __('$modelLabelKey');\n    }\n";
            }

            // Check if getPluralModelLabel already exists
            if (! preg_match('/public\s+static\s+function\s+getPluralModelLabel\s*\(/', $content)) {
                $methods[] = "\n    public static function getPluralModelLabel(): string\n    {\n        return __('$pluralModelLabelKey');\n    }\n";
            }

            if (! empty($methods)) {
                $content = substr_replace($content, implode('', $methods), $insertPosition, 0);
            }
        }

        return $content;
    }
}
