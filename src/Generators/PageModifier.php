<?php

namespace MominAlZaraa\FilamentLocalization\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MominAlZaraa\FilamentLocalization\Services\StatisticsService;

class PageModifier
{
    protected StatisticsService $statistics;

    public function __construct()
    {
        $this->statistics = app(StatisticsService::class);
    }

    public function modify(string $pageClass, array $analysis, $panel): void
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

        // Modify infolist entries
        $content = $this->modifyInfolistEntries($content, $analysis['infolist_entries'], $analysis, $panel);

        // Modify actions
        $content = $this->modifyActions($content, $analysis['actions'], $analysis, $panel);

        // Modify sections
        $content = $this->modifySections($content, $analysis['sections'], $analysis, $panel);

        // Modify custom content
        $content = $this->modifyCustomContent($content, $analysis['custom_content'], $analysis, $panel);

        // Modify labels, navigation, and titles
        $content = $this->modifyLabels($content, $analysis['labels'], $analysis, $panel);
        $content = $this->modifyNavigation($content, $analysis['navigation'], $analysis, $panel);
        $content = $this->modifyTitles($content, $analysis['titles'], $analysis, $panel);

        // Add missing label methods if needed
        $content = $this->addMissingLabelMethods($content, $analysis, $panel);

        // Only write if content changed
        if ($content !== $originalContent) {
            File::put($filePath, $content);
            $this->statistics->incrementFilesModified();
        }
    }

    protected function modifyInfolistEntries(string $content, array $entries, array $analysis, $panel): string
    {
        foreach ($entries as $entry) {
            // Skip if preserve existing labels is enabled and entry has a label
            if ($entry['has_label'] && config('filament-localization.preserve_existing_labels', false)) {
                continue;
            }

            $component = $entry['component'];
            $entryName = $entry['name'];
            $translationKey = $this->buildTranslationKey($analysis, $panel, $entry['translation_key']);

            // If entry already has a label, we need to replace it
            if ($entry['has_label']) {
                $escapedEntryName = preg_quote($entryName, '/');

                // Pattern: Find make('entry') followed by ->label() anywhere after it
                $pattern = "/({$component}::make\(['\"]".$escapedEntryName."['\"]\)(?:.*?))->label\((?:[^()]*|\([^()]*\))*\)/s";
                $replacement = "$1->label(__('$translationKey'))";

                $newContent = preg_replace($pattern, $replacement, $content, 1);

                // If replacement worked, use it
                if ($newContent !== $content) {
                    $content = $newContent;
                    $this->statistics->incrementFieldsLocalized();
                }
            } else {
                // Add label after make()
                $escapedEntryName = preg_quote($entryName, '/');
                $pattern = "/({$component}::make\(['\"]".$escapedEntryName."['\"]\))/";
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

    protected function modifyActions(string $content, array $actions, array $analysis, $panel): string
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

    protected function modifySections(string $content, array $sections, array $analysis, $panel): string
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

    protected function modifyCustomContent(string $content, array $customContent, array $analysis, $panel): string
    {
        foreach ($customContent as $item) {
            $translationKey = $this->buildTranslationKey($analysis, $panel, $item['translation_key']);
            $escapedContent = preg_quote($item['content'], '/');

            // Replace hardcoded strings with translation keys
            $pattern = "/['\"]{$escapedContent}['\"]/";
            $replacement = "__('$translationKey')";

            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    protected function buildTranslationKey(array $analysis, $panel, string $key): string
    {
        $prefix = config('filament-localization.translation_key_prefix', 'filament');
        $structure = config('filament-localization.structure', 'panel-based');

        return match ($structure) {
            'flat' => "{$prefix}.{$key}",
            'nested' => "{$prefix}/".Str::snake($analysis['page_name']).".{$key}",
            'panel-based' => "{$prefix}/{$panel->getId()}/".Str::snake($analysis['page_name']).".{$key}",
            default => "{$prefix}/{$panel->getId()}/".Str::snake($analysis['page_name']).".{$key}",
        };
    }

    protected function modifyLabels(string $content, array $labels, array $analysis, $panel): string
    {
        foreach ($labels as $label) {
            if ($label['has_translation']) {
                continue; // Already has translation
            }

            $translationKey = $this->buildTranslationKey($analysis, $panel, $label['translation_key']);
            $escapedValue = preg_quote($label['value'], '/');

            // Replace hardcoded values with translation keys
            $pattern = '/return\s+[\'"]' . $escapedValue . '[\'"]/';
            $replacement = "return __('$translationKey')";

            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    protected function modifyNavigation(string $content, array $navigation, array $analysis, $panel): string
    {
        foreach ($navigation as $nav) {
            if ($nav['has_translation']) {
                continue; // Already has translation
            }

            $translationKey = $this->buildTranslationKey($analysis, $panel, $nav['translation_key']);
            $escapedValue = preg_quote($nav['value'], '/');

            // Replace hardcoded values with translation keys
            $pattern = '/return\s+[\'"]' . $escapedValue . '[\'"]/';
            $replacement = "return __('$translationKey')";

            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    protected function modifyTitles(string $content, array $titles, array $analysis, $panel): string
    {
        foreach ($titles as $title) {
            if ($title['has_translation']) {
                continue; // Already has translation
            }

            $translationKey = $this->buildTranslationKey($analysis, $panel, $title['translation_key']);
            $escapedValue = preg_quote($title['value'], '/');

            // Replace hardcoded values with null (we'll use getTitle() method instead)
            $pattern = '/protected\s+static\s+\?string\s+\$' . $title['property'] . '\s*=\s*[\'"]' . $escapedValue . '[\'"]/';
            $replacement = "protected static ?string \$" . $title['property'] . " = null";

            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    protected function addMissingLabelMethods(string $content, array $analysis, $panel): string
    {
        $pageName = $analysis['page_name'];
        $translationKey = $this->buildTranslationKey($analysis, $panel, 'title');

        // Check if getTitle method exists (non-static for pages)
        if (!preg_match('/public\s+function\s+getTitle\s*\(/', $content)) {
            // Add getTitle method before the closing brace
            $pattern = '/(\n\s*}\s*)$/';
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertPosition = $matches[0][1];
                $method = "\n    public function getTitle(): string\n    {\n        return __('$translationKey');\n    }\n";
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
