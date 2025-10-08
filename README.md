# Filament Localization Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mominalzaraa/filament-localization.svg?style=flat-square)](https://packagist.org/packages/mominalzaraa/filament-localization)
[![Total Downloads](https://img.shields.io/packagist/dt/mominalzaraa/filament-localization.svg?style=flat-square)](https://packagist.org/packages/mominalzaraa/filament-localization)

Automatically scan and localize Filament resources with structured translation files. This package eliminates the repetitive task of manually adding translation keys to every field, column, action, and component in your Filament application.

## Features

- 🚀 **Automatic Scanning**: Scans all Filament resources across all panels
- 🏗️ **Structured Organization**: Creates organized translation files following Filament's structure
- 🔄 **Git Integration**: Automatically creates a commit for easy reverting
- 📊 **Detailed Statistics**: Shows comprehensive statistics of processed files
- 🎯 **Selective Processing**: Process specific panels or all panels at once
- 🔒 **Safe Operation**: Creates backups before modifying files
- 🌍 **Multi-locale Support**: Generate translations for multiple locales simultaneously
- ⚡ **Dry Run Mode**: Preview changes without applying them

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

### Other Components
- Actions, Filters, Notifications

## Installation

You can install the package via composer:

```bash
composer require mominalzaraa/filament-localization
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="filament-localization-config"
```

## Configuration

The configuration file `config/filament-localization.php` provides extensive customization options:

```php
return [
    'default_locale' => 'en',
    'locales' => ['en', 'el'],
    'structure' => 'panel-based', // flat, nested, or panel-based
    'backup' => true,
    'git' => [
        'enabled' => true,
        'commit_message' => 'chore: add Filament localization support',
    ],
    'excluded_panels' => [],
    'excluded_resources' => [],
    'label_generation' => 'title_case', // title_case, sentence_case, or keep_original
    'preserve_existing_labels' => false,
    'translation_key_prefix' => 'filament',
];
```

## Usage

### Basic Usage

Localize all panels and resources:

```bash
php artisan filament:localize
```

### Selective Processing

Localize specific panels:

```bash
php artisan filament:localize --panel=admin
```

Generate specific locales:

```bash
php artisan filament:localize --locale=en --locale=el --locale=fr
```

### Dry Run

Preview changes without applying them:

```bash
php artisan filament:localize --dry-run
```

### Force Update

Force update even if labels already exist:

```bash
php artisan filament:localize --force
```

### Skip Git Commit

Skip automatic git commit:

```bash
php artisan filament:localize --no-git
```

## Translation File Structure

The package creates organized translation files based on your configuration:

### Panel-Based Structure (Recommended)

```
lang/
├── en/
│   └── filament/
│       ├── admin/
│       │   ├── user_resource.php
│       │   ├── post_resource.php
│       │   └── ...
│       └── blogger/
│           ├── post_resource.php
│           ├── comment_resource.php
│           └── ...
└── el/
    └── filament/
        ├── admin/
        │   ├── user_resource.php
        │   └── ...
        └── blogger/
            ├── post_resource.php
            └── ...
```

### Nested Structure

```
lang/
├── en/
│   └── filament/
│       ├── user_resource.php
│       ├── post_resource.php
│       └── ...
└── el/
    └── filament/
        ├── user_resource.php
        └── ...
```

### Flat Structure

```
lang/
├── en/
│   └── filament.php
└── el/
    └── filament.php
```

## Example Output

When you run the command, you'll see detailed progress and statistics:

```
╔════════════════════════════════════════════════════════════════╗
║                                                                ║
║          Filament Localization Package v1.0.0                  ║
║                                                                ║
║  Automatically scan and localize Filament resources            ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝

⚠️  IMPORTANT NOTICE:
This command will programmatically update your application resources.
All processing is done locally with no external usage or handling.
Your resources will be updated for localization using Laravel's locale handling.

💡 A git commit will be created after processing for easy reverting.

+---------------------------+------------------+
| Setting                   | Value            |
+---------------------------+------------------+
| Panels to process         | admin, blogger   |
| Locales to generate       | en, el           |
| Structure                 | panel-based      |
| Dry run                   | No               |
| Git commit                | Yes              |
+---------------------------+------------------+

Do you want to proceed with localization? (yes/no) [yes]:
> yes

🚀 Starting localization process...

📦 Processing panel: admin
 15/15 [============================] 100% - Processing UserResource...

📦 Processing panel: blogger
 25/25 [============================] 100% - Processing PostResource...

📊 Localization Statistics:

+---------------------------+-------+
| Metric                    | Count |
+---------------------------+-------+
| Panels Processed          | 2     |
| Resources Processed       | 40    |
| Files Modified            | 40    |
| Translation Files Created | 80    |
| Translation Keys Added    | 1,250 |
| Fields Localized          | 450   |
| Actions Localized         | 120   |
| Columns Localized         | 680   |
| Total Processing Time     | 12.5s |
+---------------------------+-------+

📝 Creating git commit...
✅ Git commit created successfully!
💡 You can revert this commit using: git reset --soft HEAD~1

✅ Localization completed successfully!
```

## How It Works

1. **Scanning**: The package scans all Filament resources in your application
2. **Analysis**: It identifies all localizable components (fields, columns, actions, etc.)
3. **Translation Files**: Creates organized translation files with default labels
4. **Resource Modification**: Updates resource files to use translation keys
5. **Git Commit**: Creates a commit for easy reverting if needed

## Before and After

### Before

```php
TextInput::make('name')
    ->required(),

TextColumn::make('email')
    ->searchable(),

Action::make('delete')
    ->requiresConfirmation(),
```

### After

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

### Translation File Created

```php
// lang/en/filament/admin/user_resource.php
return [
    'name' => 'Name',
    'email' => 'Email',
    'delete' => 'Delete',
    // ... more translations
];
```

## Safety Features

- **Backups**: Creates timestamped backups before modifying files
- **Git Integration**: Automatic commit for easy reverting
- **Dry Run**: Preview changes before applying
- **Preserve Existing**: Option to preserve existing labels
- **Error Handling**: Comprehensive error reporting

## Reverting Changes

If you need to revert the changes:

```bash
# Using git (recommended)
git reset --soft HEAD~1

# Or restore from backups
# Backup files are created with .backup.YYYY-MM-DD-HH-II-SS extension
```

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher
- Filament 4.0 or higher

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Momin Al Zaraa](https://github.com/MominAlZaraa)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

If you find this package helpful, please consider:
- ⭐ Starring the repository
- 🐛 Reporting issues
- 💡 Suggesting new features
- 🤝 Contributing code

For support and questions:
- 📧 Email: [support@mominpert.com](mailto:support@mominpert.com)
- 🌐 Website: [mominpert.com](https://mominpert.com)
- 💬 GitHub Issues: [Report an issue](https://github.com/MominAlZaraa/filament-localization/issues)
- 📖 GitHub Discussions: [Ask a question](https://github.com/MominAlZaraa/filament-localization/discussions)

## Roadmap

- [ ] Support for custom components
- [ ] Translation suggestions using AI
- [ ] Batch translation import/export
- [ ] Translation validation
- [ ] IDE integration
- [ ] Web UI for managing translations

## FAQ

**Q: Will this overwrite my existing translations?**  
A: No, by default it preserves existing translations. You can change this in the config.

**Q: Can I use this with custom Filament components?**  
A: Currently it supports standard Filament components. Custom component support is planned.

**Q: Does this work with Filament plugins?**  
A: Yes, it works with any Filament resources in your application.

**Q: Can I customize the translation key format?**  
A: Yes, you can customize the prefix and structure in the configuration file.

**Q: Is this safe to use in production?**  
A: Yes, but we recommend testing in a development environment first and using the dry-run mode.
