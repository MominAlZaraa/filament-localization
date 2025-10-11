<?php

namespace MominAlZaraa\FilamentLocalization\Tests;

use MominAlZaraa\FilamentLocalization\FilamentLocalizationServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FilamentLocalizationServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
            \Filament\FilamentServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Tables\TablesServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\Infolists\InfolistsServiceProvider::class,
            \Filament\Notifications\NotificationsServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Widgets\WidgetsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set DeepL API key in the correct config location
        $app['config']->set('filament-localization.deepl.api_key', 'test-key-for-tests');

        // Set up Filament configuration for testing
        $app['config']->set('filament', [
            'default_filesystem_disk' => 'public',
            'default_theme_mode' => 'system',
        ]);
    }
}
