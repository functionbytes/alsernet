// Bundle + minificado OPCIONAL de modules/HelpdeskTickets/public/js/tickets-app/
// (núcleo + un fichero por modal, partido el 8-sep-2026 de un único archivo de
// 11.304 líneas — ver 2c12c6f8b). No sustituye el flujo de trabajo normal del
// módulo: en desarrollo se sigue editando y probando fichero a fichero, y el
// blade cae solo a los <script> sueltos si este bundle no existe o queda más
// viejo que cualquiera de los fuentes que agrupa (ver index.blade.php). Este
// script es para generar tickets-app.min.js justo antes de publicar a
// producción, cuando ya no se va a tocar ningún modal en esa tanda.
//
// Cada fichero fuente es un <script> clásico de nivel superior (sin IIFE ni
// import/export): TKA, openModal(), escapeHtml()… cuelgan de `window` porque
// el navegador comparte el scope global entre <script> sueltos cargados en
// orden. Por eso aquí NO se usa esbuild en modo --bundle (trataría cada
// fichero como un módulo aislado y rompería justo esa suposición): se
// CONCATENAN en el orden del manifest —core siempre primero— y se minifica
// el resultado como un único script clásico, preservando la semántica
// exacta de los <script> sueltos que sustituye.
//
// Carga bajo demanda: los modales listados en manifest.json → "lazy" NO van en
// tickets-app.min.js. Cada uno se minifica aparte en js/tickets-app-lazy/ y se
// descarga la primera vez que se abre (ver scripts/tickets-app-lazy-loader.js,
// cuyo código se añade al final del bundle). Los que comparten variables o
// helpers con core u otros modales se quedan en el bundle inicial.
//
// Uso: node scripts/build-tickets-app.mjs
// (o `npm run build:tickets-app`)

import { readFile, writeFile, mkdir, rm } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as esbuild from 'esbuild';

const root = path.dirname(fileURLToPath(import.meta.url)) + '/..';
const srcDir = path.join(root, 'modules/HelpdeskTickets/public/js/tickets-app');
const manifestPath = path.join(srcDir, 'manifest.json');
const outDir = path.join(root, 'modules/HelpdeskTickets/public/js');
const outPath = path.join(outDir, 'tickets-app.min.js');
const publishedDir = path.join(root, 'public/modules/helpdesktickets/js');
const publishedPath = path.join(publishedDir, 'tickets-app.min.js');
const lazyLoaderPath = path.join(root, 'scripts/tickets-app-lazy-loader.js');
const lazyOutDir = path.join(outDir, 'tickets-app-lazy');
const lazyPublishedDir = path.join(publishedDir, 'tickets-app-lazy');

// El CSS del módulo es un único archivo (no dividido como el JS): solo hace
// falta minificarlo, sin concatenar nada. Mismo criterio de "opcional, cae
// solo si está actualizado" que el JS — ver index.blade.php.
const cssSrcPath = path.join(root, 'modules/HelpdeskTickets/public/css/tickets-app.css');
const cssOutPath = path.join(root, 'modules/HelpdeskTickets/public/css/tickets-app.min.css');
const cssPublishedDir = path.join(root, 'public/modules/helpdesktickets/css');
const cssPublishedPath = path.join(cssPublishedDir, 'tickets-app.min.css');

const kb = (n) => (n / 1024).toFixed(1) + ' KB';

async function buildCss() {
    const css = await readFile(cssSrcPath, 'utf8');

    const result = await esbuild.transform(css, {
        loader: 'css',
        minify: true,
        legalComments: 'none',
    });

    if (result.warnings.length) {
        for (const w of result.warnings) console.warn('[esbuild css]', w.text, w.location);
    }

    await writeFile(cssOutPath, result.code, 'utf8');
    await mkdir(cssPublishedDir, { recursive: true });
    await writeFile(cssPublishedPath, result.code, 'utf8');

    console.log(`tickets-app.min.css generado: ${kb(css.length)} → ${kb(result.code.length)} minificado`);
    console.log(`  fuente:     ${path.relative(root, cssOutPath)}`);
    console.log(`  publicado:  ${path.relative(root, cssPublishedPath)}`);
}

