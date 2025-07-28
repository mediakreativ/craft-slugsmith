# Slugsmith Plugin for Craft CMS

**Slugsmith brings advanced slug control to Craft CMS.** Take full control of your slugs with powerful customization, intuitive tools, and site-specific settings.

Looking for a way to transliterate your titles into clean, URL-friendly slugs - like turning `¿Cómo estás?` into `como-estas` - or `Rødgrød med fløde` into `rodgrod-med-flode`?

You don't have to look any further.

Just add this line to your `config/general.php`:

```php
->limitAutoSlugsToAscii(true)
```

That's it. **You don't need this plugin for that**.

**But if you want more** - like refresh buttons, per-site custom replacement rules, per-site transliteration control, or automatic hashtag conversion - Slugsmith is here to help.

## Key Features

- **Slug Refresh Button**  
  Regenerate slugs based on the current title with a single click - directly in the entry editor.

- **Custom Slug Rules**  
  Define your own replacement rules (per site) to handle special characters, brand names, abbreviations, and more - all applied before Craft's default slugification.

- **Per-Site ASCII Transliteration Control**  
  Override Craft's global `limitAutoSlugsToAscii` setting with fine-grained toggles per site.

- **Hashtag Conversion**  
  Automatically convert hashtags like `#WeLoveCraft` into readable slugs like `hashtag-we-love-craft`.

## Language support

Slugsmith is localized in:  
**Arabic, Chinese, Czech, Danish, Dutch, English, French, German, Greek, Hebrew, Italian, Japanese, Korean, Polish, Portuguese, Russian, Spanish, Swedish, Turkish, Ukrainian.** Need another language? Feel free to [contact us](mailto:plugins@mediakreativ.de) or [submit a feature request](https://github.com/mediakreativ/craft-slugsmith/issues).

## Requirements

- **Craft CMS**: 5.0.0+
- **PHP**: 8.2+

## Installation

Install Slugsmith via the [Craft Plugin Store](https://plugins.craftcms.com/slugsmith)  
or using Composer:

```bash
composer require mediakreativ/craft-slugsmith
./craft plugin/install slugsmith
```

## Feedback & Support

Your feedback helps us improve! For feature requests or bug reports, please submit an [issue on GitHub](https://github.com/mediakreativ/craft-slugsmith/issues). You can also reach us directly via email at [plugins@mediakreativ.de](mailto:plugins@mediakreativ.de).
