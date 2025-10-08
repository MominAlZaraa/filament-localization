<?php

test('package is configured correctly', function () {
    expect(config('filament-localization.default_locale'))->toBe('en');
    expect(config('filament-localization.structure'))->toBe('panel-based');
});

test('translation key prefix is correct', function () {
    expect(config('filament-localization.translation_key_prefix'))->toBe('filament');
});

test('backup is enabled by default', function () {
    expect(config('filament-localization.backup'))->toBeTrue();
});

test('git integration is enabled by default', function () {
    expect(config('filament-localization.git.enabled'))->toBeTrue();
});
