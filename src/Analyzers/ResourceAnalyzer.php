<?php

namespace MominAlZaraa\FilamentLocalization\Analyzers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class ResourceAnalyzer
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

    public function analyze(string $resourceClass, $panel): array
    {
        $reflection = new ReflectionClass($resourceClass);
        $filePath = $reflection->getFileName();

        if (! $filePath || ! File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);

        $analysis = [
            'resource_class' => $resourceClass,
            'resource_name' => class_basename($resourceClass),
            'file_path' => $filePath,
            'panel_id' => $panel->getId(),
            'fields' => [],
            'actions' => [],
            'columns' => [],
            'sections' => [],
            'tabs' => [],
            'filters' => [],
            'relation_managers' => [],
            'schema_files' => [], // Track separate schema files
            'static_properties' => $this->analyzeStaticProperties($content),
        ];

        // Detect and load separate schema files (like UserForm, UserTable, etc.)
        $schemaFiles = $this->detectSchemaFiles($content, $resourceClass);
        $analysis['schema_files'] = $schemaFiles;

        // Analyze form fields (from resource file and schema files)
        $analysis['fields'] = $this->analyzeFormFields($content);

        // Also analyze fields from separate schema files
        foreach ($schemaFiles['form'] ?? [] as $schemaFile) {
            if (File::exists($schemaFile['path'])) {
                $schemaContent = File::get($schemaFile['path']);
                $schemaFields = $this->analyzeFormFields($schemaContent);

                // Mark fields as being from a schema file
                foreach ($schemaFields as &$field) {
                    $field['schema_file'] = $schemaFile['path'];
                    $field['schema_class'] = $schemaFile['class'];
                }

                $analysis['fields'] = array_merge($analysis['fields'], $schemaFields);
            }
        }

        // Analyze table columns
        $analysis['columns'] = $this->analyzeTableColumns($content);

        // Also analyze columns from separate table schema files
        foreach ($schemaFiles['table'] ?? [] as $schemaFile) {
            if (File::exists($schemaFile['path'])) {
                $schemaContent = File::get($schemaFile['path']);
                $schemaColumns = $this->analyzeTableColumns($schemaContent);

                // Mark columns as being from a schema file
                foreach ($schemaColumns as &$column) {
                    $column['schema_file'] = $schemaFile['path'];
                    $column['schema_class'] = $schemaFile['class'];
                }

                $analysis['columns'] = array_merge($analysis['columns'], $schemaColumns);
            }
        }

        // Analyze actions
        $analysis['actions'] = $this->analyzeActions($content);

        // Analyze sections and layout components
        $analysis['sections'] = $this->analyzeSections($content);

        // Analyze filters
        $analysis['filters'] = $this->analyzeFilters($content);

        // Analyze relation managers
        $analysis['relation_managers'] = $this->analyzeRelationManagers($resourceClass);

        return $analysis;
    }

    protected function analyzeStaticProperties(string $content): array
    {
        $properties = [
            'model_label' => null,
            'navigation_label' => null,
            'plural_model_label' => null,
            'has_get_model_label' => false,
        ];

        // Check for static properties
        if (preg_match('/protected static \?\w+ \$modelLabel\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $properties['model_label'] = $matches[1];
        }

        if (preg_match('/protected static \?\w+ \$navigationLabel\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $properties['navigation_label'] = $matches[1];
        }

        if (preg_match('/protected static \?\w+ \$pluralModelLabel\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $properties['plural_model_label'] = $matches[1];
        }

        // Check for getModelLabel method
        if (preg_match('/public static function getModelLabel\(\)/', $content)) {
            $properties['has_get_model_label'] = true;
        }

        return $properties;
    }

    protected function analyzeFormFields(string $content): array
    {
        $fields = [];
        $foundFields = []; // Track found fields to avoid duplicates

        foreach ($this->formComponents as $component) {
            // Match patterns like: TextInput::make('field_name')
            // Use word boundary or namespace separator to ensure exact component name match
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

                    // Check if this field has a description
                    $hasDescription = $this->hasDescription($content, $fieldName, $component);

                    // Check if this is a Select component with hardcoded options
                    $selectOptions = [];
                    $hasDefault = false;
                    if ($component === 'Select') {
                        $selectOptions = $this->analyzeSelectOptions($content, $fieldName, $component);
                        $hasDefault = $this->hasDefault($content, $fieldName, $component);
                    }

                    $fields[] = [
                        'name' => $fieldName,
                        'component' => $component,
                        'has_label' => $hasLabel,
                        'has_description' => $hasDescription,
                        'has_default' => $hasDefault,
                        'select_options' => $selectOptions,
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
            $pattern = "/{$column}::make\(['\"]([^'\"]+)['\"]\)/";

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

        // Match patterns like: Action::make('action_name')
        $pattern = "/Action::make\(['\"]([^'\"]+)['\"]\)/";

        preg_match_all($pattern, $content, $matches);

        if (! empty($matches[1])) {
            foreach ($matches[1] as $actionName) {
                // Check if this action already has a label
                $hasLabel = $this->hasLabel($content, $actionName, 'Action');

                $actions[] = [
                    'name' => $actionName,
                    'has_label' => $hasLabel,
                    'default_label' => $this->generateLabel($actionName),
                    'translation_key' => $this->generateTranslationKey($actionName),
                ];
            }
        }

        return $actions;
    }

    protected function analyzeSections(string $content): array
    {
        $sections = [];
        $titleCounts = []; // Track how many times each title appears

        foreach ($this->layoutComponents as $component) {
            // Match patterns like: Section::make('Section Title') - hardcoded strings
            $hardcodedPattern = "/{$component}::make\(['\"]([^'\"]+)['\"]\)/";
            preg_match_all($hardcodedPattern, $content, $hardcodedMatches);

            if (! empty($hardcodedMatches[1])) {
                foreach ($hardcodedMatches[1] as $sectionTitle) {
                    // Skip if it looks like a field name (snake_case)
                    if (Str::contains($sectionTitle, '_')) {
                        continue;
                    }

                    // Check if this section has a description
                    $hasDescription = $this->hasLayoutDescription($content, $sectionTitle, $component);

                    // Generate unique translation key
                    $baseKey = $this->generateTranslationKey(Str::snake($sectionTitle));
                    $uniqueKey = $this->generateUniqueTranslationKey($baseKey, $titleCounts);

                    $sections[] = [
                        'title' => $sectionTitle,
                        'component' => $component,
                        'translation_key' => $uniqueKey,
                        'has_translation' => false,
                        'has_description' => $hasDescription,
                    ];
                }
            }

            // Match patterns like: Section::make(__('translation.key')) - translation functions
            $translationPattern = "/{$component}::make\(__\(['\"]([^'\"]+)['\"]\)\)/";
            preg_match_all($translationPattern, $content, $translationMatches);

            if (! empty($translationMatches[1])) {
                foreach ($translationMatches[1] as $translationKey) {
                    // Extract the actual title from the translation key for display purposes
                    $title = $this->extractTitleFromTranslationKey($translationKey);

                    // Check if this section has a description by looking for the full make() call with translation key
                    $hasDescription = $this->hasLayoutDescriptionWithTranslationKey($content, $translationKey, $component);

                    // Generate unique translation key
                    $baseKey = $this->generateTranslationKey($title);
                    $uniqueKey = $this->generateUniqueTranslationKey($baseKey, $titleCounts);

                    $sections[] = [
                        'title' => $title,
                        'component' => $component,
                        'translation_key' => $uniqueKey,
                        'original_translation_key' => $translationKey, // Keep the original for reference
                        'has_translation' => true,
                        'has_description' => $hasDescription,
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

    protected function analyzeRelationManagers(string $resourceClass): array
    {
        $relationManagers = [];

        try {
            $reflection = new ReflectionClass($resourceClass);

            if ($reflection->hasMethod('getRelations')) {
                $method = $reflection->getMethod('getRelations');
                $method->setAccessible(true);

                // Try to get relation managers (this might not work for all resources)
                // We'll need to parse the file content instead
                $filePath = $reflection->getFileName();
                $content = File::get($filePath);

                // Look for relation manager references
                preg_match_all('/([A-Za-z]+RelationManager)::class/', $content, $matches);

                if (! empty($matches[1])) {
                    foreach ($matches[1] as $relationManager) {
                        $relationManagers[] = [
                            'class' => $relationManager,
                            'name' => str_replace('RelationManager', '', $relationManager),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if we can't analyze relation managers
        }

        return $relationManagers;
    }

    protected function hasLabel(string $content, string $fieldName, string $component): bool
    {
        // Look for ->label() after the make() call for this specific field
        // Match from make() to the next field separator (comma followed by whitespace and the next component)
        // or to the end of the components array (closing bracket/paren)

        // First, find the make() call for this field
        $makePattern = "/{$component}::make\(['\"]".preg_quote($fieldName, '/')."['\"]\)/";

        if (! preg_match($makePattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $startPos = $matches[0][1] + strlen($matches[0][0]);

        // Find the next make() call or closing bracket/paren that ends the field
        // Match: newline + whitespace + (component::make OR ] OR })
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

    protected function hasDescription(string $content, string $fieldName, string $component): bool
    {
        // Look for ->description() after the make() call for this specific field
        // Similar to hasLabel but looking for ->description(

        // First, find the make() call for this field
        $makePattern = "/{$component}::make\(['\"]".preg_quote($fieldName, '/')."['\"]\)/";

        if (! preg_match($makePattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $startPos = $matches[0][1] + strlen($matches[0][0]);

        // Find the next make() call or closing bracket/paren that ends the field
        // Match: newline + whitespace + (component::make OR ] OR })
        $endPattern = "/\n\s*(?:\w+::make\(|[\]\}]\))/";

        if (preg_match($endPattern, $content, $endMatches, PREG_OFFSET_CAPTURE, $startPos)) {
            $endPos = $endMatches[0][1];
        } else {
            $endPos = strlen($content);
        }

        // Extract just this field's definition
        $fieldContent = substr($content, $startPos, $endPos - $startPos);

        // Check if this field's content contains ->description(
        return str_contains($fieldContent, '->description(');
    }

    protected function hasDefault(string $content, string $fieldName, string $component): bool
    {
        // Look for ->default() after the make() call for this specific field
        $makePattern = "/{$component}::make\(['\"]".preg_quote($fieldName, '/')."['\"]\)/";

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

        // Check if this field's content contains ->default(
        return str_contains($fieldContent, '->default(');
    }

    protected function analyzeSelectOptions(string $content, string $fieldName, string $component): array
    {
        $options = [];

        // Look for ->options([...]) after the make() call for this specific field
        $makePattern = "/{$component}::make\(['\"]".preg_quote($fieldName, '/')."['\"]\)/";

        if (! preg_match($makePattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $options;
        }

        $startPos = $matches[0][1] + strlen($matches[0][0]);

        // Find the next make() call that ends the field
        // Look for a newline followed by whitespace and then a component make() call
        // This ensures we don't match closing brackets within the field definition
        $endPattern = "/\n\s+\w+::make\(/";

        if (preg_match($endPattern, $content, $endMatches, PREG_OFFSET_CAPTURE, $startPos)) {
            $endPos = $endMatches[0][1];
        } else {
            $endPos = strlen($content);
        }

        // Extract just this field's definition
        $fieldContent = substr($content, $startPos, $endPos - $startPos);

        // Look for ->options([...]) pattern - handle multiline arrays
        if (preg_match('/->options\s*\(\s*\[(.*?)\]\s*\)/s', $fieldContent, $optionsMatch)) {
            $optionsContent = $optionsMatch[1];

            // Parse the options array content
            // Look for patterns like: 'key' => 'value' or 'key' => __('translation')
            // Also handle numeric keys like: 0 => __('no'), 1 => __('yes')
            // Handle multiline format with proper whitespace and indentation
            preg_match_all("/(?:['\"]([^'\"]+)['\"]|(\d+))\s*=>\s*(?:['\"]([^'\"]+)['\"]|__\(['\"]([^'\"]+)['\"]\))/", $optionsContent, $optionMatches, PREG_SET_ORDER);

            foreach ($optionMatches as $optionMatch) {
                $key = $optionMatch[1] ?: $optionMatch[2]; // String key or numeric key
                $value = $optionMatch[3] ?: $optionMatch[4] ?: $key; // Use value, translation key, or key as fallback
                $isTranslation = isset($optionMatch[4]); // Check if it's a translation function

                // For boolean select fields (0/1), use the display value for translation key
                if ($isTranslation) {
                    // Already has translation, extract the key part
                    $translationKey = $value;
                } else {
                    $translationKey = $this->generateTranslationKey($key);

                    // Special handling for boolean select fields
                    if (is_numeric($key) && ! empty($value)) {
                        // Use the display value (like 'No', 'Yes') instead of the numeric key
                        $translationKey = $this->generateTranslationKey($value);
                    }
                }

                $options[] = [
                    'key' => $key,
                    'value' => $value,
                    'is_translation' => $isTranslation,
                    'translation_key' => $translationKey,
                ];
            }
        }

        return $options;
    }

    protected function hasLayoutDescription(string $content, string $title, string $component): bool
    {
        // Look for ->description() after the make() call for this specific layout component
        // Similar to hasDescription but for layout components

        // First, try to find the make() call with hardcoded string
        $makePattern = "/{$component}::make\(['\"]".preg_quote($title, '/')."['\"]\)/";
        $startPos = 0;

        if (preg_match($makePattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $startPos = $matches[0][1] + strlen($matches[0][0]);
        } else {
            // Try to find make() call with translation key
            $translationPattern = "/{$component}::make\(__\(['\"][^'\"]*['\"]\)\)/";
            if (preg_match($translationPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $startPos = $matches[0][1] + strlen($matches[0][0]);
            } else {
                return false;
            }
        }

        // Find the next make() call or closing bracket/paren that ends the component
        // Match: newline + whitespace + (component::make OR ] OR })
        $endPattern = "/\n\s*(?:\w+::make\(|[\]\}]\))/";

        if (preg_match($endPattern, $content, $endMatches, PREG_OFFSET_CAPTURE, $startPos)) {
            $endPos = $endMatches[0][1];
        } else {
            $endPos = strlen($content);
        }

        // Extract just this component's definition
        $componentContent = substr($content, $startPos, $endPos - $startPos);

        // Check if this component's content contains ->description(
        return str_contains($componentContent, '->description(');
    }

    protected function hasLayoutDescriptionWithTranslationKey(string $content, string $translationKey, string $component): bool
    {
        // Look for ->description() after a make() call with a specific translation key
        $escapedKey = preg_quote($translationKey, '/');
        $makePattern = "/{$component}::make\(__\(['\"]".$escapedKey."['\"]\)\)/";

        if (! preg_match($makePattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $startPos = $matches[0][1] + strlen($matches[0][0]);

        // Find the next make() call or closing bracket/paren that ends the component
        $endPattern = "/\n\s*(?:\w+::make\(|[\]\}]\))/";

        if (preg_match($endPattern, $content, $endMatches, PREG_OFFSET_CAPTURE, $startPos)) {
            $endPos = $endMatches[0][1];
        } else {
            $endPos = strlen($content);
        }

        // Extract just this component's definition
        $componentContent = substr($content, $startPos, $endPos - $startPos);

        // Check if this component's content contains ->description(
        return str_contains($componentContent, '->description(');
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

    protected function generateTranslationKey(string $fieldName): string
    {
        return Str::snake($fieldName);
    }

    protected function generateUniqueTranslationKey(string $baseKey, array &$titleCounts): string
    {
        if (! isset($titleCounts[$baseKey])) {
            $titleCounts[$baseKey] = 0;
        }

        $titleCounts[$baseKey]++;

        // If this is the first occurrence, use the base key
        if ($titleCounts[$baseKey] === 1) {
            return $baseKey;
        }

        // For subsequent occurrences, append a number
        return $baseKey.'_'.$titleCounts[$baseKey];
    }

    protected function extractTitleFromTranslationKey(string $translationKey): string
    {
        // Extract the last part of the translation key for display
        // e.g., 'filament/admin.details' -> 'details'
        $parts = explode('.', $translationKey);
        $lastPart = end($parts);

        // Convert snake_case to title case for display
        return Str::title(str_replace('_', ' ', $lastPart));
    }

    protected function detectSchemaFiles(string $content, string $resourceClass): array
    {
        $schemaFiles = [
            'form' => [],
            'table' => [],
        ];

        // Detect form schema class: pattern like "return SomeClass::configure($schema)"
        // This can be in the form() method
        if (preg_match('/function\s+form\s*\([^)]*\)\s*:\s*Schema\s*{[^}]*return\s+([A-Za-z\\\\]+)::configure\s*\(/s', $content, $matches)) {
            $schemaClass = $matches[1];
            $schemaFiles['form'][] = $this->resolveSchemaClass($schemaClass, $resourceClass, $content);
        }

        // Detect table schema class: pattern like "return SomeClass::configure($table)"
        // This can be in the table() method
        if (preg_match('/function\s+table\s*\([^)]*\)\s*:\s*Table\s*{[^}]*return\s+([A-Za-z\\\\]+)::configure\s*\(/s', $content, $matches)) {
            $schemaClass = $matches[1];
            $schemaFiles['table'][] = $this->resolveSchemaClass($schemaClass, $resourceClass, $content);
        }

        return $schemaFiles;
    }

    protected function resolveSchemaClass(string $schemaClass, string $resourceClass, string $content): array
    {
        // If the class name doesn't have a namespace, we need to resolve it from the use statements
        if (! Str::contains($schemaClass, '\\')) {
            // Extract use statements from the content
            preg_match_all('/use\s+([^;]+);/', $content, $useMatches);

            foreach ($useMatches[1] as $useStatement) {
                if (Str::endsWith($useStatement, '\\'.$schemaClass) || $useStatement === $schemaClass) {
                    $schemaClass = $useStatement;
                    break;
                }

                // Handle aliased imports (use X as Y)
                if (preg_match('/(.+)\s+as\s+(.+)/', $useStatement, $aliasMatch)) {
                    if (trim($aliasMatch[2]) === $schemaClass) {
                        $schemaClass = trim($aliasMatch[1]);
                        break;
                    }
                }
            }

            // If still not resolved, assume it's in the same namespace as the resource
            if (! Str::contains($schemaClass, '\\')) {
                $resourceNamespace = (new ReflectionClass($resourceClass))->getNamespaceName();
                $schemaClass = $resourceNamespace.'\\'.$schemaClass;
            }
        }

        // Try to get the file path for this class
        try {
            $reflection = new ReflectionClass($schemaClass);
            $filePath = $reflection->getFileName();
        } catch (\Exception $e) {
            $filePath = null;
        }

        return [
            'class' => $schemaClass,
            'path' => $filePath,
        ];
    }
}
