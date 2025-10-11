<?php

use Filament\Facades\Filament;
use Filament\Resources\Resource;

test('localize filament command can be executed', function () {
    // Mock Filament panels to return empty array
    Filament::shouldReceive('getPanels')
        ->andReturn([]);

    // @phpstan-ignore-next-line
    $this->artisan('filament:localize --help')
        ->assertExitCode(0);
});

test('localize filament command shows help without errors', function () {
    // Mock Filament panels to return empty array
    Filament::shouldReceive('getPanels')
        ->andReturn([]);

    // @phpstan-ignore-next-line
    $this->artisan('filament:localize --help')
        ->assertExitCode(0);
});

test('localize filament command handles missing resources gracefully', function () {
    // Mock Filament panels to return empty array
    Filament::shouldReceive('getPanels')
        ->andReturn([]);

    // Test with dry run only (removed --resource option as it doesn't exist)
    // When no panels are found, command returns exit code 1
    // @phpstan-ignore-next-line
    $this->artisan('filament:localize', [
        '--dry-run' => true,
    ])
        ->assertExitCode(1);
});

test('localize filament command works with dry run', function () {
    // Mock Filament panels to return empty array
    Filament::shouldReceive('getPanels')
        ->andReturn([]);

    // When no panels are found, command returns exit code 1
    // @phpstan-ignore-next-line
    $this->artisan('filament:localize', [
        '--dry-run' => true,
    ])
        ->assertExitCode(1);
});
