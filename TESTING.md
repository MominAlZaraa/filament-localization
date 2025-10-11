# Testing Guide

## Environment Setup

### DeepL API Key

For testing and development, you need to set up a DeepL API key:

1. **Get a DeepL API Key:**
   - Visit [DeepL API](https://www.deepl.com/pro-api)
   - Sign up for a free account
   - Get your API key from the dashboard

2. **Set the API Key:**
   
   **For Local Development:**
   ```bash
   # Add to your .env file
   DEEPL_API_KEY=your-deepl-api-key-here
   ```

   **For GitHub Actions:**
   - Go to your repository Settings > Secrets and variables > Actions
   - Add a new secret named `DEEPL_API_KEY`
   - Set the value to your DeepL API key

3. **Test Configuration:**
   ```bash
   # Run tests
   composer test
   
   # Run static analysis
   composer analyse
   ```

## Test Structure

- **Feature Tests**: Integration tests for commands and services
- **Unit Tests**: Individual component tests
- **Larastan Analysis**: Static analysis with Laravel-specific rules

## Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run static analysis
composer analyse

# Format code
composer format
```

## GitHub Actions

The workflows are configured to:
- Run tests on PHP 8.2, 8.3, 8.4
- Test with Laravel 12.*
- Use Larastan for static analysis
- Handle missing DeepL API key gracefully
- Fix code style automatically