// Un modal 'lazy' solo puede exponer hacia fuera funciones listadas en el mapa
// (el cargador les instala un stub) y su valor de retorno no debe usarse: si
// otro fichero llama a un símbolo suyo que NO está en el mapa, en el bundle esa
// llamada daría ReferenceError al primer clic. Se detecta aquí, en el build,
// en vez de en producción.
function validateLazy(files, lazy, sources) {
    for (const [file, names] of Object.entries(lazy)) {
        if (file === 'core') throw new Error("'core' no puede ser 'lazy'");
        if (!files.includes(file)) throw new Error(`manifest.json: lazy '${file}' no está en "files"`);
        if (!Array.isArray(names) || names.length === 0) throw new Error(`manifest.json: lazy '${file}' sin funciones expuestas`);

        const declared = [...sources[file].matchAll(/^ {4}(?:async\s+)?function\s*\*?\s*([A-Za-z_$][\w$]*)\s*\(/gm)].map((m) => m[1]);
        const topVars = [...sources[file].matchAll(/^ {4}(?:var|let|const)\s+([A-Za-z_$][\w$]*)/gm)].map((m) => m[1]);

        for (const name of names) {
            if (!declared.includes(name)) {
                throw new Error(`manifest.json: lazy '${file}' dice exponer ${name}(), pero no la declara como función de nivel superior`);
            }
        }

        for (const name of [...declared, ...topVars]) {
            if (names.includes(name)) continue;
            const re = new RegExp(`(?<![\\w$.])${name.replace(/\$/g, '\\$')}(?![\\w$])`);
            for (const other of files) {
                if (other === file) continue;
                const code = sources[other].split('\n').filter((l) => !/^\s*(\/\/|\*|\/\*)/.test(l)).join('\n');
                if (re.test(code)) {
                    throw new Error(`'${other}' usa '${name}', declarado en el modal 'lazy' '${file}': añádelo a lazy['${file}'] (solo si es una función cuyo retorno no se usa) o saca ese modal de "lazy"`);
                }
            }
        }
    }
}

async function main() {
    const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
    const files = manifest.files;

    if (!Array.isArray(files) || files.length === 0) {
        throw new Error(`manifest.json sin "files" en ${manifestPath}`);
    }
    if (files[0] !== 'core') {
        // No es una regla de esbuild: es que TKA se define en core.js y todo
        // lo demás lo lee al ejecutarse. Con otro orden el bundle cargaría
        // bien pero fallaría en tiempo de ejecución al primer clic.
        throw new Error(`'core' debe ir primero en manifest.json (va: '${files[0]}')`);
    }

    const lazy = manifest.lazy && typeof manifest.lazy === 'object' ? manifest.lazy : {};
    const lazyFiles = Object.keys(lazy);

    const sources = {};
    for (const file of files) {
        const filePath = path.join(srcDir, `${file}.js`);
        if (!existsSync(filePath)) {
            throw new Error(`manifest.json referencia '${file}', pero no existe ${filePath}`);
        }
        sources[file] = await readFile(filePath, 'utf8');
    }

    validateLazy(files, lazy, sources);

    const minify = (code) => esbuild.transform(code, {
        loader: 'js',
        minify: true,
        target: 'es2019',
        // legalComments 'none': los comentarios en español de este módulo son
        // documentación para quien edita el FUENTE, no para el bundle
        // servido — igual que hace Vite con el resto de assets del proyecto.
        legalComments: 'none',
    });

    // Los ficheros 'lazy' se escriben ANTES que el bundle: index.blade.php
    // compara el mtime de cada fuente con el de su fichero generado.
    await rm(lazyOutDir, { recursive: true, force: true });
    await rm(lazyPublishedDir, { recursive: true, force: true });
    await mkdir(lazyOutDir, { recursive: true });
    await mkdir(lazyPublishedDir, { recursive: true });

    let lazyMin = 0;
    for (const file of lazyFiles) {
        const result = await minify(sources[file]);
        for (const w of result.warnings) console.warn(`[esbuild ${file}]`, w.text, w.location);
        await writeFile(path.join(lazyOutDir, `${file}.min.js`), result.code, 'utf8');
        await writeFile(path.join(lazyPublishedDir, `${file}.min.js`), result.code, 'utf8');
        lazyMin += result.code.length;
    }

    const chunks = [];
    for (const file of files) {
        if (file in lazy) continue;
        // Separador con salto de línea real: dos ficheros pegados sin él
        // pueden fusionar un `}` de cierre con la siguiente sentencia (ASI).
        chunks.push(`// ---- ${file}.js ----\n${sources[file]}`);
    }

    if (lazyFiles.length > 0) {
        const loader = (await readFile(lazyLoaderPath, 'utf8')).split('__LAZY_MAP__').join(JSON.stringify(lazy));
        if (loader.includes('__LAZY_MAP__')) throw new Error('el cargador lazy conserva el marcador __LAZY_MAP__ sin sustituir');
        chunks.push(`// ---- lazy loader ----\n${loader}`);
    }

    const concatenated = chunks.join('\n\n');
    const result = await minify(concatenated);

    if (result.warnings.length) {
        for (const w of result.warnings) console.warn('[esbuild]', w.text, w.location);
    }

    await mkdir(outDir, { recursive: true });
    await writeFile(outPath, result.code, 'utf8');

    await mkdir(publishedDir, { recursive: true });
    await writeFile(publishedPath, result.code, 'utf8');

    const eagerCount = files.length - lazyFiles.length;
    console.log(`tickets-app.min.js generado: ${eagerCount} ficheros → ${kb(concatenated.length)} → ${kb(result.code.length)} minificado`);
    console.log(`  fuente:     ${path.relative(root, outPath)}`);
    console.log(`  publicado:  ${path.relative(root, publishedPath)}`);
    if (lazyFiles.length > 0) {
        console.log(`  bajo demanda: ${lazyFiles.length} modales, ${kb(lazyMin)} minificados en ${path.relative(root, lazyOutDir)}/`);
    }

    await buildCss();
}

main().catch((err) => {
    console.error(err.message || err);
    process.exit(1);
});
