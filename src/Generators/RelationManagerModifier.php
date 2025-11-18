<?php

namespace MominAlZaraa\FilamentLocalization\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MominAlZaraa\FilamentLocalization\Services\StatisticsService;

class RelationManagerModifier
{
    protected StatisticsService $statistics;

    public function __construct(?StatisticsService $statistics = null)
    {
        $this->statistics = $statistics ?? app(StatisticsService::class);
    }

    public function modify(string $relationManagerClass, array $analysis, $panel, bool $force = false): void
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

        // Modify form fields
        $content = $this->modifyFields($content, $analysis['fields'], $analysis, $panel, $force);

        // Modify table columns
        $content = $this->modifyColumns($content, $analysis['columns'], $analysis, $panel, $force);

        // Modify actions
        $content = $this->modifyActions($content, $analysis['actions'], $analysis, $panel, $force);

        // Modify sections
        $content = $this->modifySections($content, $analysis['sections'], $analysis, $panel, $force);

        // Modify filters
        $content = $this->modifyFilters($content, $analysis['filters'], $analysis, $panel, $force);

        // Modify labels, navigation, and titles
        $content = $this->modifyLabels($content, $analysis['labels'], $analysis, $panel, $force);
        $content = $this->modifyNavigation($content, $analysis['navigation'], $analysis, $panel, $force);
        $content = $this->modifyTitles($content, $analysis['titles'], $analysis, $panel, $force);

        // Add missing label methods if needed
        $content = $this->addMissingLabelMethods($content, $analysis, $panel);

        // If force mode is enabled, update any existing translation keys to use the correct panel
        if ($force) {
            $content = $this->updateTranslationKeysToCorrectPanel($content, $panel, $analysis);
        }

        // Only write if content changed
        if ($content !== $originalContent) {
            File::put($filePath, $content);
            $this->statistics->incrementFilesModified();
        }
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
                $escapedFieldName = preg_quote($fieldName, '/');

                // Pattern: Find make('field') followed by ->label() anywhere after it
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

            $component = $action['component'];
            $actionName = $action['name'];
            $translationKey = $this->buildTranslationKey($analysis, $panel, $action['translation_key']);

