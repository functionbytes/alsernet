# Changelog

All notable changes to the Sitemap module will be documented in this file.

## [1.0.0] - 2026-02-08

### Added
- Initial release of Sitemap module
- `SitemapBuilder` class for building sitemaps
- `HasSitemapItems` trait for models
- `GenerateSitemapCommand` for manual generation
- `PingSitemapCommand` to notify search engines
- `SitemapController` with multiple endpoints
- `SitemapHelper` with utility functions
- `Sitemap` facade for easy access
- `CacheSitemapResponse` middleware
- Automatic daily sitemap generation via scheduler
- Support for multiple models
- Configurable cache system (24h default)
- XML and Index sitemap templates
- Comprehensive documentation:
  - README.md
  - INSTALLATION.md
  - EXAMPLES.md
  - QUICKSTART.md
  - STRUCTURE.md
- Feature tests
- Support for:
  - Custom URLs per model
  - Custom priorities (0.0 - 1.0)
  - Custom change frequencies
  - Custom filters for sitemap items
  - Multiple sitemap formats
  - Sitemap index for large sites

### Routes
- `GET /sitemap.xml` - Main sitemap
- `GET /sitemap-pages.xml` - Pages sitemap
- `GET /sitemap-posts.xml` - Posts sitemap
- `GET /sitemap-index.xml` - Sitemap index

### Commands
- `php artisan sitemap:generate` - Generate sitemap
- `php artisan sitemap:ping` - Ping search engines

### Configuration
- Cache enabled/disabled
- Cache duration (default: 24h)
- Max items per sitemap (default: 50,000)
- Models to include in sitemap

### Features
- Automatic XML escaping
- Valid W3C sitemap format
- Google and Bing compatible
- Support for lastmod, changefreq, priority
- Automatic date formatting
- Memory efficient for large sites
- Easy integration with existing models
- No database required

## [Unreleased]

### Planned
- Support for video sitemaps
- Support for image sitemaps
- Support for news sitemaps
- Multi-language support
- Automatic sitemap compression (gzip)
- Admin panel integration
- Visual sitemap generator
- Analytics integration
- Custom ping endpoints
- Rate limiting for sitemap generation
- Queue support for large sitemaps
- S3/Cloud storage support
- Sitemap validation
- XML schema validation
- Broken link detection

---

## Version Schema

Format: [MAJOR.MINOR.PATCH]

- **MAJOR**: Incompatible API changes
- **MINOR**: Backwards-compatible functionality
- **PATCH**: Backwards-compatible bug fixes

---

## Links

- [Documentation](README.md)
- [Installation Guide](INSTALLATION.md)
- [Examples](EXAMPLES.md)
- [Quick Start](QUICKSTART.md)
