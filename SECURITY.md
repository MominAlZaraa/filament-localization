# Security Policy

## Supported versions

Security fixes are applied to the latest release line of this package. Please upgrade to the newest `mominalzaraa/filament-localization` version before reporting an issue against an older tag.

| Version | Supported |
| ------- | --------- |
| 2.x     | ✅        |
| 1.x     | ❌ (Filament v4 line; upgrade to 2.x when possible) |
| < 1.0   | ❌        |

## Reporting a vulnerability

Please **do not** open a public GitHub issue for security vulnerabilities.

Report privately by emailing **[support@mominpert.com](mailto:support@mominpert.com)** with:

- A clear description of the issue and impact
- Steps to reproduce (or a minimal proof of concept)
- Affected package version(s) and your Laravel / Filament versions
- Any suggested remediation, if you have one

You can also use [GitHub private vulnerability reporting](https://github.com/MominAlZaraa/filament-localization/security/advisories/new) if it is enabled on this repository.

## What to expect

- We aim to acknowledge reports within **72 hours**
- We will keep you informed while we investigate and prepare a fix
- Once a fix is available, we will coordinate disclosure and credit you if you wish

## Scope

This package automates Filament localization (scanning resources, generating translation keys/files, optional DeepL translation). Reports are especially welcome for issues that could:

- Exfiltrate or overwrite unexpected files outside intended localization paths
- Execute unintended commands via git/integration hooks
- Leak API credentials (for example DeepL keys) through logs or generated artifacts
- Allow privilege escalation when the package runs in a Filament / Laravel application

Out of scope: vulnerabilities solely in upstream dependencies (Laravel, Filament, DeepL SDK) — please report those to the respective maintainers, unless our usage of them introduces a distinct risk.

## Safe disclosure

Thank you for helping keep Filament Localization and its users safe. Responsible, private disclosure is appreciated and will be treated with priority.
