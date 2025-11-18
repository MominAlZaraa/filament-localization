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

            if ($stat['type'] === 'stat_title') {
                // Replace Stat::make('Title', $value) with Stat::make(__('key'), $value)
                $pattern = '/Stat::make\s*\(\s*[\'"]'.$escapedValue.'[\'"]\s*,\s*([^)]+)\)/';
                $replacement = 'Stat::make(__(\''.$translationKey.'\'), $1)';
                $content = preg_replace($pattern, $replacement, $content, 1);
            } elseif ($stat['type'] === 'stat_description') {
                // Replace ->description('Description') with ->description(__('key'))
                $pattern = '/->description\s*\(\s*[\'"]'.$escapedValue.'[\'"]\s*\)/';
                $replacement = '->description(__(\''.$translationKey.'\'))';
                $content = preg_replace($pattern, $replacement, $content, 1);
            } elseif ($stat['type'] === 'widget_property') {
                // Replace ->property('Value') with ->property(__('key'))
                $pattern = '/->(title|label|placeholder|helper|hint)\s*\(\s*[\'"]'.$escapedValue.'[\'"]\s*\)/';
                $replacement = '->$1(__(\''.$translationKey.'\'))';
                $content = preg_replace($pattern, $replacement, $content, 1);
            }
        }

        return $content;
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
