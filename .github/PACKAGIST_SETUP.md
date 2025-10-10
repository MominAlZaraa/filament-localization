# Packagist Publishing Setup Guide

## Overview

This guide will help you set up automated publishing of your Filament Localization package to Packagist using GitHub Actions.

## Prerequisites

1. **Packagist Account**: Create an account at [packagist.org](https://packagist.org)
2. **GitHub Repository**: Your package should be in a public GitHub repository
3. **Package Submitted**: Your package should be submitted to Packagist first

## Step 1: Submit Package to Packagist

### Initial Submission

1. **Visit Packagist**: Go to [packagist.org](https://packagist.org)
2. **Login**: Use your GitHub account to login
3. **Submit Package**: Click "Submit" and enter your repository URL:
   ```
   https://github.com/MominAlZaraa/filament-localization
   ```
4. **Verify Details**: Confirm package name `mominalzaraa/filament-localization`
5. **Submit**: Click "Submit" to create the package

### Package Information

- **Package Name**: `mominalzaraa/filament-localization`
- **Repository**: `https://github.com/MominAlZaraa/filament-localization`
- **Description**: Automatically scan and localize Filament resources with structured translation files
- **License**: MIT
- **Keywords**: localization, translation, i18n, multilingual, automation, developer-tools

## Step 2: Set Up Packagist Webhook

### Enable GitHub Hook

1. **Go to Package Page**: Visit your package on Packagist
2. **Settings**: Click on "Settings" tab
3. **GitHub Hook**: Click "GitHub Hook" button
4. **Authorize**: Grant Packagist access to your GitHub repositories
5. **Select Repository**: Choose `MominAlZaraa/filament-localization`
6. **Activate**: Click "Activate Hook"

This allows Packagist to automatically detect new releases.

## Step 3: Get Packagist API Token

### Create API Token

1. **Login to Packagist**: Go to [packagist.org](https://packagist.org)
2. **Profile**: Click on your username in the top right
3. **API Tokens**: Click "API Tokens" in the sidebar
4. **Generate Token**: Click "Generate Token"
5. **Name**: Enter "GitHub Actions" or similar
6. **Copy Token**: Copy the generated token (you won't see it again)

### Required Information

- **Username**: Your Packagist username (usually `MominAlZaraa`)
- **API Token**: The token you just generated

## Step 4: Add GitHub Secrets

### Add Repository Secrets

1. **Go to Repository**: Visit [github.com/MominAlZaraa/filament-localization](https://github.com/MominAlZaraa/filament-localization)
2. **Settings**: Click "Settings" tab
3. **Secrets and Variables**: Click "Secrets and variables" → "Actions"
4. **New Repository Secret**: Click "New repository secret"

### Add These Secrets

#### Secret 1: PACKAGIST_USERNAME
- **Name**: `PACKAGIST_USERNAME`
- **Value**: Your Packagist username (e.g., `MominAlZaraa`)

#### Secret 2: PACKAGIST_TOKEN
- **Name**: `PACKAGIST_TOKEN`
- **Value**: Your Packagist API token

## Step 5: Test the Workflow

### Create a Test Release

1. **Update Version**: In `composer.json`, update version to `1.0.1`
2. **Commit Changes**: 
   ```bash
   git add composer.json
   git commit -m "chore: bump version to 1.0.1"
   git push origin main
   ```
3. **Create Tag**:
   ```bash
   git tag v1.0.1
   git push origin v1.0.1
   ```
4. **Check Actions**: Go to "Actions" tab in GitHub to see the workflow run
5. **Check Packagist**: Visit your package on Packagist to see the new version

## Step 6: Verify Publishing

### Check Package on Packagist

1. **Visit Package**: Go to [packagist.org/packages/mominalzaraa/filament-localization](https://packagist.org/packages/mominalzaraa/filament-localization)
2. **Verify Version**: Check that version 1.0.1 appears
3. **Check Downloads**: Verify download statistics are updating

### Test Installation

```bash
# Test installation from Packagist
composer create-project laravel/laravel test-project
cd test-project
composer require mominalzaraa/filament-localization
```

## Workflow Details

### GitHub Actions Workflow

The workflow (`.github/workflows/publish.yml`) triggers on version tags:

```yaml
on:
  push:
    tags:
      - 'v*.*.*'
```

### What It Does

1. **Checkout Code**: Gets the latest code
2. **Setup PHP**: Installs PHP 8.2 with required extensions
3. **Install Dependencies**: Runs `composer install --no-dev`
4. **Validate**: Checks `composer.json` is valid
5. **Publish**: Notifies Packagist of the new release

## Future Releases

### Creating New Releases

1. **Update Version**: Change version in `composer.json`
2. **Update CHANGELOG**: Add new version to `CHANGELOG.md`
3. **Commit**: Commit the changes
4. **Tag**: Create a new version tag
5. **Push**: Push the tag to trigger publishing

### Example Release Process

```bash
# 1. Update version in composer.json to "1.0.2"
# 2. Update CHANGELOG.md
git add .
git commit -m "chore: prepare release v1.0.2"
git tag v1.0.2
git push origin main
git push origin v1.0.2
```

## Troubleshooting

### Common Issues

#### Workflow Fails
- **Check Secrets**: Ensure `PACKAGIST_USERNAME` and `PACKAGIST_TOKEN` are set
- **Check Token**: Verify the API token is valid and not expired
- **Check Permissions**: Ensure the token has write access to your package

#### Package Not Updating
- **Check Webhook**: Verify the GitHub hook is active on Packagist
- **Check Repository**: Ensure the repository URL is correct
- **Manual Update**: Try manually updating the package on Packagist

#### Installation Issues
- **Check Version**: Ensure the version exists on Packagist
- **Check Dependencies**: Verify all dependencies are available
- **Clear Cache**: Try `composer clear-cache`

### Getting Help

- **Packagist Support**: [packagist.org/about](https://packagist.org/about)
- **GitHub Actions**: [docs.github.com/actions](https://docs.github.com/actions)
- **Composer**: [getcomposer.org/doc](https://getcomposer.org/doc)

## Security Notes

### API Token Security

- **Never Commit**: Never commit API tokens to your repository
- **Use Secrets**: Always use GitHub Secrets for sensitive data
- **Rotate Tokens**: Regularly rotate your API tokens
- **Limited Scope**: Use tokens with minimal required permissions

### Repository Security

- **Public Repository**: Packagist requires public repositories
- **No Secrets**: Never commit secrets or sensitive data
- **Clean History**: Keep your Git history clean

## Success Checklist

- [ ] Package submitted to Packagist
- [ ] GitHub webhook activated
- [ ] API token generated
- [ ] GitHub secrets added
- [ ] Test release created
- [ ] Workflow runs successfully
- [ ] Package appears on Packagist
- [ ] Installation works from Packagist

## Next Steps

After successful setup:

1. **Create Release**: Tag your first official release
2. **Update Documentation**: Add Packagist installation instructions
3. **Monitor**: Watch for any issues with the workflow
4. **Maintain**: Keep the package updated and secure

---

**Package**: mominalzaraa/filament-localization  
**Repository**: https://github.com/MominAlZaraa/filament-localization  
**Packagist**: https://packagist.org/packages/mominalzaraa/filament-localization

For support, contact: support@mominpert.com
