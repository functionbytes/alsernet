/**
 * Lighthouse CI configuration — runs Google Lighthouse against the app and
 * fails the build if key scores drop below thresholds.
 *
 * Install:   npm install -g @lhci/cli
 * Run:       lhci autorun
 *
 * CI integration: add a step in .github/workflows/ci.yml (or equivalent):
 *     - name: Lighthouse CI
 *       run: npx --yes @lhci/cli@0.14.x autorun
 */

module.exports = {
    ci: {
        collect: {
            // URLs to audit (override via LHCI_URLS env var, comma-separated)
            url: (process.env.LHCI_URLS
                ? process.env.LHCI_URLS.split(',')
                : ['http://localhost:8000/']).map((s) => s.trim()),
            numberOfRuns: 3,
            startServerCommand: 'php artisan serve --port=8000',
            startServerReadyPattern: 'Server running',
            settings: {
                preset: 'desktop',
                onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'],
            },
        },
        assert: {
            assertions: {
                // Performance
                'categories:performance': ['warn', { minScore: 0.9 }],
                'largest-contentful-paint': ['warn', { maxNumericValue: 2500 }],
                'interaction-to-next-paint': ['warn', { maxNumericValue: 200 }],
                'cumulative-layout-shift': ['warn', { maxNumericValue: 0.1 }],
                'total-blocking-time': ['warn', { maxNumericValue: 200 }],
                'speed-index': ['warn', { maxNumericValue: 3400 }],

                // Accessibility (hard floor — ship-blocking)
                'categories:accessibility': ['error', { minScore: 0.9 }],
                'color-contrast': 'error',
                'image-alt': 'error',

                // Best Practices
                'categories:best-practices': ['warn', { minScore: 0.9 }],
                'uses-https': 'error',
                'no-vulnerable-libraries': 'warn',

                // SEO (must be high for a SEO module!)
                'categories:seo': ['error', { minScore: 0.95 }],
                'meta-description': 'error',
                'viewport': 'error',
                'document-title': 'error',
                'crawlable-anchors': 'error',
                'robots-txt': 'warn',
                'structured-data': 'off', // Lighthouse can't fully evaluate JSON-LD
            },
        },
        upload: {
            // Upload results to temporary public storage (useful in PRs).
            // For a permanent dashboard, see https://github.com/GoogleChrome/lighthouse-ci-action
            target: 'temporary-public-storage',
        },
    },
};
