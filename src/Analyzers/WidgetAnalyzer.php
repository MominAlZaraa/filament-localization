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
            'file_path' => $filePath,
            'panel' => $panel->getId(),
            'stats' => $this->analyzeStats($content),
            'custom_content' => $this->hasCustomContent($content),
        ];

        return $analysis;
    }

    protected function analyzeStats(string $content): array
    {
        $stats = [];
        $seenKeys = [];

        $this->collectStat($stats, $seenKeys, 'stat_title', function (string $value) use ($content) {
            $pattern = '/Stat::make\s*\(\s*[\'"]'.preg_quote($value, '/').'[\'"]\s*,\s*[^)]+\)/';

            return preg_match($pattern, $content) === 1;
        }, $content, '/Stat::make\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[^)]+\)/');

        $this->collectStat($stats, $seenKeys, 'stat_description', fn (string $value) => true, $content, '/->description\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/');

        $this->collectStat($stats, $seenKeys, 'widget_property', fn (string $value) => true, $content, '/->(?:title|heading|label|placeholder|helper|hint|description)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/');

        $this->collectHeadingProperty($stats, $seenKeys, $content);

        return $stats;
    }

    /**
     * @param  callable(string): bool  $shouldInclude
     */
    protected function collectStat(array &$stats, array &$seenKeys, string $type, callable $shouldInclude, string $content, string $pattern): void
    {
        if (! preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            $value = $match[1];

            if (! $shouldInclude($value)) {
                continue;
            }

            $translationKey = $this->generateTranslationKey($value, $type);
            $dedupeKey = "{$type}:{$translationKey}";

            if (isset($seenKeys[$dedupeKey])) {
                continue;
            }

            $seenKeys[$dedupeKey] = true;

            $stats[] = [
                'type' => $type,
                'value' => $value,
                'translation_key' => $translationKey,
                'has_translation' => str_contains($match[0], '__('),
            ];
        }
    }

    protected function collectHeadingProperty(array &$stats, array &$seenKeys, string $content): void
    {
        $patterns = [
            '/protected\s+static\s+\?string\s+\$heading\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/',
            '/protected\s+static\s+string\s+\$heading\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $content, $match)) {
                continue;
            }

            $value = $match[1];
            $translationKey = 'heading';
            $dedupeKey = "heading_property:{$translationKey}";

            if (isset($seenKeys[$dedupeKey])) {
                continue;
            }

            $seenKeys[$dedupeKey] = true;

            $stats[] = [
                'type' => 'widget_heading_property',
                'value' => $value,
                'translation_key' => $translationKey,
                'has_translation' => false,
            ];
        }

        if (preg_match('/public\s+(?:static\s+)?function\s+getHeading\s*\([^)]*\)\s*:\s*\??string\s*\{[^}]*return\s+[\'"]([^\'"]+)[\'"]\s*;/s', $content, $match)) {
            $value = $match[1];
            $translationKey = 'heading';
            $dedupeKey = "heading_method:{$translationKey}";

            if (! isset($seenKeys[$dedupeKey])) {
                $seenKeys[$dedupeKey] = true;

                $stats[] = [
                    'type' => 'widget_heading_method',
                    'value' => $value,
                    'translation_key' => $translationKey,
                    'has_translation' => false,
                ];
            }
        }
    }

    protected function generateTranslationKey(string $value, string $type): string
    {
        if ($type === 'widget_heading_property' || $type === 'widget_heading_method') {
            return 'heading';
        }

        return Str::snake($value);
    }

    protected function hasCustomContent(string $content): bool
    {
        $patterns = [
            '/Stat::make\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[^)]+\)/',
            '/->(?:description|title|heading|label|placeholder|helper|hint)\s*\(\s*[\'"][^\'"]+[\'"]\s*\)/',
            '/protected\s+static\s+(?:\?string|string)\s+\$heading\s*=\s*[\'"][^\'"]+[\'"]/',
            '/function\s+getHeading\s*\(/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}
