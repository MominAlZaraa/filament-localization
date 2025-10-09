<?php

namespace MominAlZaraa\FilamentLocalization\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use MominAlZaraa\FilamentLocalization\Services\GitService;
use MominAlZaraa\FilamentLocalization\Services\LocalizationService;
use MominAlZaraa\FilamentLocalization\Services\PintService;
use MominAlZaraa\FilamentLocalization\Services\StatisticsService;

class LocalizeFilamentCommand extends Command
{
    protected $signature = 'filament:localize
                            {--panel=* : Specific panel(s) to localize}
                            {--locale=* : Specific locale(s) to generate}
                            {--force : Force localization even if labels exist}
                            {--no-git : Skip git commit}
                            {--dry-run : Preview changes without applying them}';

    protected $description = 'Automatically scan and localize Filament resources with structured translation files';

    protected LocalizationService $localizationService;

    protected StatisticsService $statisticsService;

    protected GitService $gitService;

    protected PintService $pintService;

    public function __construct()
    {
        parent::__construct();

        $this->statisticsService = new StatisticsService;
        $this->localizationService = new LocalizationService($this->statisticsService);
        $this->gitService = new GitService;
        $this->pintService = new PintService;
    }

    public function handle(): int
    {
        $this->displayWelcomeBanner();

        // Check if git is initialized and working directory is clean
        if (! $this->option('no-git') && config('filament-localization.git.enabled')) {
            if (! $this->gitService->isGitRepository()) {
                $this->warn('⚠️  Not a git repository. Skipping git integration.');
            } elseif (! $this->gitService->isWorkingDirectoryClean()) {
                $this->error('❌ Working directory is not clean. Please commit or stash your changes first.');

                return self::FAILURE;
            }
        }

        // Get panels to process
        $panels = $this->getPanelsToProcess();

        if (empty($panels)) {
            $this->error('❌ No panels found to process.');

            return self::FAILURE;
        }

        // Get locales to generate
        $locales = $this->getLocalesToGenerate();

        // Confirm with user
        if (! $this->confirmProcessing($panels, $locales)) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        // Start processing
        $startTime = microtime(true);
        $this->statisticsService->reset();

        $this->newLine();
        $this->info('🚀 Starting localization process...');
        $this->newLine();

        // Process each panel
        foreach ($panels as $panel) {
            $this->processPanel($panel, $locales);
        }

        // Calculate statistics
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // Display results
        $this->displayResults($duration);

        // Create git commit if enabled
        if (! $this->option('no-git') && ! $this->option('dry-run') && config('filament-localization.git.enabled')) {
            $this->createGitCommit();
        }

        $this->newLine();
        $this->info('✅ Localization completed successfully!');

        return self::SUCCESS;
    }

