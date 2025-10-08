<?php

use MominAlZaraa\FilamentLocalization\Commands\LocalizeFilamentCommand;

test('service provider registers command', function () {
    expect($this->app->make(LocalizeFilamentCommand::class))
        ->toBeInstanceOf(LocalizeFilamentCommand::class);
});

test('config file is published', function () {
    $configPath = config_path('filament-localization.php');

    $this->artisan('vendor:publish', [
        '--tag' => 'filament-localization-config',
        '--force' => true,
    ])->assertExitCode(0);
});
