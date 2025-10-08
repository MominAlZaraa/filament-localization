<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale to use when generating translation files.
    | If no locale is specified, this will be used.
    |
    */

    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | The locales that should be generated when running the localization command.
    | You can add or remove locales as needed.
    |
    */

    'locales' => [
        'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation File Structure
    |--------------------------------------------------------------------------
    |
    | The structure for organizing translation files.
    | Options: 'flat', 'nested', 'panel-based'
    |
    | - flat: All translations in lang/{locale}/filament.php
    | - nested: Organized by resource: lang/{locale}/filament/{resource}.php
    | - panel-based: Organized by panel and resource: lang/{locale}/filament/{panel}/{resource}.php
    |
    */

    'structure' => 'panel-based',

    /*
    |--------------------------------------------------------------------------
    | Backup Before Processing
    |--------------------------------------------------------------------------
    |
    | Whether to create a backup of files before modifying them.
    | Recommended to keep this enabled for safety.
    |
    */

    'backup' => true,

    /*
    |--------------------------------------------------------------------------
    | Git Integration
    |--------------------------------------------------------------------------
    |
    | Automatically create a git commit after localization.
    | This allows easy reverting if needed.
    |
    */

    'git' => [
        'enabled' => true,
        'commit_message' => 'chore: add Filament localization support',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Panels
    |--------------------------------------------------------------------------
    |
    | Panels to exclude from localization scanning.
    |
    */

    'excluded_panels' => [
        // 'blogger',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Resources
    |--------------------------------------------------------------------------
    |
    | Resources to exclude from localization scanning.
    |
    */

    'excluded_resources' => [
        // 'UserResource',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Types to Scan
    |--------------------------------------------------------------------------
    |
    | The Filament component types to scan for localization.
    |
    */

    'scan_components' => [
        'forms' => true,
        'tables' => true,
        'infolists' => true,
        'actions' => true,
        'notifications' => true,
        'widgets' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Label Generation Strategy
    |--------------------------------------------------------------------------
    |
    | How to generate default labels from field names.
    | Options: 'title_case', 'sentence_case', 'keep_original'
    |
    */

    'label_generation' => 'title_case',

    /*
    |--------------------------------------------------------------------------
    | Preserve Existing Labels
    |--------------------------------------------------------------------------
    |
    | If a field already has a label, should we preserve it or replace it?
    |
    */

    'preserve_existing_labels' => false,

    /*
    |--------------------------------------------------------------------------
    | Translation Key Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix to use for translation keys.
    | Example: 'filament' will generate keys like __('filament/admin.user.name')
    |
    */

    'translation_key_prefix' => 'filament',

    /*
    |--------------------------------------------------------------------------
    | Verbose Output
    |--------------------------------------------------------------------------
    |
    | Show detailed progress information during processing.
    |
    */

    'verbose' => true,

];
