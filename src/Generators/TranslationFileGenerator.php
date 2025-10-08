<?php

namespace MominAlZaraa\FilamentLocalization\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MominAlZaraa\FilamentLocalization\Services\StatisticsService;

class TranslationFileGenerator
{
    protected StatisticsService $statistics;

    public function __construct()
    {
        $this->statistics = app(StatisticsService::class);
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
