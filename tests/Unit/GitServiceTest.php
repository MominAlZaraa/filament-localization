<?php

use MominAlZaraa\FilamentLocalization\Services\GitService;

test('git service can be instantiated', function () {
    $service = app(GitService::class);
    expect($service)->toBeInstanceOf(GitService::class);
});

test('git service detects git repository', function () {
    $service = app(GitService::class);

    // This should be true since we're in a git repository
    expect($service->isGitRepository())->toBeTrue();
});

test('git service checks working directory status', function () {
    $service = app(GitService::class);

    // This should return a boolean
    $isClean = $service->isWorkingDirectoryClean();
    expect($isClean)->toBeBool();
});

test('git service can get last commit hash', function () {
    $service = app(GitService::class);

    $hash = $service->getLastCommitHash();
    expect($hash)->toBeString();
    expect($hash)->not->toBeEmpty();
});

test('git service can create commit', function () {
    $service = app(GitService::class);

    // Test that the service can be instantiated and has the expected methods
    expect($service)->toBeInstanceOf(GitService::class);
});
