# Plugin Banner Creation Guide

## Requirements

- **Aspect Ratio**: 16:9
- **Minimum Size**: 2560x1440 pixels
- **Format**: JPEG (preferred)
- **File Name**: `plugin-banner.jpg`
- **Location**: `.github/plugin-banner.jpg`

## Design Recommendations

### Content to Include:
1. **Package Name**: "Filament Localization"
2. **Tagline**: "Automate Your Translation Workflow"
3. **Key Feature Visual**: Show command output or translation file structure
4. **Brand Colors**: Use Filament's orange/amber colors
5. **Professional Look**: Clean, modern design

### What to Avoid:
- ❌ Full panel screenshots
- ❌ Cluttered UI elements
- ❌ Low-resolution images
- ❌ Too much text
- ❌ Distracting backgrounds

## Banner Ideas

### Option 1: Command Output
Show terminal with:
```bash
php artisan filament:localize --panel=admin

📦 Processing panel: admin
 15/15 [============================] 100%

✅ Statistics:
- Panels processed: 1
- Resources processed: 15
- Translation files created: 30
- Translation keys added: 450
```

### Option 2: Before/After Comparison
Split screen showing:
- **Left**: Resource file with hardcoded strings
- **Right**: Resource file with translation keys

### Option 3: Translation Files Structure
Show IDE file tree with generated translation files:
```
lang/
├── en/filament/admin/
│   ├── user_resource.php
│   ├── post_resource.php
│   └── ...
└── el/filament/admin/
    ├── user_resource.php
    ├── post_resource.php
    └── ...
```

## Tools for Creation

1. **Figma** (Recommended)
   - Professional design tool
   - Free tier available
   - Export to JPEG at 2560x1440

2. **Canva**
   - Easy to use
   - Templates available
   - Custom dimensions supported

3. **Beyond Code Banner Generator**
   - URL: https://banners.beyondco.de
   - Quick and easy
   - Filament-style banners

4. **Photoshop/GIMP**
   - Full control
   - Professional results

## Example Banner Layout

```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│  [Logo]  FILAMENT LOCALIZATION                         │
│                                                         │
│          Automate Your Translation Workflow            │
│                                                         │
│  ┌─────────────────────────────────────────────────┐  │
│  │ $ php artisan filament:localize                 │  │
│  │                                                 │  │
│  │ ✅ 15 resources processed                       │  │
│  │ ✅ 450 translation keys added                   │  │
│  │ ✅ 30 translation files created                 │  │
│  └─────────────────────────────────────────────────┘  │
│                                                         │
│  🌍 Multi-locale  📦 40+ Components  ⚡ Automated      │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

## Color Palette

Use Filament's brand colors:
- **Primary**: `#F59E0B` (Amber 500)
- **Secondary**: `#0EA5E9` (Sky 500)
- **Background**: `#1F2937` (Gray 800) or `#FFFFFF` (White)
- **Text**: `#F9FAFB` (Gray 50) or `#111827` (Gray 900)

## Upload Instructions

1. Create the banner (2560x1440px JPEG)
2. Save as `plugin-banner.jpg`
3. Place in `.github/` directory
4. Commit and push to GitHub
5. Update `PLUGIN_INFO.json` with correct URL:
   ```json
   "image": "https://raw.githubusercontent.com/MominAlZaraa/filament-localization/main/.github/plugin-banner.jpg"
   ```

## Quick Start with Beyond Code

1. Visit: https://banners.beyondco.de
2. Enter title: "Filament Localization"
3. Enter subtitle: "Automate Your Translation Workflow"
4. Choose Filament theme
5. Download as 2560x1440 JPEG
6. Upload to `.github/plugin-banner.jpg`

---

**Need Help?** Contact support@mominpert.com
