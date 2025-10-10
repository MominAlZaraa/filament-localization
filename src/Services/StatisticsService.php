<?php

namespace MominAlZaraa\FilamentLocalization\Services;

class StatisticsService
{
    protected array $statistics = [
        'panels_processed' => 0,
        'resources_processed' => 0,
        'files_modified' => 0,
        'translation_files_created' => 0,
        'translation_keys_added' => 0,
        'fields_localized' => 0,
        'actions_localized' => 0,
        'columns_localized' => 0,
        'errors' => [],
    ];

    public function reset(): void
    {
        $this->statistics = [
            'panels_processed' => 0,
            'resources_processed' => 0,
            'files_modified' => 0,
            'translation_files_created' => 0,
            'translation_keys_added' => 0,
            'fields_localized' => 0,
            'actions_localized' => 0,
            'columns_localized' => 0,
            'errors' => [],
        ];
    }

    public function incrementPanelsProcessed(): void
    {
        $this->statistics['panels_processed']++;
    }

    public function incrementResourcesProcessed(): void
    {
        $this->statistics['resources_processed']++;
    }

    public function incrementFilesModified(): void
    {
        $this->statistics['files_modified']++;
    }

    public function incrementTranslationFilesCreated(): void
    {
        $this->statistics['translation_files_created']++;
    }

    public function incrementTranslationKeysAdded(int $count = 1): void
    {
        $this->statistics['translation_keys_added'] += $count;
    }

    public function incrementFieldsLocalized(int $count = 1): void
    {
        $this->statistics['fields_localized'] += $count;
    }

    public function incrementActionsLocalized(int $count = 1): void
    {
        $this->statistics['actions_localized'] += $count;
    }

    public function incrementColumnsLocalized(int $count = 1): void
    {
        $this->statistics['columns_localized'] += $count;
    }

    public function addError(string $error): void
    {
        $this->statistics['errors'][] = $error;
    }

    public function getStatistics(): array
    {
        return $this->statistics;
    }
}
