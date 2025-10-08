#!/bin/bash

# Test Filament Localization Package in Fresh Laravel
# This script creates a fresh Laravel app and installs the package

set -e

echo "🚀 Testing Filament Localization Package"
echo "=========================================="
echo ""

# Check if test directory exists
if [ -d "/home/momin/Desktop/test/test-filament-localization" ]; then
    echo "⚠️  Test directory already exists. Removing..."
    rm -rf /home/momin/Desktop/test/test-filament-localization
fi

# Create test directory
mkdir -p /home/momin/Desktop/test
cd /home/momin/Desktop/test

echo "📦 Creating fresh Laravel application..."
composer create-project laravel/laravel test-filament-localization --quiet

cd test-filament-localization

echo ""
echo "🔗 Adding package repository..."
composer config repositories.filament-localization vcs https://github.com/MominAlZaraa/filament-localization

echo ""
echo "📥 Installing Filament Localization package..."
composer require mominalzaraa/filament-localization:dev-main --no-interaction

echo ""
echo "📥 Installing Filament..."
composer require filament/filament --no-interaction

echo ""
echo "⚙️  Installing Filament Localization..."
php artisan filament:install --panels --no-interaction

echo ""
echo "📝 Publishing configuration..."
php artisan vendor:publish --tag="filament-localization-config" --force

echo ""
echo "✅ Package installed successfully!"
echo ""
echo "📂 Test application location:"
echo "   /home/momin/Desktop/test/test-filament-localization"
echo ""
echo "🧪 To test the package, run:"
echo "   cd /home/momin/Desktop/test/test-filament-localization"
echo "   php artisan filament:localize --help"
echo ""
echo "🗑️  To clean up, run:"
echo "   rm -rf /home/momin/Desktop/test/test-filament-localization"
echo ""
echo "Done! 🎉"
