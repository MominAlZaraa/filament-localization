<?php

namespace MominAlZaraa\FilamentLocalization\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MominAlZaraa\FilamentLocalization\Analyzers\ResourceAnalyzer;
use MominAlZaraa\FilamentLocalization\Generators\TranslationFileGenerator;
use MominAlZaraa\FilamentLocalization\Generators\ResourceModifier;

class LocalizationService
{
    protected ResourceAnalyzer $analyzer;

    protected TranslationFileGenerator $translationGenerator;

    protected ResourceModifier $resourceModifier;

    protected StatisticsService $statistics;

    public function __construct(StatisticsService $statistics = null)
    {
        $this->analyzer = new ResourceAnalyzer;
        $this->translationGenerator = new TranslationFileGenerator;
        $this->resourceModifier = new ResourceModifier;
        $this->statistics = $statistics ?? app(StatisticsService::class);
    }

    public function processResource(string $resourceClass, $panel, array $locales, bool $dryRun = false): void
    {
        try {
            // Analyze the resource to find all localizable fields
            $analysis = $this->analyzer->analyze($resourceClass, $panel);

            if (empty($analysis['fields']) && empty($analysis['actions']) && empty($analysis['columns'])) {
                return; // Nothing to localize
            }

            // Generate translation files for each locale
            foreach ($locales as $locale) {
                $this->translationGenerator->generate(
                    $analysis,
                    $panel->getId(),
                    $locale,
                    $dryRun
                );
            }

            // Modify the resource file to use translation keys
            if (! $dryRun) {
                $this->resourceModifier->modify($resourceClass, $analysis, $panel);
            }

            // Update statistics
            $this->statistics->incrementResourcesProcessed();
            $this->statistics->incrementFieldsLocalized(count($analysis['fields']));
            $this->statistics->incrementActionsLocalized(count($analysis['actions']));
            $this->statistics->incrementColumnsLocalized(count($analysis['columns']));
        } catch (\Exception $e) {
            $this->statistics->addError("Failed to process {$resourceClass}: {$e->getMessage()}");
        }
    }
}
