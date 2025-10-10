<?php

namespace MominAlZaraa\FilamentLocalization\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MominAlZaraa\FilamentLocalization\Services\StatisticsService;

class TranslationFileGenerator
{
    protected StatisticsService $statistics;

    public function __construct(?StatisticsService $statistics = null)
    {
        $this->statistics = $statistics ?? app(StatisticsService::class);
    }

    public function generate(array $analysis, string $panelId, string $locale, bool $dryRun = false): void
    {
        $structure = config('filament-localization.structure', 'panel-based');

        $translationPath = $this->getTranslationPath($analysis, $panelId, $locale, $structure);
        $translations = $this->buildTranslations($analysis);

        if (empty($translations)) {
            return;
        }

        if ($dryRun) {
            return;
        }

        // Ensure directory exists
        $directory = dirname($translationPath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Check if file exists before writing
        $fileExists = File::exists($translationPath);

        // Load existing translations if file exists
        $existingTranslations = [];
        if ($fileExists) {
            $existingTranslations = include $translationPath;
        }

        // Merge translations (preserve existing ones)
        $mergedTranslations = array_merge($translations, $existingTranslations);

        // Sort translations alphabetically
        ksort($mergedTranslations);

        // Generate PHP array content
        $content = $this->generatePhpArrayContent($mergedTranslations);

        // Write to file
        File::put($translationPath, $content);

        // Update statistics
        if (! $fileExists) {
            $this->statistics->incrementTranslationFilesCreated();
        } else {
            $this->statistics->incrementFilesModified();
        }

        $this->statistics->incrementTranslationKeysAdded(count($translations));
    }

    protected function getTranslationPath(array $analysis, string $panelId, string $locale, string $structure): string
    {
        $basePath = lang_path($locale);
        $prefix = config('filament-localization.translation_key_prefix', 'filament');

        return match ($structure) {
            'flat' => "{$basePath}/{$prefix}.php",
            'nested' => "{$basePath}/{$prefix}/".Str::snake($analysis['resource_name']).'.php',
            'panel-based' => "{$basePath}/{$prefix}/{$panelId}/".Str::snake($analysis['resource_name']).'.php',
            default => "{$basePath}/{$prefix}/{$panelId}/".Str::snake($analysis['resource_name']).'.php',
        };
    }

    protected function buildTranslations(array $analysis): array
    {
        $translations = [];

        // Add resource label translations
        $resourceName = $analysis['resource_name'];
        $resourceLabel = $this->generateLabelFromResourceName($resourceName);

        $translations['navigation_label'] = Str::plural($resourceLabel);
        $translations['model_label'] = $resourceLabel;
        $translations['plural_model_label'] = Str::plural($resourceLabel);

        // Add field translations
        foreach ($analysis['fields'] as $field) {
            if (! $field['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$field['translation_key']] = $field['default_label'];
            }
        }

        // Add column translations
        foreach ($analysis['columns'] as $column) {
            if (! $column['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$column['translation_key']] = $column['default_label'];
            }
        }

        // Add action translations
        foreach ($analysis['actions'] as $action) {
            if (! $action['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$action['translation_key']] = $action['default_label'];
            }
        }

        // Add section translations
        foreach ($analysis['sections'] as $section) {
            $translations[$section['translation_key']] = $section['title'];
        }

        // Add filter translations
        foreach ($analysis['filters'] as $filter) {
            if (! $filter['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$filter['translation_key']] = $filter['default_label'];
            }
        }

        return $translations;
    }

    protected function generateLabelFromResourceName(string $resourceName): string
    {
        // Remove 'Resource' suffix if present
        $label = preg_replace('/Resource$/', '', $resourceName);

        // Convert PascalCase to words with spaces
        $label = preg_replace('/([a-z])([A-Z])/', '$1 $2', $label);

        return $label;
    }

    public function generatePageTranslations(array $analysis, string $panelId, string $locale, bool $dryRun = false): void
    {
        $structure = config('filament-localization.structure', 'panel-based');

        $translationPath = $this->getPageTranslationPath($analysis, $panelId, $locale, $structure);
        $translations = $this->buildPageTranslations($analysis);

        if (empty($translations)) {
            return;
        }

        if ($dryRun) {
            return;
        }

        // Ensure directory exists
        $directory = dirname($translationPath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Check if file exists before writing
        $fileExists = File::exists($translationPath);

        // Load existing translations if file exists
        $existingTranslations = [];
        if ($fileExists) {
            $existingTranslations = include $translationPath;
        }

        // Merge translations (preserve existing ones)
        $mergedTranslations = array_merge($translations, $existingTranslations);

        // Sort translations alphabetically
        ksort($mergedTranslations);

        // Generate PHP array content
        $content = $this->generatePhpArrayContent($mergedTranslations);

        // Write to file
        File::put($translationPath, $content);

        // Update statistics
        if (! $fileExists) {
            $this->statistics->incrementTranslationFilesCreated();
        } else {
            $this->statistics->incrementFilesModified();
        }

        $this->statistics->incrementTranslationKeysAdded(count($translations));
    }

    public function generateRelationManagerTranslations(array $analysis, string $panelId, string $locale, bool $dryRun = false): void
    {
        $structure = config('filament-localization.structure', 'panel-based');

        $translationPath = $this->getRelationManagerTranslationPath($analysis, $panelId, $locale, $structure);
        $translations = $this->buildRelationManagerTranslations($analysis);

        if (empty($translations)) {
            return;
        }

        if ($dryRun) {
            return;
        }

        // Ensure directory exists
        $directory = dirname($translationPath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Check if file exists before writing
        $fileExists = File::exists($translationPath);

        // Load existing translations if file exists
        $existingTranslations = [];
        if ($fileExists) {
            $existingTranslations = include $translationPath;
        }

        // Merge translations (preserve existing ones)
        $mergedTranslations = array_merge($translations, $existingTranslations);

        // Sort translations alphabetically
        ksort($mergedTranslations);

        // Generate PHP array content
        $content = $this->generatePhpArrayContent($mergedTranslations);

        // Write to file
        File::put($translationPath, $content);

        // Update statistics
        if (! $fileExists) {
            $this->statistics->incrementTranslationFilesCreated();
        } else {
            $this->statistics->incrementFilesModified();
        }

        $this->statistics->incrementTranslationKeysAdded(count($translations));
    }

    protected function getPageTranslationPath(array $analysis, string $panelId, string $locale, string $structure): string
    {
        $basePath = lang_path($locale);
        $prefix = config('filament-localization.translation_key_prefix', 'filament');

        return match ($structure) {
            'flat' => "{$basePath}/{$prefix}.php",
            'nested' => "{$basePath}/{$prefix}/".Str::snake($analysis['page_name']).'.php',
            'panel-based' => "{$basePath}/{$prefix}/{$panelId}/".Str::snake($analysis['page_name']).'.php',
            default => "{$basePath}/{$prefix}/{$panelId}/".Str::snake($analysis['page_name']).'.php',
        };
    }

    protected function getRelationManagerTranslationPath(array $analysis, string $panelId, string $locale, string $structure): string
    {
        $basePath = lang_path($locale);
        $prefix = config('filament-localization.translation_key_prefix', 'filament');

        return match ($structure) {
            'flat' => "{$basePath}/{$prefix}.php",
            'nested' => "{$basePath}/{$prefix}/".Str::snake($analysis['relation_manager_name']).'.php',
            'panel-based' => "{$basePath}/{$prefix}/{$panelId}/".Str::snake($analysis['relation_manager_name']).'.php',
            default => "{$basePath}/{$prefix}/{$panelId}/".Str::snake($analysis['relation_manager_name']).'.php',
        };
    }

    protected function buildPageTranslations(array $analysis): array
    {
        $translations = [];

        // Add infolist entry translations
        foreach ($analysis['infolist_entries'] as $entry) {
            if (! $entry['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$entry['translation_key']] = $entry['default_label'];
            }
        }

        // Add action translations
        foreach ($analysis['actions'] as $action) {
            if (! $action['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$action['translation_key']] = $action['default_label'];
            }
        }

        // Add section translations
        foreach ($analysis['sections'] as $section) {
            $translations[$section['translation_key']] = $section['title'];
        }

        // Add custom content translations
        foreach ($analysis['custom_content'] as $item) {
            $translations[$item['translation_key']] = $item['content'];
        }

        // Add label translations
        foreach ($analysis['labels'] as $label) {
            if (! $label['has_translation']) {
                $translations[$label['translation_key']] = $label['value'];
            }
        }

        // Add navigation translations
        foreach ($analysis['navigation'] as $nav) {
            if (! $nav['has_translation']) {
                $translations[$nav['translation_key']] = $nav['value'];
            }
        }

        // Add title translations
        foreach ($analysis['titles'] as $title) {
            if (! $title['has_translation']) {
                $translations[$title['translation_key']] = $title['value'];
            }
        }

        // Add default title if no title exists
        if (empty($analysis['labels']) && empty($analysis['titles'])) {
            $pageName = $analysis['page_name'];
            $defaultTitle = $this->generateDefaultTitle($pageName);
            $translations['title'] = $defaultTitle;
        }

        return $translations;
    }

    protected function buildRelationManagerTranslations(array $analysis): array
    {
        $translations = [];

        // Add field translations
        foreach ($analysis['fields'] as $field) {
            if (! $field['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$field['translation_key']] = $field['default_label'];
            }
        }

        // Add column translations
        foreach ($analysis['columns'] as $column) {
            if (! $column['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$column['translation_key']] = $column['default_label'];
            }
        }

        // Add action translations
        foreach ($analysis['actions'] as $action) {
            if (! $action['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$action['translation_key']] = $action['default_label'];
            }
        }

        // Add section translations
        foreach ($analysis['sections'] as $section) {
            $translations[$section['translation_key']] = $section['title'];
        }

        // Add filter translations
        foreach ($analysis['filters'] as $filter) {
            if (! $filter['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$filter['translation_key']] = $filter['default_label'];
            }
        }

        // Add label translations
        foreach ($analysis['labels'] as $label) {
            if (! $label['has_translation']) {
                $translations[$label['translation_key']] = $label['value'];
            }
        }

        // Add navigation translations
        foreach ($analysis['navigation'] as $nav) {
            if (! $nav['has_translation']) {
                $translations[$nav['translation_key']] = $nav['value'];
            }
        }

        // Add title translations
        foreach ($analysis['titles'] as $title) {
            if (! $title['has_translation']) {
                $translations[$title['translation_key']] = $title['value'];
            }
        }

        // Add default title if no title exists
        if (empty($analysis['labels']) && empty($analysis['titles'])) {
            $relationManagerName = $analysis['relation_manager_name'];
            $defaultTitle = $this->generateDefaultTitle($relationManagerName);
            $translations['title'] = $defaultTitle;
        }

        return $translations;
    }

    protected function generateDefaultTitle(string $name): string
    {
        // Remove common suffixes
        $title = preg_replace('/Resource$/', '', $name);
        $title = preg_replace('/RelationManager$/', '', $title);
        $title = preg_replace('/Page$/', '', $title);

        // Convert PascalCase to words with spaces
        $title = preg_replace('/([a-z])([A-Z])/', '$1 $2', $title);

        return $title;
    }

    protected function generatePhpArrayContent(array $translations): string
    {
        $content = "<?php\n\nreturn [\n\n";

        foreach ($translations as $key => $value) {
            // Escape single quotes in values
            $escapedValue = str_replace("'", "\\'", $value);
            $content .= "    '{$key}' => '{$escapedValue}',\n";
        }

        $content .= "\n];\n";

        return $content;
    }
}
