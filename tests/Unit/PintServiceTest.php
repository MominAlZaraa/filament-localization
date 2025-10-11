<?php

use MominAlZaraa\FilamentLocalization\Services\PintService;

test('pint service can be instantiated', function () {
    $service = app(PintService::class);
    expect($service)->toBeInstanceOf(PintService::class);
});

test('pint service can check if pint is available', function () {
    $service = app(PintService::class);

    $isAvailable = $service->isPintAvailable();
    expect($isAvailable)->toBeBool();
});

test('pint service can format code', function () {
    $service = app(PintService::class);

    $result = $service->formatCode();
    expect($result)->toBeBool();
});

test('pint service can format code with output', function () {
    $service = app(PintService::class);

    $result = $service->formatCodeWithOutput();
    expect($result)->toBeArray();
    expect($result)->toHaveKey('success');
    expect($result['success'])->toBeBool();
    expect($result)->toHaveKey('output');
    expect($result['output'])->toBeString();
});