            // If action already has a label, we need to replace it
            if ($action['has_label']) {
                $escapedActionName = preg_quote($actionName, '/');

                // Pattern: Find make('action') followed by ->label() anywhere after it
                $pattern = "/({$component}::make\(['\"]".$escapedActionName."['\"]\)(?:.*?))->label\((?:[^()]*|\([^()]*\))*\)/s";
                $replacement = "$1->label(__('$translationKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                if ($newContent !== $content) {
                    $content = $newContent;
                    $this->statistics->incrementActionsLocalized();
                }
            } else {
                // Add label after make()
                $escapedActionName = preg_quote($actionName, '/');
                $pattern = "/({$component}::make\(['\"]".$escapedActionName."['\"]\))/";
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
        foreach ($sections as $section) {
            $component = $section['component'];
            $title = $section['title'];
            $translationKey = $this->buildTranslationKey($analysis, $panel, $section['translation_key']);

            // Replace hardcoded section titles with translation keys
            $pattern = "/{$component}::make\(['\"]".preg_quote($title, '/')."['\"]\)/";
            $replacement = "{$component}::make(__('$translationKey'))";

            $content = preg_replace($pattern, $replacement, $content, 1);
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

    protected function updateTranslationKeysToCorrectPanel(string $content, $panel, array $analysis): string
    {
        $currentPanelId = $panel->getId();
        $prefix = config('filament-localization.translation_key_prefix', 'filament');

        // Look for translation keys that reference other panels
        $possiblePanels = ['doctor', 'Doctor', 'admin', 'Admin', 'dentist', 'Dentist', 'nurse', 'Nurse', 'patient', 'Patient', 'reception', 'Reception'];

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

    protected function buildTranslationKey(array $analysis, $panel, string $key): string
    {
        $prefix = config('filament-localization.translation_key_prefix', 'filament');
        $structure = config('filament-localization.structure', 'panel-based');

        return match ($structure) {
            'flat' => "{$prefix}.{$key}",
            'nested' => "{$prefix}/".Str::snake($analysis['relation_manager_name']).".{$key}",
            'panel-based' => "{$prefix}/{$panel->getId()}/".Str::snake($analysis['relation_manager_name']).".{$key}",
            default => "{$prefix}/{$panel->getId()}/".Str::snake($analysis['relation_manager_name']).".{$key}",
        };
    }

    protected function modifyLabels(string $content, array $labels, array $analysis, $panel, bool $force = false): string
    {
        foreach ($labels as $label) {
            if ($label['has_translation'] && ! $force) {
                continue; // Already has translation
            }

            $translationKey = $this->buildTranslationKey($analysis, $panel, $label['translation_key']);
            $escapedValue = preg_quote($label['value'], '/');

            // Replace hardcoded values with translation keys
            $pattern = '/return\s+[\'"]'.$escapedValue.'[\'"]/';
            $replacement = "return __('$translationKey')";

            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    protected function modifyNavigation(string $content, array $navigation, array $analysis, $panel, bool $force = false): string
    {
        foreach ($navigation as $nav) {
            if ($nav['has_translation'] && ! $force) {
                continue; // Already has translation
            }

            $translationKey = $this->buildTranslationKey($analysis, $panel, $nav['translation_key']);
            $escapedValue = preg_quote($nav['value'], '/');

            // Replace hardcoded values with translation keys
            $pattern = '/return\s+[\'"]'.$escapedValue.'[\'"]/';
            $replacement = "return __('$translationKey')";

            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    protected function modifyTitles(string $content, array $titles, array $analysis, $panel, bool $force = false): string
    {
        foreach ($titles as $title) {
            if ($title['has_translation'] && ! $force) {
                continue; // Already has translation
            }

            $translationKey = $this->buildTranslationKey($analysis, $panel, $title['translation_key']);
            $escapedValue = preg_quote($title['value'], '/');

            // Replace hardcoded values with null (we'll use getTitle() method instead)
            $pattern = '/protected\s+static\s+\?string\s+\$'.$title['property'].'\s*=\s*[\'"]'.$escapedValue.'[\'"]/';
            $replacement = 'protected static ?string $'.$title['property'].' = null';

            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    protected function addMissingLabelMethods(string $content, array $analysis, $panel): string
    {
        $relationManagerName = $analysis['relation_manager_name'];
        $translationKey = $this->buildTranslationKey($analysis, $panel, 'title');

        // Check if static getTitle method exists (RelationManager uses static getTitle)
        if (! preg_match('/public\s+static\s+function\s+getTitle\s*\(/', $content)) {
            // Add Model import if not already present
            if (! preg_match('/use\s+Illuminate\\\\Database\\\\Eloquent\\\\Model;/', $content)) {
                // Find the last use statement and add Model import after it
                if (preg_match_all('/use\s+[^;]+;/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                    $lastUseStatement = end($matches[0]);
                    $insertPosition = $lastUseStatement[1] + strlen($lastUseStatement[0]);
                    $import = "\nuse Illuminate\\Database\\Eloquent\\Model;";
                    $content = substr_replace($content, $import, $insertPosition, 0);
                }
            }

            // Add static getTitle method before the closing brace
            $pattern = '/(\n\s*}\s*)$/';
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertPosition = $matches[0][1];
                $method = "\n    public static function getTitle(Model \$ownerRecord, string \$pageClass): string\n    {\n        return __('$translationKey');\n    }\n";
                $content = substr_replace($content, $method, $insertPosition, 0);
            }
        }

        // Note: We don't add static $title property as it can't contain function calls
        // The getTitle() method will handle the translation

        return $content;
    }

    protected function createBackup(string $filePath): void
    {
        $backupPath = $filePath.'.backup.'.date('Y-m-d-H-i-s');
        File::copy($filePath, $backupPath);
    }
}
