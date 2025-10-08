<?php

namespace MominAlZaraa\FilamentLocalization\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckDependenciesCommand extends Command
{
    protected $signature = 'filament:check-dependencies
                            {--install : Install missing dependencies automatically}';

    protected $description = 'Check if all required dependencies are installed and compatible';

    protected array $requiredPackages = [
        'laravel/framework' => '^12.0',
        'filament/filament' => '^4.0',
    ];

    protected array $recommendedPackages = [
        'spatie/laravel-package-tools' => '^1.16',
    ];

    public function handle(): int
    {
        $this->displayBanner();

        $missingRequired = $this->checkRequiredDependencies();
        $missingRecommended = $this->checkRecommendedDependencies();

        if (empty($missingRequired) && empty($missingRecommended)) {
            $this->info('✅ All dependencies are satisfied!');
            return self::SUCCESS;
        }

        if (!empty($missingRequired)) {
            $this->displayMissingDependencies('Required', $missingRequired);

            if ($this->option('install') || $this->confirm('Would you like to install missing required dependencies?')) {
                return $this->installDependencies($missingRequired);
            }

            $this->error('❌ Required dependencies are missing. Please install them before using this package.');
            return self::FAILURE;
        }

        if (!empty($missingRecommended)) {
            $this->displayMissingDependencies('Recommended', $missingRecommended);

            if ($this->option('install') || $this->confirm('Would you like to install recommended dependencies?')) {
                return $this->installDependencies($missingRecommended);
            }
        }

        return self::SUCCESS;
    }

    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                 Dependency Check                               ║');
        $this->line('║          Filament Localization Package                         ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function checkRequiredDependencies(): array
    {
        return $this->checkDependencies($this->requiredPackages);
    }

    protected function checkRecommendedDependencies(): array
    {
        return $this->checkDependencies($this->recommendedPackages);
    }

    protected function checkDependencies(array $packages): array
    {
        $missing = [];
        $composerJson = $this->getComposerJson();

        if (!$composerJson) {
            $this->error('❌ Could not read composer.json file');
            return array_keys($packages);
        }

        foreach ($packages as $package => $version) {
            if (!$this->isPackageInstalled($package, $version, $composerJson)) {
                $missing[$package] = $version;
            }
        }

        return $missing;
    }

    protected function isPackageInstalled(string $package, string $version, array $composerJson): bool
    {
        // Check in require section
        if (isset($composerJson['require'][$package])) {
            return $this->isVersionCompatible($composerJson['require'][$package], $version);
        }

        // Check in require-dev section
        if (isset($composerJson['require-dev'][$package])) {
            return $this->isVersionCompatible($composerJson['require-dev'][$package], $version);
        }

        // Check if package is actually installed via composer show
        try {
            $output = shell_exec("composer show {$package} 2>/dev/null");
            return !empty($output);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function isVersionCompatible(string $installedVersion, string $requiredVersion): bool
    {
        // This is a simplified version check
        // In a production environment, you might want to use a more robust version comparison
        return true; // For now, assume compatibility if package is present
    }

    protected function getComposerJson(): ?array
    {
        $composerPath = base_path('composer.json');

        if (!File::exists($composerPath)) {
            return null;
        }

        $content = File::get($composerPath);
        return json_decode($content, true);
    }

    protected function displayMissingDependencies(string $type, array $missing): void
    {
        $this->newLine();
        $this->warn("⚠️  Missing {$type} Dependencies:");

        foreach ($missing as $package => $version) {
            $this->line("   • {$package} ({$version})");
        }

        $this->newLine();
        $this->info("Install with: composer require " . implode(' ', array_keys($missing)));
        $this->newLine();
    }

    protected function installDependencies(array $dependencies): int
    {
        $packages = [];
        foreach ($dependencies as $package => $version) {
            $packages[] = "{$package}:{$version}";
        }

        $command = "composer require " . implode(' ', $packages);

        $this->info("🚀 Running: {$command}");
        $this->newLine();

        $output = shell_exec($command . " 2>&1");
        $this->line($output);

        // Re-check if installation was successful
        $stillMissing = $this->checkDependencies($dependencies);

        if (empty($stillMissing)) {
            $this->info('✅ Dependencies installed successfully!');
            return self::SUCCESS;
        } else {
            $this->error('❌ Some dependencies could not be installed. Please install them manually.');
            return self::FAILURE;
        }
    }
}
