<?php

namespace MominAlZaraa\FilamentLocalization\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use MominAlZaraa\FilamentLocalization\Services\DeepLTranslationService;
use MominAlZaraa\FilamentLocalization\Services\StatisticsService;

class TranslateWithDeepLCommand extends Command
{
    protected $signature = 'filament:translate-with-deepl
                            {--source-lang=en : Source language code}
                            {--target-lang= : Target language code}
                            {--panel=* : Specific panel(s) to translate}
                            {--force : Force translation even if target files exist}
                            {--dry-run : Preview changes without applying them}';

    protected $description = 'Translate Filament resources using DeepL API from source language to target language';

    protected DeepLTranslationService $deepLService;

    protected StatisticsService $statisticsService;

    public function __construct()
    {
        parent::__construct();
        $this->deepLService = new DeepLTranslationService;
        $this->statisticsService = new StatisticsService;
    }

    public function handle(): int
    {
        $this->displayWelcomeBanner();

        // Check if DeepL is configured
        try {
            if (! $this->deepLService->isConfigured()) {
                $this->error('❌ DeepL API key not configured. Please set DEEPL_API_KEY in your .env file.');

                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ DeepL configuration error: ' . $e->getMessage());

            return self::FAILURE;
        }

        $sourceLang = $this->option('source-lang');
        $targetLang = $this->option('target-lang');

        if (! $targetLang) {
            $this->error('❌ Target language is required. Use --target-lang option.');

            return self::FAILURE;
        }

        // Validate languages
        if (! $this->deepLService->isLanguageSupported($sourceLang)) {
            $this->error("❌ Unsupported source language: {$sourceLang}");
            $this->info('Supported languages: ' . implode(', ', array_keys($this->deepLService->getSupportedLanguages())));

            return self::FAILURE;
        }

        if (! $this->deepLService->isLanguageSupported($targetLang)) {
            $this->error("❌ Unsupported target language: {$targetLang}");
            $this->info('Supported languages: ' . implode(', ', array_keys($this->deepLService->getSupportedLanguages())));

            return self::FAILURE;
        }

        // Get panels to process
        $panels = $this->getPanelsToProcess();

        if (empty($panels)) {
            $this->error('❌ No panels found to process.');

            return self::FAILURE;
        }

        // Check usage
        $this->checkDeepLUsage();

        // Confirm with user
        if (! $this->confirmTranslation($sourceLang, $targetLang, $panels)) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        // Start processing
        $startTime = microtime(true);
        $this->statisticsService->reset();

        $this->newLine();
        $this->info('🚀 Starting DeepL translation process...');
        $this->newLine();

        // Process each panel
        foreach ($panels as $panel) {
            $this->processPanel($panel, $sourceLang, $targetLang);
        }

        // Calculate statistics
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // Display results
        $this->displayResults($duration);

        $this->newLine();
        $this->info('✅ DeepL translation completed successfully!');

        return self::SUCCESS;
    }

    protected function displayWelcomeBanner(): void
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                                                                ║');
        $this->line('║          Filament DeepL Translation 1.0.0                     ║');
        $this->line('║                                                                ║');
        $this->line('║  Translate Filament resources using DeepL API                 ║');
        $this->line('║                                                                ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->warn('⚠️  IMPORTANT NOTICE:');
        $this->info('This command will translate your Filament resources using DeepL API.');
        $this->info('Make sure you have sufficient DeepL API credits.');
        $this->info('By default, existing translations will be preserved and only missing ones will be translated.');
        $this->info('Use --force to overwrite all existing translations with new ones from the source language.');
        $this->newLine();
    }

    protected function checkDeepLUsage(): void
    {
        $this->info('🔍 Checking DeepL API usage...');

        $usage = $this->deepLService->getUsage();
        if ($usage) {
            $used = $usage['character_count'] ?? 0;
            $limit = $usage['character_limit'] ?? 0;
            $remaining = $limit - $used;

            $this->info("📊 DeepL Usage: {$used}/{$limit} characters used");
            $this->info("📊 Remaining: {$remaining} characters");

            if ($remaining < 1000) {
                $this->warn('⚠️  Low DeepL API credits remaining. Consider upgrading your plan.');
            }
        } else {
            $this->warn('⚠️  Could not check DeepL usage. Proceeding anyway...');
        }

        $this->newLine();
    }

    protected function getPanelsToProcess(): array
    {
        $allPanels = collect(Filament::getPanels());

        // If specific panels are requested
        if ($this->option('panel')) {
            $requestedPanels = $this->option('panel');
            $panels = $allPanels->filter(fn($panel) => in_array($panel->getId(), $requestedPanels));

            if ($panels->isEmpty()) {
                $this->error('❌ No matching panels found for: ' . implode(', ', $requestedPanels));

                return [];
            }

            return $panels->all();
        }

        return $allPanels->all();
    }

    protected function confirmTranslation(string $sourceLang, string $targetLang, array $panels): bool
    {
        $panelNames = collect($panels)->map(fn($panel) => $panel->getId())->implode(', ');

        $this->table(
            ['Setting', 'Value'],
            [
                ['Source Language', $sourceLang],
                ['Target Language', $targetLang],
                ['Panels to process', $panelNames],
                ['Force mode', $this->option('force') ? 'Yes' : 'No'],
                ['Dry run', $this->option('dry-run') ? 'Yes' : 'No'],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('🔍 Running in DRY RUN mode - no changes will be made.');

            return true;
        }

        return $this->confirm('Do you want to proceed with DeepL translation?', true);
    }

    protected function processPanel($panel, string $sourceLang, string $targetLang): void
    {
        $panelId = $panel->getId();
        $this->info("📦 Processing panel: {$panelId}");

        // Get all resources, pages, and relation managers for this panel
        $resources = $panel->getResources();
        $pages = $this->getPanelPages($panel);
        $relationManagers = $this->getPanelRelationManagers($panel);

        // Calculate total items including resource pages
        $totalItems = count($resources) + count($pages) + count($relationManagers);
        $resourcePagesCount = 0;
        foreach ($resources as $resource) {
            $resourcePages = $this->getResourcePages($resource);
            $resourcePagesCount += count($resourcePages);
            $totalItems += count($resourcePages);
        }

        if ($totalItems === 0) {
            $this->warn("  ⚠️  No resources, pages, or relation managers found in panel: {$panelId}");

            return;
        }

        $progressBar = $this->output->createProgressBar($totalItems);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

        // Process resources
        foreach ($resources as $resource) {
            $resourceName = class_basename($resource);
            $progressBar->setMessage("Translating resource {$resourceName}...");

            $this->translateResource($resource, $panel, $sourceLang, $targetLang);

            // Also process resource pages
            $resourcePages = $this->getResourcePages($resource);

            foreach ($resourcePages as $page) {
                $pageName = class_basename($page);
                $progressBar->setMessage("Translating resource page {$pageName}...");

                $this->translatePage($page, $panel, $sourceLang, $targetLang);
                $progressBar->advance();
            }

            $progressBar->advance();
        }

        // Process standalone pages
        foreach ($pages as $page) {
            $pageName = class_basename($page);
            $progressBar->setMessage("Translating page {$pageName}...");

            $this->translatePage($page, $panel, $sourceLang, $targetLang);
            $progressBar->advance();
        }

        // Process relation managers
        foreach ($relationManagers as $relationManager) {
            $relationManagerName = class_basename($relationManager);
            $progressBar->setMessage("Translating relation manager {$relationManagerName}...");

            $this->translateRelationManager($relationManager, $panel, $sourceLang, $targetLang);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    protected function translateResource(string $resourceClass, $panel, string $sourceLang, string $targetLang): void
    {
        $structure = config('filament-localization.structure', 'panel-based');
        $prefix = config('filament-localization.translation_key_prefix', 'filament');
        $resourceName = class_basename($resourceClass);

        // Build source and target file paths
        $sourcePath = $this->getTranslationPath($resourceName, $panel->getId(), $sourceLang, $structure, $prefix);
        $targetPath = $this->getTranslationPath($resourceName, $panel->getId(), $targetLang, $structure, $prefix);

        // Check if source file exists
        if (! File::exists($sourcePath)) {
            $this->warn("  ⚠️  Source file not found: {$sourcePath}");

            return;
        }

        // Note: We no longer skip existing files - let the enhanced logic decide what to translate

        // Load source translations
        $sourceTranslations = include $sourcePath;
        if (! is_array($sourceTranslations)) {
            $this->warn("  ⚠️  Invalid source file format: {$sourcePath}");

            return;
        }

        // Load existing target translations if they exist
        $existingTargetTranslations = [];
        if (File::exists($targetPath)) {
            $existingTargetTranslations = include $targetPath;
            if (! is_array($existingTargetTranslations)) {
                $existingTargetTranslations = [];
            }
        }

        // Determine what to translate based on force mode
        if ($this->option('force')) {
            // Force mode: translate ALL source translations, overwriting existing ones
            $translationsToTranslate = $sourceTranslations;
        } else {
            // Normal mode: translate missing keys OR keys with identical content to source
            $translationsToTranslate = [];
            $skipTerms = config('filament-localization.skip_identical_terms', []);

            foreach ($sourceTranslations as $key => $value) {
                $needsTranslation = false;

                if (! array_key_exists($key, $existingTargetTranslations)) {
                    // Key doesn't exist - needs translation
                    $needsTranslation = true;
                } elseif ($existingTargetTranslations[$key] === $value) {
                    // Key exists but has same content as source - check if it should be skipped
                    if (! $this->shouldSkipTerm($value, $skipTerms)) {
                        $needsTranslation = true;
                    }
                }

                if ($needsTranslation) {
                    $translationsToTranslate[$key] = $value;
                }
            }
        }

        if (empty($translationsToTranslate)) {
            $this->info("  ✅ All translations already exist for {$resourceName}");

            return;
        }

        if ($this->option('dry-run')) {
            $this->info('  🔍 Would translate ' . count($translationsToTranslate) . " keys for {$resourceName}");

            return;
        }

        // Translate using DeepL
        $this->info('  🌐 Translating ' . count($translationsToTranslate) . " keys for {$resourceName}...");

        try {
            $translatedTexts = $this->deepLService->translateArray($translationsToTranslate, $sourceLang, $targetLang);

            if (empty($translatedTexts)) {
                $this->warn("  ⚠️  No translations returned for {$resourceName}");

                return;
            }

            // Merge with existing translations
            if ($this->option('force')) {
                // Force mode: use only the translated texts (overwrite everything)
                $finalTranslations = $translatedTexts;
            } else {
                // Normal mode: merge with existing translations
                $finalTranslations = array_merge($existingTargetTranslations, $translatedTexts);
            }
            ksort($finalTranslations);
        } catch (\Exception $e) {
            $this->error("  ❌ Translation failed for {$resourceName}: " . $e->getMessage());

            return;
        }

        // Ensure target directory exists
        $targetDir = dirname($targetPath);
        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        // Generate PHP array content
        $content = $this->generatePhpArrayContent($finalTranslations);

        // Write to file
        File::put($targetPath, $content);

        $this->info("  ✅ Translated {$resourceName} successfully");
        $this->statisticsService->incrementFilesModified();
        $this->statisticsService->incrementTranslationKeysAdded(count($translatedTexts));
    }

    protected function getTranslationPath(string $resourceName, string $panelId, string $locale, string $structure, string $prefix): string
    {
        $basePath = lang_path($locale);

        return match ($structure) {
            'flat' => "{$basePath}/{$prefix}.php",
            'nested' => "{$basePath}/{$prefix}/" . \Illuminate\Support\Str::snake($resourceName) . '.php',
            'panel-based' => "{$basePath}/{$prefix}/{$panelId}/" . \Illuminate\Support\Str::snake($resourceName) . '.php',
            default => "{$basePath}/{$prefix}/{$panelId}/" . \Illuminate\Support\Str::snake($resourceName) . '.php',
        };
    }

    protected function generatePhpArrayContent(array $translations): string
    {
        $content = "<?php\n\nreturn [\n\n";

        foreach ($translations as $key => $value) {
            // Escape single quotes in values
            $escapedValue = str_replace("'", "\\'", $value);
            $content .= "    '{$key}' => '{$escapedValue}',\n";
        }

        $content .= "\n];\n";

        return $content;
    }

    protected function displayResults(float $duration): void
    {
        $stats = $this->statisticsService->getStatistics();

        $this->newLine();
        $this->info('📊 DeepL Translation Statistics:');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Files Modified', $stats['files_modified']],
                ['Translation Keys Added', $stats['translation_keys_added']],
                ['Total Processing Time', "{$duration}s"],
            ]
        );

        if (! empty($stats['errors'])) {
            $this->newLine();
            $this->error('⚠️  Errors encountered:');
            foreach ($stats['errors'] as $error) {
                $this->line("  • {$error}");
            }
        }
    }

    protected function translatePage(string $pageClass, $panel, string $sourceLang, string $targetLang): void
    {
        $structure = config('filament-localization.structure', 'panel-based');
        $prefix = config('filament-localization.translation_key_prefix', 'filament');
        $pageName = class_basename($pageClass);

        // Build source and target file paths
        $sourcePath = $this->getTranslationPath($pageName, $panel->getId(), $sourceLang, $structure, $prefix);
        $targetPath = $this->getTranslationPath($pageName, $panel->getId(), $targetLang, $structure, $prefix);

        // Check if source file exists
        if (! File::exists($sourcePath)) {
            $this->warn("  ⚠️  Source file not found: {$sourcePath}");

            return;
        }

        // Note: We no longer skip existing files - let the enhanced logic decide what to translate

        // Load source translations
        $sourceTranslations = include $sourcePath;
        if (! is_array($sourceTranslations)) {
            $this->warn("  ⚠️  Invalid source file format: {$sourcePath}");

            return;
        }

        // Load existing target translations if they exist
        $existingTargetTranslations = [];
        if (File::exists($targetPath)) {
            $existingTargetTranslations = include $targetPath;
            if (! is_array($existingTargetTranslations)) {
                $existingTargetTranslations = [];
            }
        }

        // Determine what to translate based on force mode
        if ($this->option('force')) {
            // Force mode: translate ALL source translations, overwriting existing ones
            $translationsToTranslate = $sourceTranslations;
        } else {
            // Normal mode: translate missing keys OR keys with identical content to source
            $translationsToTranslate = [];
            $skipTerms = config('filament-localization.skip_identical_terms', []);

            foreach ($sourceTranslations as $key => $value) {
                $needsTranslation = false;

                if (! array_key_exists($key, $existingTargetTranslations)) {
                    // Key doesn't exist - needs translation
                    $needsTranslation = true;
                } elseif ($existingTargetTranslations[$key] === $value) {
                    // Key exists but has same content as source - check if it should be skipped
                    if (! $this->shouldSkipTerm($value, $skipTerms)) {
                        $needsTranslation = true;
                    }
                }

                if ($needsTranslation) {
                    $translationsToTranslate[$key] = $value;
                }
            }
        }

        if (empty($translationsToTranslate)) {
            $this->info("  ✅ No new translations needed for {$pageName}");

            return;
        }

        // Translate the missing keys
        $translatedKeys = $this->deepLService->translateArray($translationsToTranslate, $sourceLang, $targetLang);

        if (empty($translatedKeys)) {
            $this->warn("  ⚠️  Failed to translate {$pageName}");

            return;
        }

        // Merge with existing translations
        if ($this->option('force')) {
            // Force mode: use only the translated texts (overwrite everything)
            $finalTranslations = $translatedKeys;
        } else {
            // Normal mode: merge with existing translations
            $finalTranslations = array_merge($existingTargetTranslations, $translatedKeys);
        }

        // Ensure target directory exists
        $targetDir = dirname($targetPath);
        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        // Write the translated file
        $content = $this->generatePhpArrayContent($finalTranslations);
        File::put($targetPath, $content);

        $this->info("  ✅ Translated {$pageName} successfully");
        $this->statisticsService->incrementFilesModified();
        $this->statisticsService->incrementTranslationKeysAdded(count($translatedKeys));
    }

    protected function translateRelationManager(string $relationManagerClass, $panel, string $sourceLang, string $targetLang): void
    {
        $structure = config('filament-localization.structure', 'panel-based');
        $prefix = config('filament-localization.translation_key_prefix', 'filament');
        $relationManagerName = class_basename($relationManagerClass);

        // Build source and target file paths
        $sourcePath = $this->getTranslationPath($relationManagerName, $panel->getId(), $sourceLang, $structure, $prefix);
        $targetPath = $this->getTranslationPath($relationManagerName, $panel->getId(), $targetLang, $structure, $prefix);

        // Check if source file exists
        if (! File::exists($sourcePath)) {
            $this->warn("  ⚠️  Source file not found: {$sourcePath}");

            return;
        }

        // Note: We no longer skip existing files - let the enhanced logic decide what to translate

        // Load source translations
        $sourceTranslations = include $sourcePath;
        if (! is_array($sourceTranslations)) {
            $this->warn("  ⚠️  Invalid source file format: {$sourcePath}");

            return;
        }

        // Load existing target translations if they exist
        $existingTargetTranslations = [];
        if (File::exists($targetPath)) {
            $existingTargetTranslations = include $targetPath;
            if (! is_array($existingTargetTranslations)) {
                $existingTargetTranslations = [];
            }
        }

        // Determine what to translate based on force mode
        if ($this->option('force')) {
            // Force mode: translate ALL source translations, overwriting existing ones
            $translationsToTranslate = $sourceTranslations;
        } else {
            // Normal mode: translate missing keys OR keys with identical content to source
            $translationsToTranslate = [];
            $skipTerms = config('filament-localization.skip_identical_terms', []);

            foreach ($sourceTranslations as $key => $value) {
                $needsTranslation = false;

                if (! array_key_exists($key, $existingTargetTranslations)) {
                    // Key doesn't exist - needs translation
                    $needsTranslation = true;
                } elseif ($existingTargetTranslations[$key] === $value) {
                    // Key exists but has same content as source - check if it should be skipped
                    if (! $this->shouldSkipTerm($value, $skipTerms)) {
                        $needsTranslation = true;
                    }
                }

                if ($needsTranslation) {
                    $translationsToTranslate[$key] = $value;
                }
            }
        }

        if (empty($translationsToTranslate)) {
            $this->info("  ✅ No new translations needed for {$relationManagerName}");

            return;
        }

        // Translate the missing keys
        $translatedKeys = $this->deepLService->translateArray($translationsToTranslate, $sourceLang, $targetLang);

        if (empty($translatedKeys)) {
            $this->warn("  ⚠️  Failed to translate {$relationManagerName}");

            return;
        }

        // Merge with existing translations
        if ($this->option('force')) {
            // Force mode: use only the translated texts (overwrite everything)
            $finalTranslations = $translatedKeys;
        } else {
            // Normal mode: merge with existing translations
            $finalTranslations = array_merge($existingTargetTranslations, $translatedKeys);
        }

        // Ensure target directory exists
        $targetDir = dirname($targetPath);
        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        // Write the translated file
        $content = $this->generatePhpArrayContent($finalTranslations);
        File::put($targetPath, $content);

        $this->info("  ✅ Translated {$relationManagerName} successfully");
        $this->statisticsService->incrementFilesModified();
        $this->statisticsService->incrementTranslationKeysAdded(count($translatedKeys));
    }

    protected function getPanelPages($panel): array
    {
        $pages = [];

        try {
            // Get pages from the panel
            $panelPages = $panel->getPages();

            foreach ($panelPages as $page) {
                // Only include pages that are not default Filament pages
                if ($this->isCustomPage($page)) {
                    $pages[] = $page;
                }
            }
        } catch (\Exception $e) {
            // Silently fail if we can't get pages
        }

        return $pages;
    }

    protected function getResourcePages(string $resourceClass): array
    {
        $pages = [];

        try {
            $reflection = new \ReflectionClass($resourceClass);
            $filePath = $reflection->getFileName();

            if ($filePath && File::exists($filePath)) {
                $content = File::get($filePath);

                // Look for page classes in the same directory
                $resourceDir = dirname($filePath);
                $pagesDir = $resourceDir . '/Pages';

                if (File::exists($pagesDir)) {
                    $pageFiles = File::allFiles($pagesDir);

                    foreach ($pageFiles as $pageFile) {
                        $pageContent = File::get($pageFile->getPathname());
                        $pageClassName = $pageFile->getBasename('.php');

                        // Check if it's a valid page class
                        if (
                            strpos($pageContent, 'extends') !== false &&
                            (strpos($pageContent, 'Page') !== false || strpos($pageContent, 'ViewRecord') !== false ||
                                strpos($pageContent, 'CreateRecord') !== false || strpos($pageContent, 'EditRecord') !== false ||
                                strpos($pageContent, 'ListRecords') !== false)
                        ) {

                            $namespace = $this->extractNamespace($pageContent);
                            $fullClassName = $namespace . '\\' . $pageClassName;

                            if (class_exists($fullClassName)) {
                                $pages[] = $fullClassName;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if we can't get resource pages
        }

        return $pages;
    }

    protected function getPanelRelationManagers($panel): array
    {
        $relationManagers = [];

        try {
            // Get all resources for this panel
            $resources = $panel->getResources();

            foreach ($resources as $resource) {
                // Try to get relation managers from each resource
                $resourceRelationManagers = $this->getResourceRelationManagers($resource);
                $relationManagers = array_merge($relationManagers, $resourceRelationManagers);
            }
        } catch (\Exception $e) {
            // Silently fail if we can't get relation managers
        }

        return array_unique($relationManagers);
    }

    protected function getResourceRelationManagers(string $resourceClass): array
    {
        $relationManagers = [];

        try {
            $reflection = new \ReflectionClass($resourceClass);
            $filePath = $reflection->getFileName();

            if ($filePath && File::exists($filePath)) {
                $content = File::get($filePath);

                // Look for relation manager classes in the same directory
                $resourceDir = dirname($filePath);
                $relationManagersDir = $resourceDir . '/RelationManagers';

                if (File::exists($relationManagersDir)) {
                    $relationManagerFiles = File::allFiles($relationManagersDir);

                    foreach ($relationManagerFiles as $relationManagerFile) {
                        $relationManagerContent = File::get($relationManagerFile->getPathname());
                        $relationManagerClassName = $relationManagerFile->getBasename('.php');

                        // Check if it's a valid relation manager class
                        if (
                            strpos($relationManagerContent, 'extends') !== false &&
                            strpos($relationManagerContent, 'RelationManager') !== false
                        ) {

                            $namespace = $this->extractNamespace($relationManagerContent);
                            $fullClassName = $namespace . '\\' . $relationManagerClassName;

                            if (class_exists($fullClassName)) {
                                $relationManagers[] = $fullClassName;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if we can't get relation managers
        }

        return $relationManagers;
    }

    protected function isCustomPage(string $pageClass): bool
    {
        // Exclude default Filament pages
        $defaultPages = [
            'Filament\\Pages\\Dashboard',
            'Filament\\Pages\\Profile',
        ];

        return ! in_array($pageClass, $defaultPages);
    }

    protected function extractNamespace(string $content): string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    protected function shouldSkipTerm(string $value, array $skipTerms): bool
    {
        // Check if the value exactly matches any skip term
        if (in_array($value, $skipTerms)) {
            return true;
        }

        // Check if the value contains only skip terms (for compound terms)
        $words = preg_split('/\s+/', $value);
        $allWordsAreSkipTerms = true;

        foreach ($words as $word) {
            // Remove punctuation for comparison
            $cleanWord = preg_replace('/[^\w]/', '', $word);
            if (! empty($cleanWord) && ! in_array($cleanWord, $skipTerms)) {
                $allWordsAreSkipTerms = false;
                break;
            }
        }

        return $allWordsAreSkipTerms;
    }
}
