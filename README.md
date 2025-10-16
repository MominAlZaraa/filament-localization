# Filament Localization Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mominalzaraa/filament-localization?style=flat-square&logo=packagist)](https://packagist.org/packages/mominalzaraa/filament-localization)
[![Total Downloads](https://img.shields.io/packagist/dt/mominalzaraa/filament-localization?style=flat-square&logo=packagist)](https://packagist.org/packages/mominalzaraa/filament-localization)
[![GitHub Tests](https://img.shields.io/github/actions/workflow/status/MominAlZaraa/filament-localization/run-tests.yml?branch=main&label=tests&style=flat-square&logo=github)](https://github.com/MominAlZaraa/filament-localization/actions/workflows/run-tests.yml)
[![Code Style](https://img.shields.io/github/actions/workflow/status/MominAlZaraa/filament-localization/code-style.yml?branch=main&label=code%20style&style=flat-square&logo=github)](https://github.com/MominAlZaraa/filament-localization/actions/workflows/code-style.yml)
[![License](https://img.shields.io/packagist/l/mominalzaraa/filament-localization?style=flat-square)](https://github.com/MominAlZaraa/filament-localization/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/dependency-v/mominalzaraa/filament-localization/php?style=flat-square&logo=php)](https://packagist.org/packages/mominalzaraa/filament-localization)

![Filament Localization Banner](.github/plugin-banner.jpg)

Automatically scan and localize Filament resources with structured translation files. This package eliminates the repetitive task of manually adding translation keys to every field, column, action, and component in your Filament application.

**Requirements**: PHP ^8.2|^8.3|^8.4 | Laravel ^12.0 | Filament ^4.0

> **🆕 Latest Updates**: Enhanced DeepL integration with smart translation detection, page localization support, and intelligent skip terms configuration.

## Features

- 🚀 **Automatic Scanning**: Scans all Filament resources, pages, and relation managers
- 🏗️ **Smart Localization**: Removes hardcoded labels and creates translation keys
- 🤖 **DeepL Integration**: Intelligent translation with smart detection of untranslated content
- 📊 **Structured Organization**: Creates organized translation files
- 🔄 **Git Integration**: Automatic commits for easy reverting
- ⚡ **Zero Configuration**: Works out of the box
- 🎯 **Selective Processing**: Process specific panels or all at once
- 🔒 **Safe Operation**: Backups and dry-run mode
- 🧠 **Smart Detection**: Automatically detects untranslated content
- 🚫 **Skip Terms**: Intelligently skips acronyms and technical terms

## Supported Components

### Form Components
- TextInput, Textarea, Select, DatePicker, DateTimePicker, TimePicker
- Checkbox, Toggle, Radio, FileUpload
- RichEditor, MarkdownEditor, ColorPicker
- KeyValue, Repeater, Builder, TagsInput
- CheckboxList, Hidden, ViewField

### Table Columns
- TextColumn, IconColumn, ImageColumn, ColorColumn
- CheckboxColumn, ToggleColumn, SelectColumn, TextInputColumn

### Infolist Entries
- TextEntry, IconEntry, ImageEntry, ColorEntry
- KeyValueEntry, RepeatableEntry

### Layout Components
- Section, Fieldset, Grid, Tabs, Wizard, Step, Group

### Filament Pages
- Dashboard pages with static properties
- Custom pages with hardcoded strings
- Page methods (getTitle, getHeading, getNavigationLabel)

### Other Components
- Actions, Filters, Notifications, Relation Managers

## Installation

Install the package via Composer (automatically installs all dependencies):

```bash
composer require mominalzaraa/filament-localization
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="filament-localization-config"
```

## Configuration

The configuration file `config/filament-localization.php` provides customization options:

```php
return [
    'default_locale' => 'en',
    'locales' => ['en', 'es', 'fr'],
    'structure' => 'panel-based', // flat, nested, or panel-based
    'backup' => true,
    'git' => [
        'enabled' => true,
        'commit_message' => 'chore: add Filament localization support',
    ],
    'excluded_panels' => [],
    'excluded_resources' => [],
    'translation_key_prefix' => 'filament',
    
    // Skip Terms - Terms that should remain the same across languages
    'skip_identical_terms' => [
        'API', 'URL', 'HTTP', 'HTTPS', 'PDF', 'CSV', 'JSON', 'XML',
        'Laravel', 'Filament', 'Vue', 'React', 'Angular', 'Google', 'Microsoft',
        'GitHub', 'Docker', 'AWS', 'Azure', 'PayPal', 'Stripe', 'WordPress',
        'Bootstrap', 'Tailwind', 'jQuery', 'TypeScript', 'Webpack', 'Vite',
        'JWT', 'OAuth', 'REST', 'GraphQL', 'SEO', 'UX', 'UI', 'SaaS', 'PaaS',
        'IoT', 'AI', 'ML', 'DevOps', 'CI/CD', 'Agile', 'Scrum', 'TDD', 'BDD',
        'GDPR', 'ISO', 'IEEE', 'W3C', 'OWASP', 'NIST', 'ITIL', 'PMP',
    ],
    
    // DeepL Translation Configuration
    'deepl' => [
        'api_key' => env('DEEPL_API_KEY'),
        'base_url' => env('DEEPL_BASE_URL', 'https://api-free.deepl.com/v2'),
        'timeout' => 60,
        'batch_size' => 50,
        'preserve_formatting' => true,
    ],
];
```

## Commands

The package provides two commands for localization and translation:

### 1. `filament:localize` - Main Localization Command

Scans and localizes Filament resources with structured translation files.

```bash
# Basic usage
php artisan filament:localize

# Specific panels and locales
php artisan filament:localize --panel=admin --locale=en --locale=es

# Preview changes
php artisan filament:localize --dry-run

# Force update existing labels
php artisan filament:localize --force

# Skip git commit
php artisan filament:localize --no-git
```

**Options:**
- `--panel=*` - Specific panel(s) to localize
- `--locale=*` - Specific locale(s) to generate  
- `--force` - Force localization even if labels exist
- `--no-git` - Skip git commit
- `--dry-run` - Preview changes without applying them

### 2. `filament:translate-with-deepl` - DeepL Translation Command

Intelligently translates Filament resources using the DeepL API with smart detection of untranslated content.

**Prerequisites:**
1. Get a DeepL API key from [DeepL API](https://www.deepl.com/pro-api)
2. Add to your `.env` file: `DEEPL_API_KEY=your_deepl_api_key_here`

```bash
# Smart translation - detects untranslated content automatically
php artisan filament:translate-with-deepl --source-lang=en --target-lang=es --panel=admin

# Force mode - overwrites all existing translations
php artisan filament:translate-with-deepl --source-lang=en --target-lang=fr --panel=admin --force

# Multiple panels with preview
php artisan filament:translate-with-deepl --source-lang=en --target-lang=de --panel=admin --panel=blog --dry-run
```

**Features:**
- **Smart Detection**: Automatically detects untranslated content
- **Skip Terms**: Intelligently skips acronyms and technical terms
- **Force Mode**: Overwrites existing translations when using `--force`
- **Mixed Content**: Processes files with both translated and untranslated content

**Options:**
- `--source-lang=SOURCE-LANG` - Source language code (default: "en")
- `--target-lang=TARGET-LANG` - Target language code (required)
- `--panel=PANEL` - Specific panel(s) to translate
- `--force` - Force translation and overwrite all existing translations
- `--dry-run` - Preview changes without applying them

**Supported Languages:** 40+ languages including English, Spanish, French, German, Italian, Portuguese, Russian, Japanese, Chinese, Arabic, and more.

## Quick Start

1. **Generate translation files:**
   ```bash
   php artisan filament:localize --panel=admin
   ```

2. **Translate with DeepL (optional):**
   ```bash
   php artisan filament:translate-with-deepl --source-lang=en --target-lang=es --panel=admin
   ```

3. **Install language switcher:**
   ```bash
   composer require craft-forge/filament-language-switcher
   ```

4. **Configure in your panel provider:**
   ```php
   FilamentLanguageSwitcherPlugin::make()
       ->locales([
           ['code' => 'en', 'name' => 'English', 'flag' => 'gb'],
           ['code' => 'es', 'name' => 'Spanish', 'flag' => 'es'],
       ])
   ```

## Translation File Structure

The package creates organized translation files based on your configuration:

**Panel-Based Structure (Recommended):**
```
lang/
├── en/filament/admin/user_resource.php
├── en/filament/blog/post_resource.php
└── es/filament/admin/user_resource.php
```

**Other Structures:** Nested or flat structures available via configuration.

## How It Works

1. **Scans** all Filament resources, pages, and relation managers
2. **Identifies** localizable components (fields, columns, actions, static properties, methods)
3. **Removes** hardcoded labels and replaces with translation keys
4. **Creates** organized translation files with default labels
5. **Updates** resource and page files to use dynamic translation keys
6. **Intelligently translates** using DeepL API with smart detection:
   - Missing keys (keys that don't exist in target language)
   - Untranslated content (identical to source language)
   - Skips acronyms and technical terms that should remain the same
7. **Creates** git commit for easy reverting

## Key Features

### Smart Label Management
Automatically removes hardcoded labels and replaces them with translation keys:

```php
// Before
protected static ?string $modelLabel = 'Articles';

// After  
protected static ?string $modelLabel = null;

public static function getModelLabel(): string
{
    return __('filament/admin/user_resource.model_label');
}
```

### DeepL Integration
- **Smart Detection**: Automatically detects untranslated content
- **Skip Terms**: Intelligently skips acronyms and technical terms
- **Force Mode**: Overwrites existing translations when using `--force`
- **Mixed Content**: Processes files with both translated and untranslated content
- High accuracy translations with 40+ supported languages
- Context-aware processing with formatting preservation
- Batch processing with usage monitoring
- Graceful error handling and fallbacks

### Page Localization Support
Now supports Filament pages with static properties and methods:

```php
// Before
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Blog Dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
}

// After
class Dashboard extends BaseDashboard
{
    protected static ?string $title = null;
    protected static ?string $navigationLabel = null;

    public function getTitle(): string
    {
        return __('filament/admin/dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament/admin/dashboard.navigation_label');
    }
}
```

### Smart Translation Detection
The DeepL translation command intelligently detects what needs to be translated:

**Normal Mode (Default):**
- Translates missing keys (keys that don't exist in target language)
- Translates keys with identical content to source (indicating untranslated content)
- Skips terms configured in `skip_identical_terms` (e.g., "SMS", "API", "URL")
- Preserves existing properly translated content

**Force Mode (`--force`):**
- Overwrites ALL existing translations with new ones from source language
- Useful for complete retranslation or when source language has been updated

**Example Scenarios:**

```php
// Source (English)
return [
    'title' => 'Blog Dashboard',
    'navigation_label' => 'Send Email',
    'description' => 'Manage your blog posts and comments',
];

// Target (Spanish) - Before Translation
return [
    'title' => 'Blog Dashboard',           // ← Will be translated (identical to source)
    'navigation_label' => 'Send Email',    // ← Will be translated (not in skip terms)
    'description' => 'Gestiona tus publicaciones', // ← Will be preserved (properly translated)
];

// Target (Spanish) - After Translation
return [
    'title' => 'Panel de Blog',            // ← Translated
    'navigation_label' => 'Enviar Email',  // ← Translated
    'description' => 'Gestiona tus publicaciones', // ← Preserved
];
```

## Language Switching

This package generates translation files. To enable language switching in your Filament panels, install a language switcher plugin:

**Recommended:** `craft-forge/filament-language-switcher`

```bash
composer require craft-forge/filament-language-switcher
```

**Configure in your panel provider:**
```php
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentLanguageSwitcherPlugin::make()
                ->locales([
                    ['code' => 'en', 'name' => 'English', 'flag' => 'gb'],
                    ['code' => 'es', 'name' => 'Spanish', 'flag' => 'es'],
                ]),
        ]);
}
```

**Other options:** `filament/spatie-laravel-translatable-plugin`, `awcodes/filament-language-switcher`

## Before and After

### Visual Comparison

#### Before Localization
The Filament admin interface displays in English with hardcoded labels:

![Before Localization - Users List](photos/before.png)
*Users list page showing English labels*

![Before Localization - User Edit Form](photos/before-2.png)
*User edit form with English field labels*

![Before Localization - File Structure](photos/before-3.png)
*File structure showing only English translation files*

#### After Localization
The same interface now supports multiple languages with proper translation keys:

![After Localization - Users List](photos/after.png)
*Users list page with localized Greek labels*

![After Localization - User Edit Form](photos/after-2.png)
*User edit form with localized Greek field labels*

![After Localization - File Structure](photos/after-3.png)
*File structure showing organized translation files for multiple locales*

### Code Comparison

**Before Localization:**
```php
TextInput::make('name')->required(),
TextColumn::make('email')->searchable(),
Action::make('delete')->requiresConfirmation(),
```

**After Localization:**
```php
TextInput::make('name')
    ->label(__('filament/admin/user_resource.name'))
    ->required(),

TextColumn::make('email')
    ->label(__('filament/admin/user_resource.email'))
    ->searchable(),

Action::make('delete')
    ->label(__('filament/admin/user_resource.delete'))
    ->requiresConfirmation(),
```

**Translation Files Created:**
```php
// lang/en/filament/admin/user_resource.php
return ['name' => 'Name', 'email' => 'Email', 'delete' => 'Delete'];

// lang/es/filament/admin/user_resource.php  
return ['name' => 'Nombre', 'email' => 'Email', 'delete' => 'Eliminar'];
```

## Safety Features

- **Backups**: Creates timestamped backups before modifying files
- **Git Integration**: Automatic commit for easy reverting (`git reset --soft HEAD~1`)
- **Dry Run**: Preview changes before applying
- **Error Handling**: Comprehensive error reporting

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

- 📧 Email: [support@mominpert.com](mailto:support@mominpert.com)
- 🌐 Website: [mominpert.com](https://mominpert.com)
- 💬 GitHub Issues: [Report an issue](https://github.com/MominAlZaraa/filament-localization/issues)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
