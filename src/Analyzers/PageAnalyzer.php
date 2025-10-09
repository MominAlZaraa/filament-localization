<?php

namespace MominAlZaraa\FilamentLocalization\Analyzers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class PageAnalyzer
{
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

    protected array $actionComponents = [
        'Action',
        'CreateAction',
        'EditAction',
        'DeleteAction',
        'ViewAction',
        'BulkAction',
    ];

    public function analyze(string $pageClass, $panel): array
    {
        $reflection = new ReflectionClass($pageClass);
        $filePath = $reflection->getFileName();

        if (! $filePath || ! File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);

        $analysis = [
            'page_class' => $pageClass,
            'page_name' => class_basename($pageClass),
            'file_path' => $filePath,
            'panel_id' => $panel->getId(),
            'infolist_entries' => [],
            'actions' => [],
            'sections' => [],
            'custom_content' => [],
            'has_custom_content' => false,
        ];

        // Check if this page has custom content that needs localization
        $analysis['has_custom_content'] = $this->hasCustomContent($content);

        if (!$analysis['has_custom_content']) {
            return $analysis;
        }

        // Analyze infolist entries (for ViewRecord pages)
        $analysis['infolist_entries'] = $this->analyzeInfolistEntries($content);

        // Analyze actions
        $analysis['actions'] = $this->analyzeActions($content);

        // Analyze sections and layout components
        $analysis['sections'] = $this->analyzeSections($content);

        // Analyze custom content like hardcoded strings
        $analysis['custom_content'] = $this->analyzeCustomContent($content);

        return $analysis;
    }

    protected function hasCustomContent(string $content): bool
    {
        // Check for common patterns that indicate custom content
        $patterns = [
            // Infolist entries
            '/TextEntry::make\(/',
            '/IconEntry::make\(/',
            '/ImageEntry::make\(/',
            '/ColorEntry::make\(/',
            '/KeyValueEntry::make\(/',
            '/RepeatableEntry::make\(/',
            
            // Custom actions
            '/Action::make\(/',
            '/CreateAction::make\(/',
            '/EditAction::make\(/',
            '/DeleteAction::make\(/',
            '/ViewAction::make\(/',
            '/BulkAction::make\(/',
            
            // Custom sections
            '/Section::make\(/',
            '/Fieldset::make\(/',
            '/Grid::make\(/',
            '/Tabs::make\(/',
            '/Wizard::make\(/',
            
            // Custom labels and titles
            '/->label\([\'"][^\'"]+[\'"]\)/',
            '/->title\([\'"][^\'"]+[\'"]\)/',
            '/->heading\([\'"][^\'"]+[\'"]\)/',
            
            // Custom HTML content
            '/->html\(/',
            '/->state\(/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    protected function analyzeInfolistEntries(string $content): array
    {
        $entries = [];

        foreach ($this->infolistEntries as $component) {
            // Match patterns like: TextEntry::make('field_name')
            $pattern = "/(?<![:\w]){$component}::make\(['\"]([^'\"]+)['\"]\)/";

            preg_match_all($pattern, $content, $matches);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $entryName) {
                    // Check if this entry already has a label
                    $hasLabel = $this->hasLabel($content, $entryName, $component);

                    $entries[] = [
                        'name' => $entryName,
                        'component' => $component,
                        'has_label' => $hasLabel,
                        'default_label' => $this->generateLabel($entryName),
                        'translation_key' => $this->generateTranslationKey($entryName),
                    ];
                }
            }
        }

        return $entries;
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

    protected function analyzeCustomContent(string $content): array
    {
        $customContent = [];

        // Look for hardcoded strings in common methods
        $patterns = [
            // Labels in methods
            '/->label\([\'"]([^\'"]+)[\'"]\)/',
            '/->title\([\'"]([^\'"]+)[\'"]\)/',
            '/->heading\([\'"]([^\'"]+)[\'"]\)/',
            '/->description\([\'"]([^\'"]+)[\'"]\)/',
            
            // HTML content with hardcoded strings
            '/->html\([\'"]([^\'"]+)[\'"]\)/',
            '/->state\([\'"]([^\'"]+)[\'"]\)/',
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            
            if (! empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    // Skip very short strings or obvious field names
                    if (strlen($match) < 3 || Str::contains($match, '_')) {
                        continue;
                    }

                    $customContent[] = [
                        'content' => $match,
                        'type' => 'hardcoded_string',
                        'translation_key' => $this->generateTranslationKey(Str::snake($match)),
                    ];
                }
            }
        }

        return $customContent;
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
