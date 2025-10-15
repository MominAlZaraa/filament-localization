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

        // Merge translations intelligently - preserve existing translations and only add missing keys
        $mergedTranslations = $this->mergeTranslationsIntelligently($existingTranslations, $translations);

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
            'nested' => "{$basePath}/{$prefix}/" . Str::snake($analysis['resource_name']) . '.php',
            'panel-based' => "{$basePath}/{$prefix}/{$panelId}/" . Str::snake($analysis['resource_name']) . '.php',
            default => "{$basePath}/{$prefix}/{$panelId}/" . Str::snake($analysis['resource_name']) . '.php',
        };
    }

    protected function buildTranslations(array $analysis): array
    {
        $translations = [];

        // Add resource label translations - use existing static properties if available
        $staticProperties = $analysis['static_properties'] ?? [];
        $resourceName = $analysis['resource_name'];

        // Use existing static properties or generate from resource name
        $modelLabel = $staticProperties['model_label'] ?? $this->generateLabelFromResourceName($resourceName);
        $navigationLabel = $staticProperties['navigation_label'] ?? Str::plural($modelLabel);
        $pluralModelLabel = $staticProperties['plural_model_label'] ?? Str::plural($modelLabel);

        // Handle getModelLabel method - if it exists, we should remove or nullify static properties
        if ($staticProperties['has_get_model_label'] ?? false) {
            // If getModelLabel exists, we don't need static property translations
            // The getModelLabel method will handle the labels dynamically
            // We can optionally add a comment or skip these translations
        } else {
            // Only add translations if they don't already exist in the target file
            // or if we're not preserving existing labels
            if (! config('filament-localization.preserve_existing_labels', false)) {
                $translations['navigation_label'] = $navigationLabel;
                $translations['model_label'] = $modelLabel;
                $translations['plural_model_label'] = $pluralModelLabel;
            }
        }

        // Add field translations
        foreach ($analysis['fields'] as $field) {
            // Only add if field doesn't have a label OR if we're not preserving existing labels
            if (! $field['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$field['translation_key']] = $field['default_label'];
            }

            // Add description translations if field has description
            if ($field['has_description']) {
                $descriptionKey = $field['translation_key'] . '_description';
                $translations[$descriptionKey] = $field['default_label'] . ' Description';
            }

            // Add Select options translations
            if (isset($field['select_options']) && ! empty($field['select_options'])) {
                foreach ($field['select_options'] as $option) {
                    // Use the translation_key from the option (which now handles boolean fields correctly)
                    $optionKey = $field['translation_key'] . '.' . $option['translation_key'];
                    // Use the option value for the translation text
                    $translations[$optionKey] = $option['value'];
                }
            }

            // Add default value translations
            if (isset($field['has_default']) && $field['has_default']) {
                $defaultKey = $field['translation_key'] . '_default';
                $translations[$defaultKey] = $field['default_label'] . ' Default';
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
            $hasTranslation = $section['has_translation'] ?? false;

            // Only add section translations if they don't already have translations
            if (! $hasTranslation) {
                $translations[$section['translation_key']] = $section['title'];
            }

            // Add description translations if section has description
            if (isset($section['has_description']) && $section['has_description']) {
                $descriptionKey = $section['translation_key'] . '_description';
                $translations[$descriptionKey] = $section['title'] . ' Description';
            }
        }

        // Add filter translations
        foreach ($analysis['filters'] as $filter) {
            if (! $filter['has_label'] || ! config('filament-localization.preserve_existing_labels', false)) {
                $translations[$filter['translation_key']] = $filter['default_label'];
            }
        }

        return $translations;
    }

    public function handleStaticPropertiesForGetModelLabel(array $analysis, bool $dryRun = false): void
    {
        $staticProperties = $analysis['static_properties'] ?? [];

        // Only proceed if getModelLabel method exists
        if (! ($staticProperties['has_get_model_label'] ?? false)) {
            return;
        }

        $filePath = $analysis['file_path'];
        if (! File::exists($filePath)) {
            return;
        }

        $content = File::get($filePath);
        $originalContent = $content;

        // Remove or nullify static properties that are handled by getModelLabel
        $propertiesToHandle = ['modelLabel', 'navigationLabel', 'pluralModelLabel'];

        foreach ($propertiesToHandle as $property) {
            // Pattern to match static property declarations
            $pattern = '/protected static \?\w+ \$' . $property . '\s*=\s*[\'"][^\'"]*[\'"];?\s*\n?/';

            if (preg_match($pattern, $content)) {
                // Replace with null assignment
                $replacement = "protected static ?string \${$property} = null;\n";
                $content = preg_replace($pattern, $replacement, $content);
            }
        }

        // Only write if content changed and not in dry run mode
        if ($content !== $originalContent && ! $dryRun) {
            File::put($filePath, $content);
        }
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

        // Merge translations intelligently - preserve existing translations and only add missing keys
        $mergedTranslations = $this->mergeTranslationsIntelligently($existingTranslations, $translations);

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

        // Merge translations intelligently - preserve existing translations and only add missing keys
        $mergedTranslations = $this->mergeTranslationsIntelligently($existingTranslations, $translations);

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

    public function generateWidgetTranslations(array $analysis, string $panelId, string $locale, bool $dryRun = false): void
    {
        $structure = config('filament-localization.structure', 'panel-based');

        $translationPath = $this->getWidgetTranslationPath($analysis, $panelId, $locale, $structure);
        $translations = $this->buildWidgetTranslations($analysis);

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

        // Merge translations intelligently - preserve existing translations and only add missing keys
        $mergedTranslations = $this->mergeTranslationsIntelligently($existingTranslations, $translations);

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
            'nested' => "{$basePath}/{$prefix}/" . Str::snake($analysis['page_name']) . '.php',
            'panel-based' => "{$basePath}/{$prefix}/{$panelId}/" . Str::snake($analysis['page_name']) . '.php',
            default => "{$basePath}/{$prefix}/{$panelId}/" . Str::snake($analysis['page_name']) . '.php',
        };
    }

    protected function getWidgetTranslationPath(array $analysis, string $panelId, string $locale, string $structure): string
    {
        $basePath = lang_path($locale);
        $prefix = config('filament-localization.translation_key_prefix', 'filament');

        return match ($structure) {
            'flat' => "{$basePath}/{$prefix}.php",
            'nested' => "{$basePath}/{$prefix}/" . Str::snake($analysis['widget_name']) . '.php',
            'panel-based' => "{$basePath}/{$prefix}/{$panelId}/" . Str::snake($analysis['widget_name']) . '.php',
            default => "{$basePath}/{$prefix}/{$panelId}/" . Str::snake($analysis['widget_name']) . '.php',
        };
    }

    protected function getRelationManagerTranslationPath(array $analysis, string $panelId, string $locale, string $structure): string
    {
        $basePath = lang_path($locale);
        $prefix = config('filament-localization.translation_key_prefix', 'filament');

        return match ($structure) {
            'flat' => "{$basePath}/{$prefix}.php",
            'nested' => "{$basePath}/{$prefix}/" . Str::snake($analysis['relation_manager_name']) . '.php',
            'panel-based' => "{$basePath}/{$prefix}/{$panelId}/" . Str::snake($analysis['relation_manager_name']) . '.php',
            default => "{$basePath}/{$prefix}/{$panelId}/" . Str::snake($analysis['relation_manager_name']) . '.php',
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
            $hasTranslation = $section['has_translation'] ?? false;

            // Only add section translations if they don't already have translations
            if (! $hasTranslation) {
                $translations[$section['translation_key']] = $section['title'];
            }

            // Add description translations if section has description
            if (isset($section['has_description']) && $section['has_description']) {
                $descriptionKey = $section['translation_key'] . '_description';
                $translations[$descriptionKey] = $section['title'] . ' Description';
            }
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

    protected function buildWidgetTranslations(array $analysis): array
    {
        $translations = [];

        // Add stat translations
        foreach ($analysis['stats'] as $stat) {
            if (! $stat['has_translation']) {
                $translations[$stat['translation_key']] = $stat['value'];
            }
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
            $hasTranslation = $section['has_translation'] ?? false;

            // Only add section translations if they don't already have translations
            if (! $hasTranslation) {
                $translations[$section['translation_key']] = $section['title'];
            }

            // Add description translations if section has description
            if (isset($section['has_description']) && $section['has_description']) {
                $descriptionKey = $section['translation_key'] . '_description';
                $translations[$descriptionKey] = $section['title'] . ' Description';
            }
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

    protected function mergeTranslationsIntelligently(array $existingTranslations, array $newTranslations): array
    {
        $merged = $existingTranslations;

        foreach ($newTranslations as $key => $value) {
            // Only add the key if it doesn't exist in existing translations
            if (! array_key_exists($key, $existingTranslations)) {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