    protected function displayWelcomeBanner(): void
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                                                                ║');
        $this->line('║          Filament Localization Package v1.0.0                  ║');
        $this->line('║                                                                ║');
        $this->line('║  Automatically scan and localize Filament resources            ║');
        $this->line('║                                                                ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->warn('⚠️  IMPORTANT NOTICE:');
        $this->info('This command will programmatically update your application resources.');
        $this->info('All processing is done locally with no external usage or handling.');
        $this->info('Your resources will be updated for localization using Laravel\'s locale handling.');
        $this->newLine();

        if (config('filament-localization.git.enabled') && ! $this->option('no-git')) {
            $this->info('💡 A git commit will be created after processing for easy reverting.');
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

        // Filter out excluded panels
        $excludedPanels = config('filament-localization.excluded_panels', []);
        $panels = $allPanels->filter(fn($panel) => ! in_array($panel->getId(), $excludedPanels));

        return $panels->all();
    }

    protected function getLocalesToGenerate(): array
    {
        if ($this->option('locale')) {
            return $this->option('locale');
        }

        return config('filament-localization.locales', ['en']);
    }

    protected function confirmProcessing(array $panels, array $locales): bool
    {
        $panelNames = collect($panels)->map(fn($panel) => $panel->getId())->implode(', ');

        $this->table(
            ['Setting', 'Value'],
            [
                ['Panels to process', $panelNames],
                ['Locales to generate', implode(', ', $locales)],
                ['Structure', config('filament-localization.structure')],
                ['Dry run', $this->option('dry-run') ? 'Yes' : 'No'],
                ['Git commit', (! $this->option('no-git') && config('filament-localization.git.enabled')) ? 'Yes' : 'No'],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('🔍 Running in DRY RUN mode - no changes will be made.');

            return true;
        }

        return $this->confirm('Do you want to proceed with localization?', true);
    }

    protected function processPanel($panel, array $locales): void
    {
        $panelId = $panel->getId();

        $this->info("📦 Processing panel: {$panelId}");

        // Get all resources for this panel
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

        // $this->info("Found {$resourcePagesCount} resource pages to process");

        if ($totalItems === 0) {
            $this->warn("  ⚠️  No resources, pages, or relation managers found in panel: {$panelId}");

            return;
        }

        $progressBar = $this->output->createProgressBar($totalItems);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

        // Process resources
        foreach ($resources as $resource) {
            $resourceName = class_basename($resource);
            $progressBar->setMessage("Processing resource {$resourceName}...");

            $this->localizationService->processResource(
                $resource,
                $panel,
                $locales,
                $this->option('dry-run')
            );

            // Also process resource pages
            $resourcePages = $this->getResourcePages($resource);

            foreach ($resourcePages as $page) {
                $pageName = class_basename($page);
                $progressBar->setMessage("Processing resource page {$pageName}...");

                $this->localizationService->processPage(
                    $page,
                    $panel,
                    $locales,
                    $this->option('dry-run')
                );

                $progressBar->advance();
            }

            $progressBar->advance();
        }

        // Process pages
        foreach ($pages as $page) {
            $pageName = class_basename($page);
            $progressBar->setMessage("Processing page {$pageName}...");

            $this->localizationService->processPage(
                $page,
                $panel,
                $locales,
                $this->option('dry-run')
            );

            $progressBar->advance();
        }

        // Process relation managers
        foreach ($relationManagers as $relationManager) {
            $relationManagerName = class_basename($relationManager);
            $progressBar->setMessage("Processing relation manager {$relationManagerName}...");

            $this->localizationService->processRelationManager(
                $relationManager,
                $panel,
                $locales,
                $this->option('dry-run')
            );

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Increment panel processed counter
        $this->statisticsService->incrementPanelsProcessed();
    }

    protected function displayResults(float $duration): void
    {
        $stats = $this->statisticsService->getStatistics();

        $this->newLine();
        $this->info('📊 Localization Statistics:');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Panels Processed', $stats['panels_processed']],
                ['Resources Processed', $stats['resources_processed']],
                ['Files Modified', $stats['files_modified']],
                ['Translation Files Created', $stats['translation_files_created']],
                ['Translation Keys Added', $stats['translation_keys_added']],
                ['Fields Localized', $stats['fields_localized']],
                ['Actions Localized', $stats['actions_localized']],
                ['Columns Localized', $stats['columns_localized']],
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

            if ($filePath && \File::exists($filePath)) {
                $content = \File::get($filePath);

                // Look for page references in the resource use statements
                // Extract use statements and find Pages
                preg_match_all('/use\s+([^;]+);/', $content, $useMatches);

                foreach ($useMatches[1] as $useStatement) {
                    if (str_contains($useStatement, 'Pages\\')) {
                        // Extract the page class name
                        // Extract page name using string functions instead of regex
                        $parts = explode('\\', $useStatement);
                        $pageName = end($parts);
                        $fullClassName = $useStatement; // Use the full use statement as class name

                        if ($this->isCustomPage($fullClassName)) {
                            $pages[] = $fullClassName;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if we can't analyze the resource
        }

        return $pages;
    }

    protected function resolvePageClass(string $pageClass, string $resourceClass, string $content): ?string
    {
        // If the class name doesn't have a namespace, we need to resolve it from the use statements
        if (! str_contains($pageClass, '\\')) {
            // Extract use statements from the content
            preg_match_all('/use\s+([^;]+);/', $content, $useMatches);

            foreach ($useMatches[1] as $useStatement) {
                if (str_ends_with($useStatement, '\\' . $pageClass) || $useStatement === $pageClass) {
                    return $useStatement;
                }

                // Handle aliased imports (use X as Y)
                if (preg_match('/(.+)\s+as\s+(.+)/', $useStatement, $aliasMatch)) {
                    if (trim($aliasMatch[2]) === $pageClass) {
                        return trim($aliasMatch[1]);
                    }
                }
            }

            // If still not resolved, assume it's in the same namespace as the resource
            if (! str_contains($pageClass, '\\')) {
                $resourceNamespace = (new \ReflectionClass($resourceClass))->getNamespaceName();
                return $resourceNamespace . '\\Pages\\' . $pageClass;
            }
        }

        return $pageClass;
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

            if ($filePath && \File::exists($filePath)) {
                $content = \File::get($filePath);

                // Look for relation manager references
                preg_match_all('/([A-Za-z]+RelationManager)::class/', $content, $matches);

                if (! empty($matches[1])) {
                    foreach ($matches[1] as $relationManager) {
                        // Try to resolve the full class name
                        $fullClassName = $this->resolveRelationManagerClass($relationManager, $resourceClass, $content);
                        if ($fullClassName) {
                            $relationManagers[] = $fullClassName;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if we can't analyze the resource
        }

        return $relationManagers;
    }

    protected function resolveRelationManagerClass(string $relationManagerClass, string $resourceClass, string $content): ?string
    {
        // If the class name doesn't have a namespace, we need to resolve it from the use statements
        if (! str_contains($relationManagerClass, '\\')) {
            // Extract use statements from the content
            preg_match_all('/use\s+([^;]+);/', $content, $useMatches);

            foreach ($useMatches[1] as $useStatement) {
                if (str_ends_with($useStatement, '\\' . $relationManagerClass) || $useStatement === $relationManagerClass) {
                    return $useStatement;
                }

                // Handle aliased imports (use X as Y)
                if (preg_match('/(.+)\s+as\s+(.+)/', $useStatement, $aliasMatch)) {
                    if (trim($aliasMatch[2]) === $relationManagerClass) {
                        return trim($aliasMatch[1]);
                    }
                }
            }

            // If still not resolved, assume it's in the same namespace as the resource
            if (! str_contains($relationManagerClass, '\\')) {
                $resourceNamespace = (new \ReflectionClass($resourceClass))->getNamespaceName();
                return $resourceNamespace . '\\' . $relationManagerClass;
            }
        }

        return $relationManagerClass;
    }

    protected function isCustomPage(string $pageClass): bool
    {
        try {
            $reflection = new \ReflectionClass($pageClass);
            $filePath = $reflection->getFileName();

            if (! $filePath || ! \File::exists($filePath)) {
                return false;
            }

            $content = \File::get($filePath);

            // Check if this page has custom content that needs localization
            $patterns = [
                // Infolist entries
                '/TextEntry::make\(/',
                '/IconEntry::make\(/',
                '/ImageEntry::make\(/',
                '/ColorEntry::make\(/',
                '/KeyValueEntry::make\(/',
                '/RepeatableEntry::make\(/',

                // Custom actions
                '/Action::make\(/',
                '/CreateAction::make\(/',
                '/EditAction::make\(/',
                '/DeleteAction::make\(/',
                '/ViewAction::make\(/',
                '/BulkAction::make\(/',

                // Custom sections
                '/Section::make\(/',
                '/Fieldset::make\(/',
                '/Grid::make\(/',
                '/Tabs::make\(/',
                '/Wizard::make\(/',

                // Custom labels and titles
                '/->label\([\'"][^\'"]+[\'"]\)/',
                '/->title\([\'"][^\'"]+[\'"]\)/',
                '/->heading\([\'"][^\'"]+[\'"]\)/',

                // Custom HTML content
                '/->html\(/',
                '/->state\(/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function createGitCommit(): void
    {
        $this->newLine();
        $this->info('📝 Creating git commit...');

        try {
            // Run Pint code formatting if enabled
            if (config('filament-localization.pint.enabled', true)) {
                $this->info('🎨 Running Laravel Pint for code formatting...');
                $pintResult = $this->pintService->formatCodeWithOutput();
                
                if ($pintResult['success']) {
                    $this->info('✅ Code formatted successfully!');
                    if (!empty($pintResult['output'])) {
                        $this->line($pintResult['output']);
                    }
                } else {
                    $this->warn('⚠️  Pint formatting failed: ' . $pintResult['error']);
                }
            }

            $commitMessage = config('filament-localization.git.commit_message');
            $this->gitService->createCommit($commitMessage);

            $this->info('✅ Git commit created successfully!');
            $this->info('💡 You can revert this commit using: git reset --soft HEAD~1');
        } catch (\Exception $e) {
            $this->error('❌ Failed to create git commit: ' . $e->getMessage());
        }
    }
}
