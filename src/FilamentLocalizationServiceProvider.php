<?php

namespace MominAlZaraa\FilamentLocalization;

use MominAlZaraa\FilamentLocalization\Commands\LocalizeFilamentCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentLocalizationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-localization')
            ->hasConfigFile()
            ->hasCommand(LocalizeFilamentCommand::class);
    }

    public function packageBooted(): void
    {
        //
    }
}
