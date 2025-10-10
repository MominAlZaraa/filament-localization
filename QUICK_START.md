# Quick Start Guide

## 🚀 Get Started in 3 Minutes

### Step 1: Install
```bash
composer require mominalzaraa/filament-localization
```

### Step 2: Publish Config
```bash
php artisan vendor:publish --tag="filament-localization-config"
```

### Step 3: Run
```bash
php artisan filament:localize
```

That's it! Your Filament application is now localized. 🎉

## What Just Happened?

The package:
1. ✅ Scanned all your Filament resources
2. ✅ Added translation keys to all fields, columns, and actions
3. ✅ Created organized translation files
4. ✅ Created a git commit for easy reverting

## Next Steps

### 1. Review Generated Translations

Check your translation files:
```
lang/en/filament/{panel}/{resource}.php
```

Example:
```
lang/en/filament/blogger/post_resource.php
```

### 2. Customize Translations

Edit the files to improve labels:
```php
// lang/en/filament/blogger/post_resource.php
return [
    'title' => 'Post Title', // Customize as needed
    'content' => 'Post Content',
    'published_at' => 'Publication Date',
    // ...
];
```

### 3. Add More Locales

Edit `config/filament-localization.php`:
```php
'locales' => ['en', 'el', 'fr', 'de'],
```

Then run again:
```bash
php artisan filament:localize
```

### 4. Translate to Other Languages

Copy English translations and translate:
```php
// lang/el/filament/blogger/post_resource.php
return [
    'title' => 'Τίτλος Άρθρου',
    'content' => 'Περιεχόμενο Άρθρου',
    'published_at' => 'Ημερομηνία Δημοσίευσης',
    // ...
];
```

## Common Commands

```bash
# Preview changes without applying
php artisan filament:localize --dry-run

# Process specific panel
php artisan filament:localize --panel=blogger

# Force update existing labels
php artisan filament:localize --force

# Skip git commit
php artisan filament:localize --no-git
```

## Need to Revert?

```bash
# Undo the localization
git reset --soft HEAD~1

# Or restore from backups
# Backup files: *.backup.YYYY-MM-DD-HH-II-SS
```

## Configuration Options

Edit `config/filament-localization.php`:

```php
return [
    // Add your locales
    'locales' => ['en', 'el'],
    
    // Choose structure: 'panel-based', 'nested', or 'flat'
    'structure' => 'panel-based',
    
    // Enable/disable features
    'backup' => true,
    'git' => ['enabled' => true],
    
    // Exclude panels or resources
    'excluded_panels' => [],
    'excluded_resources' => [],
];
```

## Example Output

```
╔════════════════════════════════════════════════════════════════╗
║          Filament Localization Package v1.0.0                  ║
╚════════════════════════════════════════════════════════════════╝

🚀 Starting localization process...

📦 Processing panel: admin
 15/15 [============================] 100%

📊 Localization Statistics:

+---------------------------+-------+
| Metric                    | Count |
+---------------------------+-------+
| Resources Processed       | 15    |
| Translation Files Created | 30    |
| Fields Localized          | 180   |
| Total Processing Time     | 3.2s  |
+---------------------------+-------+

✅ Localization completed successfully!
```

## Troubleshooting

### Issue: "Not a git repository"
```bash
git init
# or
php artisan filament:localize --no-git
```

### Issue: Labels not showing
```bash
php artisan cache:clear
php artisan config:clear
```

### Issue: Some fields not localized
```bash
php artisan filament:localize --force
```

## Documentation

- 📖 [Full Documentation](README.md)
- 🔧 [Installation Guide](INSTALLATION.md)
- 📋 [Complete Summary](PACKAGE_SUMMARY.md)
- 📝 [Changelog](CHANGELOG.md)

## Support

- 📧 Email: [support@mominpert.com](mailto:support@mominpert.com)
- 🌐 Website: [mominpert.com](https://mominpert.com)
- 🐛 [Report Issues](https://github.com/MominAlZaraa/filament-localization/issues)
- 💬 [Discussions](https://github.com/MominAlZaraa/filament-localization/discussions)
- ⭐ [Star on GitHub](https://github.com/MominAlZaraa/filament-localization)

## Features

✅ Automatic scanning of all Filament resources  
✅ Support for 40+ Filament component types  
✅ Organized translation file structure  
✅ Git integration for easy reverting  
✅ Dry run mode for previewing changes  
✅ Comprehensive statistics reporting  
✅ Multi-locale support  
✅ Backup creation before modifications  
✅ Selective panel processing  
✅ Configurable label generation  

---

**Happy Localizing! 🌍**

Made with ❤️ for the Filament community
