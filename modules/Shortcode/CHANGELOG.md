# Changelog

All notable changes to the Shortcode module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-08

### Added
- Initial release of Shortcode module
- ShortcodeCompiler with full parsing and compilation
- Shortcode Facade for easy access
- Helper functions: `shortcode()`, `strip_shortcodes()`, `register_shortcode()`, `has_shortcode()`, `all_shortcodes()`
- Blade directives: `@shortcode`, `@stripshortcodes`
- Configuration file with caching and performance options
- Default shortcodes:
  - `[button]` - Styled buttons with links
  - `[alert]` - Bootstrap alert messages
  - `[columns]` and `[column]` - Responsive grid layouts
  - `[youtube]` - Embedded YouTube videos
  - `[image]` - Media module integration
  - `[icon]` - Bootstrap Icons
  - `[badge]` - Bootstrap badges
  - `[card]` - Bootstrap cards
  - `[accordion]` and `[accordion-item]` - Accordion components
  - `[quote]` - Blockquotes with attribution
- Support for self-closing shortcodes (`[shortcode /]`)
- Support for nested shortcodes
- Built-in caching mechanism for compiled shortcodes
- Comprehensive documentation (README.md)
- Usage examples (EXAMPLES.md)
- Error handling and logging
- Security features (attribute escaping)

### Features
- Regex-based shortcode parsing
- Attribute parsing with key-value pairs
- Content enclosure support
- Cache management (clear, duration control)
- Shortcode registration and unregistration
- Check shortcode existence
- List all registered shortcodes
- Strip shortcodes from content
- Bootstrap 5 compatible output
- Integration with Media module for image shortcodes

### Configuration Options
- Enable/disable shortcode processing globally
- Cache control (enable/disable, duration)
- Auto-register default shortcodes
- Selective shortcode enabling
- Error handling modes
- Maximum nesting level

### Developer Features
- Easy shortcode registration via callback
- Facade support
- Helper functions
- Blade directive integration
- Service Provider with extensible boot method
- Clear API for custom shortcodes

## [Unreleased]

### Planned Features
- Visual shortcode builder
- Shortcode preview in admin
- Import/export shortcode templates
- Shortcode validation
- More default shortcodes (video, audio, gallery, etc.)
- Shortcode documentation generator
- Performance optimization
- Unit tests
- Integration tests

---

## Version History

### Version 1.0.0
First stable release with core functionality and 11 default shortcodes.
