<?php

namespace MominAlZaraa\FilamentLocalization\Generators;

use Illuminate\Support\Str;

class WidgetModifier
{
    public function modify(string $content, array $analysis, $panel): string
    {
        $content = $this->modifyStats($content, $analysis, $panel);

        return $content;
    }

    protected function modifyStats(string $content, array $analysis, $panel): string
    {
        foreach ($analysis['stats'] as $stat) {
            if ($stat['has_translation']) {
                continue;
            }

            $translationKey = $this->buildTranslationKey($analysis, $panel, $stat['translation_key']);
            $escapedValue = preg_quote($stat['value'], '/');

            if ($stat['type'] === 'stat_title') {
                // Replace Stat::make('Title', $value) with Stat::make(__('key'), $value)
                $pattern = '/Stat::make\s*\(\s*[\'"]' . $escapedValue . '[\'"]\s*,\s*([^)]+)\)/';
                $replacement = 'Stat::make(__(\'' . $translationKey . '\'), $1)';
                $content = preg_replace($pattern, $replacement, $content, 1);
            } elseif ($stat['type'] === 'stat_description') {
                // Replace ->description('Description') with ->description(__('key'))
                $pattern = '/->description\s*\(\s*[\'"]' . $escapedValue . '[\'"]\s*\)/';
                $replacement = '->description(__(\'' . $translationKey . '\'))';
                $content = preg_replace($pattern, $replacement, $content, 1);
            } elseif ($stat['type'] === 'widget_property') {
                // Replace ->property('Value') with ->property(__('key'))
                $pattern = '/->(title|label|placeholder|helper|hint)\s*\(\s*[\'"]' . $escapedValue . '[\'"]\s*\)/';
                $replacement = '->$1(__(\'' . $translationKey . '\'))';
                $content = preg_replace($pattern, $replacement, $content, 1);
            }
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
