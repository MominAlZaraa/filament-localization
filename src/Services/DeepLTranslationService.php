<?php

namespace MominAlZaraa\FilamentLocalization\Services;

use DeepL\TranslateTextOptions;
use DeepL\Translator;
use Illuminate\Support\Facades\Log;

class DeepLTranslationService
{
    protected Translator $translator;

    protected array $supportedLanguages = [
        'en' => 'EN',
        'el' => 'EL',
        'de' => 'DE',
        'fr' => 'FR',
        'es' => 'ES',
        'it' => 'IT',
        'pt' => 'PT',
        'ru' => 'RU',
        'ja' => 'JA',
        'zh' => 'ZH',
        'pl' => 'PL',
        'nl' => 'NL',
        'sv' => 'SV',
        'da' => 'DA',
        'no' => 'NO',
        'fi' => 'FI',
        'cs' => 'CS',
        'hu' => 'HU',
        'ro' => 'RO',
        'bg' => 'BG',
        'hr' => 'HR',
        'sk' => 'SK',
        'sl' => 'SL',
        'et' => 'ET',
        'lv' => 'LV',
        'lt' => 'LT',
        'uk' => 'UK',
        'tr' => 'TR',
        'ar' => 'AR',
        'ko' => 'KO',
        'id' => 'ID',
        'th' => 'TH',
        'vi' => 'VI',
    ];

    public function __construct()
    {
        $apiKey = config('filament-localization.deepl.api_key');

        if (empty($apiKey)) {
            // Don't throw in constructor - let isConfigured() handle it
            return;
        }

        // Check if DeepL package is available
        if (! class_exists('DeepL\Translator')) {
            // DeepL package not installed, skip initialization
            return;
        }

        try {
            $this->translator = new Translator($apiKey);
        } catch (\Exception $e) {
            // Handle case where DeepL package is not installed
            // This allows the service to be instantiated in tests
        }
    }

    public function isConfigured(): bool
    {
        try {
            $apiKey = config('filament-localization.deepl.api_key');

            return ! empty($apiKey);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    public function isLanguageSupported(string $languageCode): bool
    {
        return array_key_exists($languageCode, $this->supportedLanguages);
    }

    public function translate(string $text, string $sourceLanguage, string $targetLanguage): ?string
    {
        if (! $this->isConfigured()) {
            Log::warning('DeepL API key not configured');

            return null;
        }

        if (! $this->isLanguageSupported($sourceLanguage) || ! $this->isLanguageSupported($targetLanguage)) {
            Log::warning("Unsupported language: {$sourceLanguage} -> {$targetLanguage}");

            return null;
        }

        $sourceLangCode = $this->supportedLanguages[$sourceLanguage];
        $targetLangCode = $this->supportedLanguages[$targetLanguage];

        try {
            $result = $this->translator->translateText(
                $text,
                $sourceLangCode,
                $targetLangCode,
                [
                    TranslateTextOptions::PRESERVE_FORMATTING => true,
                ]
            );

            return $result->text;
        } catch (\Exception $e) {
            Log::error('DeepL translation failed: '.$e->getMessage());

            return null;
        }
    }

    public function translateArray(array $translations, string $sourceLanguage, string $targetLanguage): array
    {
        if (! $this->isConfigured()) {
            Log::warning('DeepL API key not configured');

            return [];
        }

        if (! $this->isLanguageSupported($sourceLanguage) || ! $this->isLanguageSupported($targetLanguage)) {
            Log::warning("Unsupported language: {$sourceLanguage} -> {$targetLanguage}");

            return [];
        }

        $sourceLangCode = $this->supportedLanguages[$sourceLanguage];
        $targetLangCode = $this->supportedLanguages[$targetLanguage];
        $translated = [];

        // Process in batches to avoid API limits
        $batchSize = config('filament-localization.deepl.batch_size', 50);
        $chunks = array_chunk($translations, $batchSize, true);

        foreach ($chunks as $chunk) {
            $texts = array_values($chunk);
            $keys = array_keys($chunk);

            try {
                $results = $this->translator->translateText(
                    $texts,
                    $sourceLangCode,
                    $targetLangCode,
                    [
                        TranslateTextOptions::PRESERVE_FORMATTING => true,
                    ]
                );

                // Results are always an array from DeepL API

                foreach ($results as $index => $result) {
                    if (isset($keys[$index])) {
                        $translated[$keys[$index]] = $result->text;
                    }
                }
            } catch (\Exception $e) {
                Log::error('DeepL batch translation failed: '.$e->getMessage());

                // Fallback to individual translations
                foreach ($chunk as $key => $text) {
                    try {
                        $result = $this->translator->translateText(
                            $text,
                            $sourceLangCode,
                            $targetLangCode,
                            [
                                TranslateTextOptions::PRESERVE_FORMATTING => true,
                            ]
                        );
                        $translated[$key] = $result->text;
                    } catch (\Exception $fallbackException) {
                        Log::error("DeepL individual translation failed for key '{$key}': ".$fallbackException->getMessage());
                        $translated[$key] = $text; // Keep original text as fallback
                    }
                }
            }
        }

        return $translated;
    }

    public function getUsage(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $usage = $this->translator->getUsage();

            return [
                'character_count' => $usage->character->count,
                'character_limit' => $usage->character->limit,
                'document_count' => $usage->document->count ?? 0,
                'document_limit' => $usage->document->limit ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('DeepL usage check failed: '.$e->getMessage());

            return null;
        }
    }

    public function getSupportedLanguagesFromAPI(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $languages = $this->translator->getGlossaryLanguages();
            $supported = [];

            foreach ($languages as $language) {
                $supported[$language->sourceLang] = $language->sourceLang;
            }

            return $supported;
        } catch (\Exception $e) {
            Log::error('DeepL supported languages check failed: '.$e->getMessage());

            return [];
        }
    }

    public function detectLanguage(string $text): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $result = $this->translator->translateText($text, null, 'EN');

            return $result->detectedSourceLang;
        } catch (\Exception $e) {
            Log::error('DeepL language detection failed: '.$e->getMessage());

            return null;
        }
    }
}
