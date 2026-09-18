// Minificado OPCIONAL de assets de módulos satélite que, a diferencia de
// tickets-app.js (ver build-tickets-app.mjs), NO están partidos en varios
// ficheros ni necesitan concatenarse: cada entrada de aquí es un único
// <script>/<link> IIFE-autocontenido que vive en su propia vista/modal y
// nunca coexiste con otro en la misma página, así que basta con
// minificarlo tal cual — mismo criterio que ticket-detail.js (retirado el
// 8-sep-2026 junto con la ficha completa que lo usaba, pero el patrón que
// dejó es el que sigue este script).
//
// Mismo contrato "opcional, cae si está desactualizado" que
// build-tickets-app.mjs: el .min.* solo se sirve si existe Y es más
// reciente que su única fuente (ver el @if en cada vista blade); en
// desarrollo se sigue editando y probando el fichero sin minificar.
//
// Uso: node scripts/build-module-assets.mjs
// (o `npm run build:module-assets`)

import { readFile, writeFile, mkdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as esbuild from 'esbuild';

const root = path.dirname(fileURLToPath(import.meta.url)) + '/..';

const TARGETS = [
    {
        loader: 'js',
        src: 'modules/HelpdeskDocument/public/js/document-panel.js',
        out: 'modules/HelpdeskDocument/public/js/document-panel.min.js',
        published: 'public/modules/helpdeskdocument/js/document-panel.min.js',
    },
    {
        loader: 'js',
        src: 'modules/HelpdeskPrestashop/public/js/cart-build.js',
        out: 'modules/HelpdeskPrestashop/public/js/cart-build.min.js',
        published: 'public/modules/helpdeskprestashop/js/cart-build.min.js',
    },
    {
        loader: 'js',
        src: 'modules/HelpdeskPrestashop/public/js/order-workspace.js',
        out: 'modules/HelpdeskPrestashop/public/js/order-workspace.min.js',
        published: 'public/modules/helpdeskprestashop/js/order-workspace.min.js',
    },
    {
        loader: 'js',
        src: 'modules/HelpdeskPrestashop/public/js/product-recommend.js',
        out: 'modules/HelpdeskPrestashop/public/js/product-recommend.min.js',
        published: 'public/modules/helpdeskprestashop/js/product-recommend.min.js',
    },
    {
        loader: 'css',
        src: 'modules/HelpdeskPrestashop/public/css/prestashop-inbox.css',
        out: 'modules/HelpdeskPrestashop/public/css/prestashop-inbox.min.css',
        published: 'public/modules/helpdeskprestashop/css/prestashop-inbox.min.css',
    },
];

const kb = (n) => (n / 1024).toFixed(1) + ' KB';

async function buildTarget(target) {
    const srcPath = path.join(root, target.src);
    const outPath = path.join(root, target.out);
    const publishedPath = path.join(root, target.published);

    const code = await readFile(srcPath, 'utf8');

    const result = await esbuild.transform(code, {
        loader: target.loader,
        minify: true,
        target: target.loader === 'js' ? 'es2019' : undefined,
        legalComments: 'none',
    });

    if (result.warnings.length) {
        for (const w of result.warnings) console.warn(`[esbuild ${target.src}]`, w.text, w.location);
    }

    await mkdir(path.dirname(outPath), { recursive: true });
    await writeFile(outPath, result.code, 'utf8');
    await mkdir(path.dirname(publishedPath), { recursive: true });
    await writeFile(publishedPath, result.code, 'utf8');

    console.log(`${path.basename(target.out)} generado: ${kb(code.length)} → ${kb(result.code.length)} minificado`);
    console.log(`  fuente:     ${path.relative(root, outPath)}`);
    console.log(`  publicado:  ${path.relative(root, publishedPath)}`);
}

async function main() {
    for (const target of TARGETS) {
        await buildTarget(target);
    }
}

main().catch((err) => {
    console.error(err.message || err);
    process.exit(1);
});
