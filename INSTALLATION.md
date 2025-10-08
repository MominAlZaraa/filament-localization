# Installation & Usage Guide

## Quick Start

### 1. Install the Package

```bash
composer require mominalzaraa/filament-localization
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag="filament-localization-config"
```

### 3. Configure Your Preferences

Edit `config/filament-localization.php` to customize:

```php
return [
    // Add your desired locales
    'locales' => ['en', 'el', 'fr', 'de'],
    
    // Choose your preferred structure
    'structure' => 'panel-based', // or 'nested' or 'flat'
    
    // Enable/disable git integration
    'git' => [
        'enabled' => true,
        'commit_message' => 'chore: add Filament localization support',
    ],
];
```

### 4. Run the Localization Command

```bash
php artisan filament:localize
```

## Detailed Configuration

### Translation File Structure

Choose how you want your translation files organized:

#### Panel-Based (Recommended)
Best for applications with multiple panels (admin, blogger, etc.)

```php
'structure' => 'panel-based',
```

Creates:
```
lang/
├── en/filament/admin/user_resource.php
├── en/filament/blogger/post_resource.php
└── ...
```

#### Nested
Good for single-panel applications

```php
'structure' => 'nested',
```

Creates:
```
lang/
├── en/filament/user_resource.php
├── en/filament/patient_resource.php
└── ...
```

#### Flat
All translations in one file (not recommended for large apps)

```php
'structure' => 'flat',
```

Creates:
```
lang/
└── en/filament.php
```

### Label Generation Strategy

Control how field names are converted to labels:

```php
// 'user_name' → 'User Name'
'label_generation' => 'title_case',

// 'user_name' → 'User name'
'label_generation' => 'sentence_case',

// 'user_name' → 'user name'
'label_generation' => 'keep_original',
```

### Excluding Resources

Exclude specific panels or resources from processing:

```php
'excluded_panels' => [
    'admin', // Don't process admin panel
],

'excluded_resources' => [
    'UserResource', // Don't process UserResource
    'SettingResource',
],
```

## Usage Examples

### Process All Panels

```bash
php artisan filament:localize
```

### Process Specific Panels

```bash
# Single panel
php artisan filament:localize --panel=admin

# Multiple panels
php artisan filament:localize --panel=admin
```

### Generate Specific Locales

```bash
# Single locale
php artisan filament:localize --locale=en

# Multiple locales
php artisan filament:localize --locale=en --locale=el --locale=fr
```

### Preview Changes (Dry Run)

```bash
php artisan filament:localize --dry-run
```

This will show you what changes would be made without actually modifying any files.

### Force Update Existing Labels

```bash
php artisan filament:localize --force
```

This will update labels even if they already exist.

### Skip Git Commit

```bash
php artisan filament:localize --no-git
```

Useful if you want to review changes before committing.

### Combined Options

```bash
php artisan filament:localize \
    --panel=admin \
    --panel=blogger \
    --locale=en \
    --locale=el \
    --force \
    --dry-run
```

## Post-Installation Steps

### 1. Review Generated Translation Files

Check the generated translation files in `lang/{locale}/filament/`:

```php
// lang/en/filament/admin/user_resource.php
return [
    'name' => 'Name',
    'email' => 'Email',
    'password' => 'Password',
    'created_at' => 'Created At',
    // ... more translations
];
```

### 2. Customize Translations

Edit the translation files to improve or customize the labels:

```php
// lang/en/filament/admin/user_resource.php
return [
    'name' => 'Full Name', // Customized
    'email' => 'Email Address', // Customized
    'password' => 'Password',
    'created_at' => 'Registration Date', // Customized
];
```

### 3. Add Translations for Other Locales

Copy the English translations and translate them:

```php
// lang/el/filament/admin/user_resource.php
return [
    'name' => 'Ονοματεπώνυμο',
    'email' => 'Διεύθυνση Email',
    'password' => 'Κωδικός Πρόσβασης',
    'created_at' => 'Ημερομηνία Εγγραφής',
];
```

