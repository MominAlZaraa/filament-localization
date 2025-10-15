<?php

namespace MominAlZaraa\FilamentLocalization\Analyzers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class WidgetAnalyzer
{
    public function analyze(string $widgetClass, $panel): array
    {
        $reflection = new ReflectionClass($widgetClass);
        $filePath = $reflection->getFileName();

        if (! $filePath || ! File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);

        $analysis = [
            'widget_name' => class_basename($widgetClass),
            'panel' => $panel->getId(),
            'stats' => $this->analyzeStats($content),
            'custom_content' => $this->hasCustomContent($content),
        ];

        return $analysis;
    }

    protected function analyzeStats(string $content): array
    {
        $stats = [];

        // Pattern to match Stat::make() calls with hardcoded strings
        $pattern = '/Stat::make\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[^)]+\)/';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $stats[] = [
                    'type' => 'stat_title',
                    'value' => $match[1],
                    'translation_key' => Str::snake($match[1]),
                    'has_translation' => false,
                ];
            }
        }

        // Pattern to match ->description() calls with hardcoded strings
        $descriptionPattern = '/->description\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/';

        if (preg_match_all($descriptionPattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $stats[] = [
                    'type' => 'stat_description',
                    'value' => $match[1],
                    'translation_key' => Str::snake($match[1]),
                    'has_translation' => false,
                ];
            }
        }

        // Pattern to match other hardcoded strings in widget methods
        $methodPattern = '/->(?:title|label|placeholder|helper|hint)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/';

        if (preg_match_all($methodPattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $stats[] = [
                    'type' => 'widget_property',
                    'value' => $match[1],
                    'translation_key' => Str::snake($match[1]),
                    'has_translation' => false,
                ];
            }
        }

        return $stats;
    }

    protected function hasCustomContent(string $content): bool
    {
        $patterns = [
            // Stat::make with hardcoded strings
            '/Stat::make\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[^)]+\)/',

            // Description with hardcoded strings
            '/->description\s*\(\s*[\'"][^\'"]+[\'"]\s*\)/',

            // Other widget properties with hardcoded strings
            '/->(?:title|label|placeholder|helper|hint)\s*\(\s*[\'"][^\'"]+[\'"]\s*\)/',

            // String literals in widget methods
            '/[\'"][A-Z][a-zA-Z\s]+[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}
