<?php

use MominAlZaraa\FilamentLocalization\Services\DeepLTranslationService;

test('deepl service can be instantiated', function () {
    $service = app(DeepLTranslationService::class);
    expect($service)->toBeInstanceOf(DeepLTranslationService::class);
});

test('deepl service handles missing api key gracefully', function () {
    config(['filament-localization.deepl.api_key' => null]);

    $service = app(DeepLTranslationService::class);

    // Should not throw exception when API key is missing
    expect($service)->toBeInstanceOf(DeepLTranslationService::class);
    expect($service->isConfigured())->toBeFalse();
});

test('deepl service configuration is loaded', function () {
    expect(config('filament-localization.deepl.api_key'))->not->toBeNull();
    expect(config('filament-localization.deepl.api_key'))->toBeString();
});
