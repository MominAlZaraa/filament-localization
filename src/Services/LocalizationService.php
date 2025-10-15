<?php

namespace MominAlZaraa\FilamentLocalization\Services;

use Illuminate\Support\Facades\File;
use MominAlZaraa\FilamentLocalization\Analyzers\PageAnalyzer;
use MominAlZaraa\FilamentLocalization\Analyzers\RelationManagerAnalyzer;
use MominAlZaraa\FilamentLocalization\Analyzers\ResourceAnalyzer;
use MominAlZaraa\FilamentLocalization\Analyzers\WidgetAnalyzer;
use MominAlZaraa\FilamentLocalization\Generators\PageModifier;
use MominAlZaraa\FilamentLocalization\Generators\RelationManagerModifier;
use MominAlZaraa\FilamentLocalization\Generators\ResourceModifier;
use MominAlZaraa\FilamentLocalization\Generators\TranslationFileGenerator;
use MominAlZaraa\FilamentLocalization\Generators\WidgetModifier;

class LocalizationService
{
    protected ResourceAnalyzer $analyzer;

    protected PageAnalyzer $pageAnalyzer;

    protected RelationManagerAnalyzer $relationManagerAnalyzer;

    protected WidgetAnalyzer $widgetAnalyzer;

    protected TranslationFileGenerator $translationGenerator;

    protected ResourceModifier $resourceModifier;

    protected PageModifier $pageModifier;

    protected RelationManagerModifier $relationManagerModifier;

    protected WidgetModifier $widgetModifier;

    protected StatisticsService $statistics;

    public function __construct(?StatisticsService $statistics = null)
    {
        $this->analyzer = new ResourceAnalyzer;
        $this->pageAnalyzer = new PageAnalyzer;
        $this->relationManagerAnalyzer = new RelationManagerAnalyzer;
        $this->widgetAnalyzer = new WidgetAnalyzer;
        $this->statistics = $statistics ?? app(StatisticsService::class);
        $this->translationGenerator = new TranslationFileGenerator($this->statistics);
        $this->resourceModifier = new ResourceModifier($this->statistics);
        $this->pageModifier = new PageModifier($this->statistics);
        $this->relationManagerModifier = new RelationManagerModifier($this->statistics);
        $this->widgetModifier = new WidgetModifier;
    }

    public function processResource(string $resourceClass, $panel, array $locales, bool $dryRun = false, bool $force = false): void
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
                $this->resourceModifier->modify($resourceClass, $analysis, $panel, $force);

                // Handle static properties for getModelLabel method
                $this->translationGenerator->handleStaticPropertiesForGetModelLabel($analysis, $dryRun);
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

    public function processPage(string $pageClass, $panel, array $locales, bool $dryRun = false): void
    {
        try {
            // Analyze the page to find all localizable content
            $analysis = $this->pageAnalyzer->analyze($pageClass, $panel);

            if (! $analysis['has_custom_content']) {
                return; // No custom content to localize
            }

            if (empty($analysis['infolist_entries']) && empty($analysis['actions']) && empty($analysis['sections']) && empty($analysis['custom_content']) && empty($analysis['titles']) && empty($analysis['labels']) && empty($analysis['navigation'])) {
                return; // Nothing to localize
            }

            // Generate translation files for each locale
            foreach ($locales as $locale) {
                $this->translationGenerator->generatePageTranslations(
                    $analysis,
                    $panel->getId(),
                    $locale,
                    $dryRun
                );
            }

            // Modify the page file to use translation keys
            if (! $dryRun) {
                $this->pageModifier->modify($pageClass, $analysis, $panel);
            }

            // Update statistics
            $this->statistics->incrementResourcesProcessed();
            $this->statistics->incrementFieldsLocalized(count($analysis['infolist_entries']));
            $this->statistics->incrementActionsLocalized(count($analysis['actions']));
        } catch (\Exception $e) {
            $this->statistics->addError("Failed to process page {$pageClass}: {$e->getMessage()}");
        }
    }

    public function processRelationManager(string $relationManagerClass, $panel, array $locales, bool $dryRun = false): void
    {
        try {
            // Analyze the relation manager to find all localizable content
            $analysis = $this->relationManagerAnalyzer->analyze($relationManagerClass, $panel);

            if (! $analysis['has_custom_content']) {
                return; // No custom content to localize
            }

            if (empty($analysis['fields']) && empty($analysis['columns']) && empty($analysis['actions']) && empty($analysis['sections']) && empty($analysis['filters'])) {
                return; // Nothing to localize
            }

            // Generate translation files for each locale
            foreach ($locales as $locale) {
                $this->translationGenerator->generateRelationManagerTranslations(
                    $analysis,
                    $panel->getId(),
                    $locale,
                    $dryRun
                );
            }

            // Modify the relation manager file to use translation keys
            if (! $dryRun) {
                $this->relationManagerModifier->modify($relationManagerClass, $analysis, $panel);
            }

            // Update statistics
            $this->statistics->incrementResourcesProcessed();
            $this->statistics->incrementFieldsLocalized(count($analysis['fields']));
            $this->statistics->incrementActionsLocalized(count($analysis['actions']));
            $this->statistics->incrementColumnsLocalized(count($analysis['columns']));
        } catch (\Exception $e) {
            $this->statistics->addError("Failed to process relation manager {$relationManagerClass}: {$e->getMessage()}");
        }
    }

    public function processWidget(string $widgetClass, $panel, array $locales, bool $dryRun = false, bool $force = false): void
    {
        try {
            // Analyze the widget to find all localizable content
            $analysis = $this->widgetAnalyzer->analyze($widgetClass, $panel);

            if (empty($analysis['stats']) && ! $analysis['custom_content']) {
                return; // Nothing to localize
            }

            // Generate translation files for each locale
            foreach ($locales as $locale) {
                $this->translationGenerator->generateWidgetTranslations($analysis, $panel->getId(), $locale, $dryRun);
            }

            // Modify the widget file to use translation keys
            if (! $dryRun) {
                $reflection = new \ReflectionClass($widgetClass);
                $filePath = $reflection->getFileName();

                if ($filePath && File::exists($filePath)) {
                    $content = File::get($filePath);
                    $modifiedContent = $this->widgetModifier->modify($content, $analysis, $panel);
                    File::put($filePath, $modifiedContent);
                }
            }

            // Update statistics
            $this->statistics->incrementResourcesProcessed();
            $this->statistics->incrementFieldsLocalized(count($analysis['stats']));
        } catch (\Exception $e) {
            $this->statistics->addError("Failed to process widget {$widgetClass}: {$e->getMessage()}");
        }
    }
}