### 4. Test Your Application

Visit your Filament panels and verify that all labels are displaying correctly.

### 5. Switch Locales

Test with different locales to ensure translations work:

```php
// In your middleware or controller
app()->setLocale('el');
```

## Reverting Changes

If you need to undo the localization:

### Using Git (Recommended)

```bash
# Soft reset (keeps changes in working directory)
git reset --soft HEAD~1

# Hard reset (discards all changes)
git reset --hard HEAD~1
```

### Using Backups

The package creates timestamped backups before modifying files:

```bash
# Find backup files
find . -name "*.backup.*"

# Restore a specific file
cp path/to/file.php.backup.2025-01-08-12-30-45 path/to/file.php
```

## Troubleshooting

### Issue: "Not a git repository"

**Solution**: Initialize git or use `--no-git` flag:

```bash
git init
# or
php artisan filament:localize --no-git
```

### Issue: "Working directory is not clean"

**Solution**: Commit or stash your changes first:

```bash
git add .
git commit -m "Save current work"
# or
git stash
```

### Issue: Labels not displaying

**Solution**: Clear your application cache:

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Issue: Some fields not localized

**Solution**: Check if they're in the excluded list or run with `--force`:

```bash
php artisan filament:localize --force
```

## Best Practices

### 1. Test in Development First

Always test the localization in a development environment before running in production.

### 2. Use Dry Run Mode

Preview changes before applying them:

```bash
php artisan filament:localize --dry-run
```

### 3. Commit Before Running

Create a commit before running the command so you can easily revert:

```bash
git add .
git commit -m "Before localization"
php artisan filament:localize
```

### 4. Review Generated Translations

Always review and improve the auto-generated translations.

### 5. Use Panel-Based Structure

For multi-panel applications, use the panel-based structure for better organization.

### 6. Keep Backups

Don't delete the backup files immediately. Keep them for a few days.

## Advanced Usage

### Custom Translation Keys

You can customize the translation key format in the config:

```php
'translation_key_prefix' => 'app', // Changes from 'filament' to 'app'
```

This will generate keys like:
```php
__('app/admin/user_resource.name')
```

### Programmatic Usage

You can use the services programmatically in your code:

```php
use Momin00\FilamentLocalization\Services\LocalizationService;

$service = new LocalizationService();
$service->processResource(
    UserResource::class,
    Filament::getPanel('admin'),
    ['en', 'el'],
    false // dry run
);
```

### Extending the Package

You can extend the analyzer to support custom components:

```php
namespace App\Services;

use Momin00\FilamentLocalization\Analyzers\ResourceAnalyzer as BaseAnalyzer;

class CustomResourceAnalyzer extends BaseAnalyzer
{
    protected array $customComponents = [
        'CustomInput',
        'CustomSelect',
    ];
    
    // Add your custom analysis logic
}
```

## Integration with CI/CD

### GitHub Actions

```yaml
name: Localization Check

on: [push, pull_request]

jobs:
  check-localization:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
      - name: Install Dependencies
        run: composer install
      - name: Check Localization
        run: php artisan filament:localize --dry-run
```

### Pre-commit Hook

```bash
#!/bin/bash
# .git/hooks/pre-commit

php artisan filament:localize --dry-run
if [ $? -ne 0 ]; then
    echo "Localization check failed. Please run: php artisan filament:localize"
    exit 1
fi
```

## Support

For issues, questions, or contributions:

- 📧 Email: [support@mominpert.com](mailto:support@mominpert.com)
- 🌐 Website: [mominpert.com](https://mominpert.com)
- 📝 [GitHub Issues](https://github.com/MominAlZaraa/filament-localization/issues)
- 💬 [Discussions](https://github.com/MominAlZaraa/filament-localization/discussions)

## Next Steps

1. ✅ Install the package
2. ✅ Configure your preferences
3. ✅ Run the localization command
4. ✅ Review generated translations
5. ✅ Customize translations as needed
6. ✅ Test with different locales
7. ✅ Deploy to production

Happy localizing! 🌍
