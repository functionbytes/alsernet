const mix = require('laravel-mix');
const path = require('path');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

const moduleName = path.basename(__dirname);
const source = `modules/${moduleName}`;
const publicDist = `public/vendor/modules/${moduleName.toLowerCase()}`;

mix.setPublicPath('../../public').setResourceRoot('/');

// Compile SCSS to CSS
mix.sass(
    `${source}/resources/sass/cookie-consent.scss`,
    `${publicDist}/css/cookie-consent.css`
).options({
    processCssUrls: false
});

// Compile JavaScript
mix.js(
    `${source}/resources/js/cookie-consent.js`,
    `${publicDist}/js/cookie-consent.js`
).options({
    terser: {
        extractComments: false,
    }
});

// Copy compiled assets to module's public folder in production
if (mix.inProduction()) {
    mix.copy(
        `${publicDist}/css/cookie-consent.css`,
        `${source}/public/css/cookie-consent.css`
    ).copy(
        `${publicDist}/js/cookie-consent.js`,
        `${source}/public/js/cookie-consent.js`
    );

    mix.version();
} else {
    // Enable source maps in development
    mix.webpackConfig({
        devtool: 'inline-source-map'
    }).sourceMaps();
}
