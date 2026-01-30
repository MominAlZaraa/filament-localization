# Changelog

All notable changes to `filament-localization` will be documented in this file.

## v2.0.0 - 2026-01-30

### Added

- **PHP 8.5** — Composer allows PHP `^8.3|^8.4|^8.5`. v2.x supports PHP 8.3, 8.4, and 8.5 only.
- **Filament 5 & Livewire 4** — Full compatibility with Filament v5.x (Livewire v4.x, Tailwind v4.x). All analyzers and modifiers work with Filament 5's structure (Forms, Tables, Infolists, Actions, Schemas).
- **README** — Version support table; "Supported" and "Deprecated" lines aligned with filament-team-guard style.

### Changed (Breaking)

- **Filament** — Requirement `^4.0` → `^5.0`. v2.x is Filament 5 only; use [v1.x](https://github.com/MominAlZaraa/filament-localization/releases) for Filament 4.
- **PHP** — Requirement `^8.2|^8.3|^8.4` → `^8.3|^8.4|^8.5`. PHP 8.2 is no longer supported in v2.x.
- **CI** — GitHub Actions test matrix updated to PHP 8.3, 8.4, and 8.5 (8.2 removed).

### Compatibility

- **Supported**: PHP ^8.3–^8.5, Laravel ^12.0, Filament ^5.0, Livewire ^4.0.
- **Deprecated (use v1.x)**: PHP &lt;8.3, Filament v4, Livewire v3.

---

## v1.0.3 - 2025-11-18

### Major Features

- 🤖 **DeepL API Integration** (NEW): Full translation service with intelligent detection of untranslated content
  - Complete DeepLTranslationService implementation for automated translations
  - New `filament:translate-deepl` command for translating existing translation files
  - Smart detection of missing translations and identical content
  - Support for 40+ languages with automatic language detection
  - Batch processing with usage monitoring
  - Configurable skip terms for acronyms and technical terms (API, URL, SMS, etc.)
  - Force mode to overwrite existing translations
  - Context-aware processing with formatting preservation

- 📊 **Widget Localization Support** (NEW): Complete widget detection and localization
  - New WidgetAnalyzer for detecting Filament widget classes
  - New WidgetModifier for automatic widget localization
  - Automatic scanning of Filament widgets
  - Translation key generation for widget labels and properties
  - Added `processWidget()` method to LocalizationService

- 🔗 **RelationManager Localization** (NEW): Full support for Filament relation managers
  - New RelationManagerAnalyzer for detecting relation manager classes
  - New RelationManagerModifier for automatic relation manager localization
  - Added `processRelationManager()` method to LocalizationService
  - Support for all relation manager components and actions

- 📄 **Page Localization Support** (NEW): Complete page detection and localization
  - New PageAnalyzer for detecting Filament page classes
  - New PageModifier for automatic page localization
  - Added `processPage()` method to LocalizationService
  - Support for static properties and methods in pages
  - Better handling of dashboard pages and custom pages

### Enhancements

- 🎨 **Laravel Pint Integration** (NEW): Added Laravel Pint for consistent code styling
  - New PintService for automatic code formatting
  - Integration with LocalizeFilamentCommand to format code after localization
  - Updated Pint version to ^1.25 in composer.json
  - GitHub Actions workflow updates for code style checks
  - Improved code quality and consistency

- 💰 **Funding Configuration** (NEW): Added GitHub Sponsors funding support
  - New .github/FUNDING.yml file
  - Improved package ownership and maintainer information

- 🎯 **Enhanced Resource Analyzer**: Significant improvements to ResourceAnalyzer
  - Enhanced class checking and detection
  - Better resource identification
  - Improved analysis capabilities

- 🔧 **Enhanced Resource Modifier**: Major improvements to ResourceModifier
  - Better compatibility and handling
  - Improved modification logic
  - Enhanced translation key generation

- 📝 **Enhanced Translation File Generator**: Improvements to TranslationFileGenerator
  - Better file structure generation
  - Improved key organization
  - Enhanced locale handling

- 🧪 **Enhanced Testing**: Expanded test coverage
  - New tests for DeepL integration (DeepLIntegrationTest)
  - New Widget analyzer tests (Unit/PageAnalyzerTest - actually for widgets)
  - New RelationManager analyzer tests (Unit/RelationManagerAnalyzerTest)
  - New Pint service tests (Unit/PintServiceTest)
  - New LocalizeFilamentCommand tests (Feature/LocalizeFilamentCommandTest)
  - New Resource modifier tests (Feature/ResourceModifierTest)
  - New Git service tests (Unit/GitServiceTest)
  - Improved ServiceProvider tests

- ⚙️ **Configuration Improvements**: Enhanced configuration options
  - DeepL API configuration with batch size settings
  - Skip identical terms configuration
  - Improved default settings
  - New config options for DeepL integration

- 📚 **Documentation Updates**: Comprehensive documentation improvements
  - New TESTING.md file
  - DeepL integration guide in README
  - Widget localization examples
  - RelationManager localization examples
  - Enhanced README with new features
  - New .env.example file

### Bug Fixes

- 🔧 Fixed backslash handling in translation keys
- 🐛 Improved error handling in DeepL translation service
- ⚡ Enhanced resource modifier for better compatibility
- 🔧 Fixed various code style issues across multiple files

### Developer Experience

- 🛠️ Removed test-package.sh from repository (contains personal information)
- 📝 Improved code formatting across all files with Laravel Pint
- 🔄 Better git integration and commit messages
- 🗑️ Removed CheckDependenciesCommand (no longer needed)

## v1.0.2 - 2025-10-08

### Enhancements

- 🔧 **Enhanced Composer Package Handling**: Improved package metadata and dependencies
  - Updated composer.json with better package information
  - Enhanced package handling for Packagist features

- 🎨 **Code Style Workflows**: Added GitHub Actions workflows for code style
  - New code-style.yml workflow for automated style checking
  - New fix-php-code-style-issues.yml workflow for automatic fixes
  - Improved code consistency across the package

- 📦 **New Command**: Added CheckDependenciesCommand
  - Dependency checking functionality
  - Better error handling for missing dependencies

- 📸 **Documentation Improvements**: Enhanced README and package documentation
  - Updated README with additional information
  - Moved photos to dedicated photos folder for better organization
  - Improved package summary documentation

- 🔄 **CI/CD Improvements**: Consolidated and improved GitHub Actions workflows
  - Consolidated workflows into single CI/CD pipeline
  - Fixed GitHub Actions conditional syntax
  - Removed publish.yml workflow
  - Restored proven working workflow structure

- 🎯 **Resource Analyzer Enhancements**: Improved resource detection and analysis
  - Enhanced ResourceAnalyzer with better class checking
  - Improved ResourceModifier for better compatibility
  - Better translation file generation

- 📝 **Code Quality**: Multiple code style fixes
  - Fixed PHP code style issues across multiple files
  - Improved code formatting consistency
  - Enhanced LocalizationService and LocalizeFilamentCommand

### Bug Fixes

- 🔧 Fixed styling issues across multiple files
- ⚡ Improved command handling and error messages

## v1.0.1 - 2024-06-05

### Bug Fixes

- 🐛 Fixed issue where Pages weren't properly localized without using the --force flag
- 🔧 Improved Page detection with more robust class checking
- ✨ Always add title and navigation_label translations for Page classes
- ⚡️ Fixed LocalizationService to properly process all Page classes

## v1.0.0 - 2025-01-08

### Initial Release

- 🎉 First stable release
- ✨ Automatic scanning and localization of Filament resources
- 🌍 Multi-locale support (generate translations for multiple locales)
- 📦 Support for all Filament v4 components (40+ component types)
- 🎯 Panel-based, nested, and flat translation structures
- 📊 Comprehensive statistics tracking
- 🔄 Git integration for easy reverting
- 🧪 Full test coverage with Pest
- 📚 Complete documentation (README, Installation Guide, Quick Start, Package Summary)
- ⚙️ Configurable label generation strategies
- 🔒 Safety features (git status check, backup creation, dry-run mode)
- 🚀 Laravel 12 and Filament 4 support
- 💻 PHP 8.2, 8.3, and 8.4 compatibility

### Supported Components

- Forms: TextInput, Select, Checkbox, Toggle, Radio, Textarea, RichEditor, MarkdownEditor, DatePicker, DateTimePicker, TimePicker, ColorPicker, FileUpload, Repeater, Builder, KeyValue, TagsInput, Hidden
- Tables: TextColumn, IconColumn, ImageColumn, ColorColumn, BadgeColumn, BooleanColumn, SelectColumn, CheckboxColumn, ToggleColumn
- Infolists: TextEntry, IconEntry, ImageEntry, ColorEntry, BadgeEntry, BooleanEntry
- Actions: Action, BulkAction, HeaderAction, CreateAction, EditAction, DeleteAction, ViewAction
- Filters: SelectFilter, TernaryFilter, DateFilter
- Sections: Section, Fieldset, Card, Grid, Tabs, Wizard, Split

### Author

- **Momin Al Zaraa**
- Email: support@mominpert.com
- Website: https://mominpert.com
- GitHub: https://github.com/MominAlZaraa