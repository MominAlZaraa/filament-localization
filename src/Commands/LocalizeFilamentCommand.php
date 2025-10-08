<?php

namespace MominAlZaraa\FilamentLocalization\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use MominAlZaraa\FilamentLocalization\Services\GitService;
use MominAlZaraa\FilamentLocalization\Services\LocalizationService;
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

    public function __construct()
    {
        parent::__construct();

        $this->statisticsService = new StatisticsService;
        $this->localizationService = new LocalizationService($this->statisticsService);
        $this->gitService = new GitService;
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
            $panels = $allPanels->filter(fn ($panel) => in_array($panel->getId(), $requestedPanels));

            if ($panels->isEmpty()) {
                $this->error('❌ No matching panels found for: '.implode(', ', $requestedPanels));

                return [];
            }

            return $panels->all();
        }

        // Filter out excluded panels
        $excludedPanels = config('filament-localization.excluded_panels', []);
        $panels = $allPanels->filter(fn ($panel) => ! in_array($panel->getId(), $excludedPanels));

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
        $panelNames = collect($panels)->map(fn ($panel) => $panel->getId())->implode(', ');

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

        if (empty($resources)) {
            $this->warn("  ⚠️  No resources found in panel: {$panelId}");

            return;
        }

        $progressBar = $this->output->createProgressBar(count($resources));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

        foreach ($resources as $resource) {
            $resourceName = class_basename($resource);
            $progressBar->setMessage("Processing {$resourceName}...");

            $this->localizationService->processResource(
                $resource,
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

    protected function createGitCommit(): void
    {
        $this->newLine();
        $this->info('📝 Creating git commit...');

        try {
            $commitMessage = config('filament-localization.git.commit_message');
            $this->gitService->createCommit($commitMessage);

            $this->info('✅ Git commit created successfully!');
            $this->info('💡 You can revert this commit using: git reset --soft HEAD~1');
        } catch (\Exception $e) {
            $this->error('❌ Failed to create git commit: '.$e->getMessage());
        }
    }
}
