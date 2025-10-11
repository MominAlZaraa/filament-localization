<?php

namespace MominAlZaraa\FilamentLocalization;

use MominAlZaraa\FilamentLocalization\Commands\LocalizeFilamentCommand;
use MominAlZaraa\FilamentLocalization\Commands\TranslateWithDeepLCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentLocalizationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-localization')
            ->hasConfigFile()
            ->hasCommand(LocalizeFilamentCommand::class)
            ->hasCommand(TranslateWithDeepLCommand::class);
    }

    public function packageBooted(): void
    {
        //
    }
}
