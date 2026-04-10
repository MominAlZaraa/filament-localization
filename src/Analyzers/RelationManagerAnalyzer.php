<?php

namespace MominAlZaraa\FilamentLocalization\Analyzers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class RelationManagerAnalyzer
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

    protected array $actionComponents = [
        'Action',
        'CreateAction',
        'EditAction',
        'DeleteAction',
        'ViewAction',
        'BulkAction',
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

    public function analyze(string $relationManagerClass, $panel, bool $force = false): array
    {
        $reflection = new ReflectionClass($relationManagerClass);
        $filePath = $reflection->getFileName();

        if (! $filePath || ! File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);

        $analysis = [
            'relation_manager_class' => $relationManagerClass,
            'relation_manager_name' => class_basename($relationManagerClass),
            'file_path' => $filePath,
            'panel_id' => $panel->getId(),
            'fields' => [],
            'columns' => [],
            'actions' => [],
            'sections' => [],
            'filters' => [],
            'table_messages' => [],
            'key_value_auxiliary_labels' => [],
            'action_modal_copy' => [],
            'has_custom_content' => false,
            'labels' => [],
            'navigation' => [],
            'titles' => [],
        ];

        // Check if this relation manager has custom content that needs localization
        $analysis['has_custom_content'] = $this->hasCustomContent($content);

        // If no custom content and not in force mode, return early
        if (! $analysis['has_custom_content'] && ! $force) {
            return $analysis;
        }

        // Analyze form fields
        $analysis['fields'] = $this->analyzeFormFields($content);

        // Analyze table columns
        $analysis['columns'] = $this->analyzeTableColumns($content);

        // Analyze actions
        $analysis['actions'] = $this->analyzeActions($content);

        // Analyze sections and layout components
        $analysis['sections'] = $this->analyzeSections($content);

        // Analyze filters
        $analysis['filters'] = $this->analyzeFilters($content);

        $analysis['table_messages'] = $this->analyzeTableMessages($content);

        $analysis['key_value_auxiliary_labels'] = $this->analyzeKeyValueAuxiliaryLabels($content);

        $analysis['action_modal_copy'] = $this->analyzeActionModalCopy($content);

        // Analyze labels, navigation, and titles
        $analysis['labels'] = $this->analyzeLabels($content);
        $analysis['navigation'] = $this->analyzeNavigation($content);
        $analysis['titles'] = $this->analyzeTitles($content);

        return $analysis;
    }

    protected function hasCustomContent(string $content): bool
    {
        // Check for common patterns that indicate custom content
        $patterns = [
            // Form fields
            '/TextInput::make\(/',
            '/Textarea::make\(/',
            '/Select::make\(/',
            '/DatePicker::make\(/',
            '/Checkbox::make\(/',

            // Table columns
            '/TextColumn::make\(/',
            '/IconColumn::make\(/',
            '/ImageColumn::make\(/',
            '/CheckboxColumn::make\(/',

            // Actions
            '/Action::make\(/',
            '/CreateAction::make\(/',
            '/EditAction::make\(/',
            '/DeleteAction::make\(/',

            // Custom labels and titles
            '/->label\([\'"][^\'"]+[\'"]\)/',
            '/->title\([\'"][^\'"]+[\'"]\)/',
            '/->heading\([\'"][^\'"]+[\'"]\)/',

            // Table empty state / heading (Filament v3+)
            '/->emptyStateHeading\s*\(/',
            '/->emptyStateDescription\s*\(/',

            // Relation manager title property / getter
            '/protected\s+static\s+\?string\s+\$title\s*=\s*[\'"][^\'"]+[\'"]/',
            '/public\s+static\s+function\s+getTitle\s*\(/',

            // KeyValue auxiliary labels & action modals
            '/->keyLabel\s*\(/',
            '/->valueLabel\s*\(/',
            '/->modalHeading\s*\(/',
            '/->modalDescription\s*\(/',
            '/->modalSubmitActionLabel\s*\(/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    protected function analyzeFormFields(string $content): array
    {
        $fields = [];
        $foundFields = [];

        foreach ($this->formComponents as $component) {
            // Match patterns like: TextInput::make('field_name')
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
                    $literalLabel = $hasLabel ? $this->extractQuotedLabelAfterMake($content, $fieldName, $component) : null;

                    $fields[] = [
                        'name' => $fieldName,
                        'component' => $component,
                        'has_label' => $hasLabel,
                        'default_label' => $literalLabel ?? $this->generateLabel($fieldName),
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
            $pattern = "/(?<![:\w]){$column}::make\(['\"]([^'\"]+)['\"]\)/";

            preg_match_all($pattern, $content, $matches);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $columnName) {
                    // Check if this column already has a label
                    $hasLabel = $this->hasLabel($content, $columnName, $column);
                    $literalLabel = $hasLabel ? $this->extractQuotedLabelAfterMake($content, $columnName, $column) : null;

                    $columns[] = [
                        'name' => $columnName,
                        'component' => $column,
                        'has_label' => $hasLabel,
                        'default_label' => $literalLabel ?? $this->generateLabel($columnName),
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

            // Filament v5: CreateAction::make() / EditAction::make() with no name
            $emptyMakePattern = "/(?<![:\\w]){$component}::make\(\)/";
            preg_match_all($emptyMakePattern, $content, $emptyMatches, PREG_OFFSET_CAPTURE);

            foreach ($emptyMatches[0] ?? [] as $occurrence) {
                $syntheticName = $this->syntheticActionName($component);
                $hasLabel = $this->hasLabelAfterPosition($content, $occurrence[1], $component);

                $actions[] = [
                    'name' => $syntheticName,
                    'component' => $component,
                    'has_label' => $hasLabel,
                    'default_label' => $this->generateLabel($syntheticName),
                    'translation_key' => $this->generateTranslationKey($syntheticName),
                    'unnamed_make' => true,
                ];
            }
        }

        return $actions;
    }

    protected function syntheticActionName(string $component): string
    {
        return match ($component) {
            'CreateAction' => 'create',
            'EditAction' => 'edit',
            'DeleteAction' => 'delete',
            'ViewAction' => 'view',
            'BulkAction' => 'bulk',
            default => Str::snake(str_replace('Action', '', $component)),
        };
    }

    /**
     * Whether an unnamed {@see Action::make()} chain already has ->label( after the make() call.
     */
    protected function hasLabelAfterPosition(string $content, int $makePosition, string $component): bool
    {
        $prefix = $component.'::make()';
        $startPos = $makePosition + strlen($prefix);
        $slice = $this->sliceChainAfterMake($content, $startPos);

        return str_contains($slice, '->label(');
    }

    /**
     * @return list<array{field: string, chain_method: string, value: string, translation_key: string, has_translation: bool}>
     */
    protected function analyzeKeyValueAuxiliaryLabels(string $content): array
    {
        $out = [];

        $pattern = "/(?<![:\\w])KeyValue::make\(['\"]([^'\"]+)['\"]\)/";

        if (! preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $out;
        }

        foreach ($matches[1] as $idx => $fieldMatch) {
            $fieldName = $fieldMatch[0];
            $offset = $matches[0][$idx][1] + strlen($matches[0][$idx][0]);

            $fieldContent = $this->sliceChainAfterMake($content, $offset);

            foreach (['keyLabel' => 'key_label', 'valueLabel' => 'value_label'] as $method => $suffix) {
                if (preg_match('/->'.$method.'\s*\(\s*__\s*\(/s', $fieldContent)) {
                    continue;
                }

                if (preg_match('/->'.$method.'\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/s', $fieldContent, $m)) {
                    $baseKey = $this->generateTranslationKey($fieldName);

                    $out[] = [
                        'field' => $fieldName,
                        'chain_method' => $method,
                        'value' => $m[1],
                        'translation_key' => $baseKey.'_'.$suffix,
                        'has_translation' => false,
                    ];
                }
            }
        }

        return $out;
    }

    /**
     * @return list<array{filament_method: string, value: string, translation_key: string, has_translation: bool}>
     */
    protected function analyzeActionModalCopy(string $content): array
    {
        $out = [];
        $counts = [];

        $filamentMethods = [
            'modalHeading',
            'modalDescription',
            'modalSubmitActionLabel',
            'modalCancelActionLabel',
        ];

        foreach ($filamentMethods as $method) {
            $pattern = '/->'.$method.'\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/';

            if (! preg_match_all($pattern, $content, $matches)) {
                continue;
            }

            foreach ($matches[1] as $value) {
                $counts[$method] = ($counts[$method] ?? 0) + 1;
                $n = $counts[$method];
                $baseKey = Str::snake($method);
                $translationKey = $n === 1 ? $baseKey : $baseKey.'_'.$n;

                $out[] = [
                    'filament_method' => $method,
                    'value' => $value,
                    'translation_key' => $translationKey,
                    'has_translation' => false,
                ];
            }
        }

        return $out;
    }

    protected function sliceChainAfterMake(string $content, int $startPos): string
    {
        $len = strlen($content);
        $endPattern = "/\n\s*(?:\w+::make\(|[\]\}]\))/";

        if (preg_match($endPattern, $content, $endMatches, PREG_OFFSET_CAPTURE, $startPos)) {
            return substr($content, $startPos, $endMatches[0][1] - $startPos);
        }

        return substr($content, $startPos, min(4000, $len - $startPos));
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

    /**
     * @return list<array{type: string, value: string, translation_key: string, has_translation: bool}>
     */
    protected function analyzeTableMessages(string $content): array
    {
        $messages = [];
        $typeCounts = [];

        $specs = [
            'empty_state_heading' => 'emptyStateHeading',
            'empty_state_description' => 'emptyStateDescription',
            'table_heading' => 'heading',
        ];

        foreach ($specs as $type => $method) {
            $pattern = '/->'.$method.'\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/';

            if (! preg_match_all($pattern, $content, $matches)) {
                continue;
            }

            foreach ($matches[1] as $value) {
                $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                $suffix = $typeCounts[$type];
                $translationKey = $suffix === 1 ? $type : $type.'_'.$suffix;

                $messages[] = [
                    'type' => $type,
                    'method' => $method,
                    'value' => $value,
                    'translation_key' => $translationKey,
                    'has_translation' => false,
                ];
            }
        }

        return $messages;
    }

    protected function extractQuotedLabelAfterMake(string $content, string $fieldName, string $component): ?string
    {
        $makePattern = "/(?<![:\\w]){$component}::make\(['\"]".preg_quote($fieldName, '/')."['\"]\)/";

        if (! preg_match($makePattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $startPos = $matches[0][1] + strlen($matches[0][0]);
        $endPattern = "/\n\s*(?:\w+::make\(|[\]\}]\))/";

        if (preg_match($endPattern, $content, $endMatches, PREG_OFFSET_CAPTURE, $startPos)) {
            $endPos = $endMatches[0][1];
        } else {
            $endPos = strlen($content);
        }

        $fieldContent = substr($content, $startPos, $endPos - $startPos);

        if (preg_match('/->label\(\s*__\s*\(/s', $fieldContent)) {
            return null;
        }

        if (preg_match('/->label\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $fieldContent, $m)) {
            return $m[1];
        }

        return null;
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
        $key = Str::snake($fieldName);
        $key = preg_replace('/[^a-z0-9_.]+/', '_', $key);
        $key = preg_replace('/_+/', '_', $key);

        return trim($key, '_');
    }

    protected function analyzeLabels(string $content): array
    {
        $labels = [];

        // Check for existing static getTitle method (RelationManager uses static getTitle)
        $labelMethods = [
            'getTitle' => 'title',
        ];

        foreach ($labelMethods as $method => $type) {
            if (preg_match('/public\s+static\s+function\s+'.$method.'\s*\([^)]*\)\s*:\s*string\s*\{/s', $content, $m, PREG_OFFSET_CAPTURE)) {
                $body = $this->extractMethodBodyFromFirstBrace($content, $m[0][1] + strlen($m[0][0]) - 1);
                if ($body === null) {
                    continue;
                }

                if (preg_match('/return\s+__\s*\(/s', $body)) {
                    continue;
                }

                if (preg_match('/return\s+[\'"]([^\'"]+)[\'"]/s', $body, $sm)) {
                    $labels[] = [
                        'method' => $method,
                        'type' => $type,
                        'value' => $sm[1],
                        'has_translation' => false,
                        'translation_key' => $this->generateTranslationKey($type),
                        'is_static' => true,
                    ];
                }
            }
        }

        return $labels;
    }

    protected function analyzeNavigation(string $content): array
    {
        $navigation = [];

        // Check for navigation-related properties and methods
        $navigationMethods = [
            'getNavigationLabel' => 'navigation_label',
            'getNavigationIcon' => 'navigation_icon',
            'getNavigationSort' => 'navigation_sort',
            'getNavigationGroup' => 'navigation_group',
            'getNavigationBadge' => 'navigation_badge',
        ];

        foreach ($navigationMethods as $method => $type) {
            if (preg_match('/public\s+(?:static\s+)?function\s+'.$method.'\s*\([^)]*\)\s*:\s*[^{]*{[^}]*return\s+[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                $navigation[] = [
                    'method' => $method,
                    'type' => $type,
                    'value' => $matches[1],
                    'has_translation' => str_contains($matches[1], '__('),
                    'translation_key' => $this->generateTranslationKey($type),
                ];
            }
        }

        return $navigation;
    }

    protected function analyzeTitles(string $content): array
    {
        $titles = [];

        // Check for static title properties (RelationManager has $title property)
        $titleProperties = [
            'title' => 'title',
        ];

        foreach ($titleProperties as $property => $type) {
            if (preg_match('/protected\s+static\s+\?string\s+\$'.$property.'\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                $titles[] = [
                    'property' => $property,
                    'type' => $type,
                    'value' => $matches[1],
                    'has_translation' => str_contains($matches[1], '__('),
                    'translation_key' => $this->generateTranslationKey($type),
                    'is_static' => true,
                ];
            }
        }

        return $titles;
    }

    protected function extractMethodBodyFromFirstBrace(string $content, int $openBracePos): ?string
    {
        $depth = 0;
        $len = strlen($content);

        for ($i = $openBracePos; $i < $len; $i++) {
            $c = $content[$i];
            if ($c === '{') {
                $depth++;
            } elseif ($c === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $openBracePos + 1, $i - $openBracePos - 1);
                }
            }
        }

        return null;
    }
}
