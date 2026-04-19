const mix = require('laravel-mix');
const path = require('path');

const directory = path.basename(path.resolve(__dirname));
const source = 'platform/themes/' + directory;
const dest = source + '/public';

/*
 * Los archivos vendor (bootstrap, swiper, jquery, etc.) ya están en dest/css y dest/js.
 * Solo compilamos los assets propios del tema: SASS y JS personalizados.
 */
mix
    // ── Compilar SASS ──────────────────────────────────────────────────────
    .sass(source + '/assets/sass/style.scss', dest + '/css')
    .sass(source + '/assets/sass/rtl.scss',   dest + '/css')

    // ── Compilar JS personalizado ──────────────────────────────────────────
    .js(source + '/assets/js/main.js',    dest + '/js')
    .js(source + '/assets/js/backend.js', dest + '/js')

    // ── Opciones de Mix ───────────────────────────────────────────────────
    .options({
        processCssUrls: false,
    });

if (!mix.inProduction()) {
    mix.sourceMaps();
}
