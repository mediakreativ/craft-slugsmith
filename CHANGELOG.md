## [1.0.1] - 2025-11-14

### Fixed
- Fixed Twig syntax error in config warning macro (changed `|bt` to `|t` filter with proper translation category)
- Fixed "Convert hashtags to slugs" setting not being saved when disabled in control panel

### Credits
- Thanks to [@redburn](https://github.com/redburn) for reporting and fixing the Twig syntax error!

## [1.0.0] - 2025-07-28

### Initial release

- Slug refresh button for the slug field in the entry editor.
- Custom slug rules (per site) to define search/replace patterns before slugification.
- Per-site control over ASCII transliteration (overrides Craft's global setting).
- Automatic conversion of hashtags (e.g. `#WeLoveCraft`) into readable slugs (e.g. `hashtag-we-love-craft`).