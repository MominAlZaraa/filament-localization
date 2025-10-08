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

        // Modify form fields
        $content = $this->modifyFields($content, $analysis['fields'], $analysis, $panel);

        // Modify table columns
        $content = $this->modifyColumns($content, $analysis['columns'], $analysis, $panel);

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

            // Pattern to match the component make call
            $pattern = "/({$component}::make\(['\"]".preg_quote($fieldName, '/')."['\"]\))/";

            // If field already has a label, we need to replace it
            if ($field['has_label']) {
                $replacePattern = "/({$component}::make\(['\"]".preg_quote($fieldName, '/')."['\"]\).*?)->label\([^)]+\)/s";
                $replacement = "$1->label(__('$translationKey'))";
                $content = preg_replace($replacePattern, $replacement, $content, 1);
            } else {
                // Add label after make()
                $replacement = "$1\n                    ->label(__('$translationKey'))";
                $content = preg_replace($pattern, $replacement, $content, 1);
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

            // Pattern to match the component make call
            $pattern = "/({$component}::make\(['\"]".preg_quote($columnName, '/')."['\"]\))/";

            // If column already has a label, we need to replace it
            if ($column['has_label']) {
                $replacePattern = "/({$component}::make\(['\"]".preg_quote($columnName, '/')."['\"]\).*?)->label\([^)]+\)/s";
                $replacement = "$1->label(__('$translationKey'))";
                $content = preg_replace($replacePattern, $replacement, $content, 1);
            } else {
                // Add label after make()
                $replacement = "$1\n                    ->label(__('$translationKey'))";
                $content = preg_replace($pattern, $replacement, $content, 1);
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

            // Pattern to match the action make call
            $pattern = "/(Action::make\(['\"]".preg_quote($actionName, '/')."['\"]\))/";

            // If action already has a label, we need to replace it
            if ($action['has_label']) {
                $replacePattern = "/(Action::make\(['\"]".preg_quote($actionName, '/')."['\"]\).*?)->label\([^)]+\)/s";
                $replacement = "$1->label(__('$translationKey'))";
                $content = preg_replace($replacePattern, $replacement, $content, 1);
            } else {
                // Add label after make()
                $replacement = "$1\n                    ->label(__('$translationKey'))";
                $content = preg_replace($pattern, $replacement, $content, 1);
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

            // Pattern to match the filter make call
            $pattern = "/((?:Select|Ternary|)Filter::make\(['\"]".preg_quote($filterName, '/')."['\"]\))/";

            // If filter already has a label, we need to replace it
            if ($filter['has_label']) {
                $replacePattern = "/((?:Select|Ternary|)Filter::make\(['\"]".preg_quote($filterName, '/')."['\"]\).*?)->label\([^)]+\)/s";
                $replacement = "$1->label(__('$translationKey'))";
                $content = preg_replace($replacePattern, $replacement, $content, 1);
            } else {
                // Add label after make()
                $replacement = "$1\n                    ->label(__('$translationKey'))";
                $content = preg_replace($pattern, $replacement, $content, 1);
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
}
