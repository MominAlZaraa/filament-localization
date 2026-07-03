<?php

namespace MominAlZaraa\FilamentLocalization\Generators;

use Illuminate\Support\Str;

class WidgetModifier
{
    public function modify(string $content, array $analysis, $panel, bool $force = false): string
    {
        $content = $this->modifyStats($content, $analysis, $panel, $force);

        // If force mode is enabled, update any existing translation keys to use the correct panel
        if ($force) {
            $content = $this->updateTranslationKeysToCorrectPanel($content, $panel);
        }

        return $content;
    }

    protected function modifyStats(string $content, array $analysis, $panel, bool $force = false): string
    {
        foreach ($analysis['stats'] as $stat) {
            if ($stat['has_translation'] && ! $force) {
                continue;
            }

            $translationKey = $this->buildTranslationKey($analysis, $panel, $stat['translation_key']);
            $escapedValue = preg_quote($stat['value'], '/');

            $content = match ($stat['type']) {
                'stat_title' => $this->replaceOnce(
                    $content,
                    '/Stat::make\s*\(\s*[\'"]'.$escapedValue.'[\'"]\s*,\s*([^)]+)\)/',
                    'Stat::make(__(\''.$translationKey.'\'), $1)'
                ),
                'stat_description' => $this->replaceOnce(
                    $content,
                    '/->description\s*\(\s*[\'"]'.$escapedValue.'[\'"]\s*\)/',
                    '->description(__(\''.$translationKey.'\'))'
                ),
                'widget_property' => $this->replaceOnce(
                    $content,
                    '/->(title|heading|label|placeholder|helper|hint|description)\s*\(\s*[\'"]'.$escapedValue.'[\'"]\s*\)/',
                    '->$1(__(\''.$translationKey.'\'))'
                ),
                'widget_heading_property' => $this->modifyHeadingProperty($content, $translationKey),
                'widget_heading_method' => $this->modifyHeadingMethod($content, $translationKey),
                default => $content,
            };
        }

        return $content;
    }

    protected function modifyHeadingProperty(string $content, string $translationKey): string
    {
        $patterns = [
            '/protected\s+static\s+\?string\s+\$heading\s*=\s*[\'"][^\'"]+[\'"]\s*;/' => 'protected static ?string $heading = null;',
            '/protected\s+static\s+string\s+\$heading\s*=\s*[\'"][^\'"]+[\'"]\s*;/' => 'protected static ?string $heading = null;',
        ];

        $replaced = false;
        foreach ($patterns as $search => $replacement) {
            $newContent = preg_replace($search, $replacement, $content, 1);
            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $replaced = true;

                break;
            }
        }

        if (! $replaced) {
            return $content;
        }

        $method = "    public static function getHeading(): ?string\n    {\n        return __('{$translationKey}');\n    }";

        if (preg_match('/public\s+(?:static\s+)?function\s+getHeading\s*\([^)]*\)\s*:\s*\??string\s*\{[^}]*\}/s', $content)) {
            return preg_replace(
                '/public\s+(?:static\s+)?function\s+getHeading\s*\([^)]*\)\s*:\s*\??string\s*\{[^}]*\}/s',
                $method,
                $content,
                1
            );
        }

        $pattern = '/(\n\s*}\s*)$/';
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[0][1];
            $content = substr_replace($content, "\n".$method."\n", $insertPosition, 0);
        }

        return $content;
    }

    protected function modifyHeadingMethod(string $content, string $translationKey): string
    {
        $method = "    public static function getHeading(): ?string\n    {\n        return __('{$translationKey}');\n    }";

        return preg_replace(
            '/public\s+(?:static\s+)?function\s+getHeading\s*\([^)]*\)\s*:\s*\??string\s*\{[^}]*\}/s',
            $method,
            $content,
            1
        );
    }

    protected function replaceOnce(string $content, string $pattern, string $replacement): string
    {
        $newContent = preg_replace($pattern, $replacement, $content, 1);

        return $newContent ?? $content;
    }

    protected function updateTranslationKeysToCorrectPanel(string $content, $panel): string
    {
        $currentPanelId = $panel->getId();
        $otherPanels = config('filament-localization.other_panel_ids', ['admin', 'Admin']);

        foreach ($otherPanels as $otherPanel) {
            if ($otherPanel === $currentPanelId) {
                continue;
            }

            // Replace __('filament/{otherPanel}/...') with __('filament/{currentPanelId}/...')
            $pattern = "/__\(['\"]filament\/{$otherPanel}\/([^'\"]+)['\"]\)/";
            $replacement = "__('filament/{$currentPanelId}/\$1')";
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    protected function buildTranslationKey(array $analysis, $panel, string $key): string
    {
        $prefix = config('filament-localization.translation_key_prefix', 'filament');
        $structure = config('filament-localization.structure', 'panel-based');
        $widgetName = Str::snake($analysis['widget_name']);

        return match ($structure) {
            'flat' => "{$prefix}.{$key}",
            'nested' => "{$prefix}.{$widgetName}.{$key}",
            'panel-based' => "{$prefix}/{$panel->getId()}/{$widgetName}.{$key}",
            default => "{$prefix}/{$panel->getId()}/{$widgetName}.{$key}",
        };
    }
}
