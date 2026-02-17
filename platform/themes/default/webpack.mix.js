const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 | Template: Default
 */

mix
    // SASS/SCSS compilation
    .sass('assets/sass/style.scss', 'public/css/style.css')
    .sass('assets/sass/rtl.scss', 'public/css/rtl.css')

    // Copy vendors
    .copy('assets/vendors', 'public/vendors')
    .copy('assets/fonts', 'public/fonts')
    .copy('assets/images', 'public/images')

    // JavaScript
    .js('assets/js/main.js', 'public/js/main.js')
    .js('assets/js/backend.js', 'public/js/backend.js')

    // Source maps
    .sourceMaps()

    // Version for cache busting
    .version();

// Production
if (mix.inProduction()) {
    mix.minify('public/css/style.css');
    mix.minify('public/js/main.js');
    mix.minify('public/js/backend.js');
}
