<?php

namespace Modules\GiftMessage\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\GiftMessage\Models\GiftMessageConfig;

class GiftMessagePdfService
{
    private const DISK = 'public';

    private const MM_PER_POINT = 2.83464567;

    private const EMOJI_CACHE_FOLDER = 'giftmessage/emoji-cache';

    private const TWEMOJI_BASE_URL = 'https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/72x72/';

    private const EMOJI_REGEX = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u';

    private const JOINER_REGEX = '/[\x{200D}\x{FE0F}\x{20E3}]/u';

    /** Interlineado del .field td de la plantilla del PDF. */
    private const LINE_HEIGHT = 1.2;

    /**
     * Interlineados a probar, del mas holgado al mas apretado. Antes de encoger
     * la letra se aprieta el interlineado: pasar de 1.2 a 1.05 gana un 12% de
     * alto sin tocar el tamano, que se nota mucho mas en el resultado impreso
     * que dos puntos menos de letra.
     */
    private const LINE_HEIGHTS = [1.2, 1.1, 1.05];

    /** Suelo absoluto: por debajo de esto no se imprime nada legible. */
    private const HARD_MIN_FONT_SIZE = 5;

    /**
     * Aire entre parrafos, en fracciones del tamano de letra. Antes cada salto
     * doble metia una linea vacia entera (con DejaVu Sans, mas de un 150% del
     * tamano), que dejaba el mensaje partido en bloques sueltos y ademas se
     * comia varias lineas de la caja, obligando a encoger la letra sin falta.
     */
    private const PARAGRAPH_SPACING_EM = 0.35;

    /**
     * El ajuste baja de medio en medio punto: entre 10 y 11 pt hay un 10% de
     * caja, y con pasos enteros se desaprovechaba en cuanto el texto se pasaba
     * por poco.
     */
    private const FONT_SIZE_STEP = 0.5;

    /**
     * Aire interior de la caja, en fracciones del tamano de letra, para que el
     * texto no vaya rozando el borde del recuadro impreso.
     */
    private const BOX_PADDING_EM = 0.15;

    public const ALIGNMENTS = ['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha'];

    public const VERTICAL_ALIGNMENTS = ['top' => 'Arriba', 'middle' => 'Centro', 'bottom' => 'Abajo'];

    /** Contenido del texto grande de cada pieza. */
    public const CONTENT_MESSAGE = 'message';

    public const CONTENT_RECIPIENT = 'recipient';

    public const CONTENT_LABELS = [
        self::CONTENT_MESSAGE => 'Mensaje regalo',
        self::CONTENT_RECIPIENT => 'Nombre de quien lo recibe',
    ];

    private const SIZES = [
        'envelope' => ['w' => 220.0, 'h' => 110.0],
        'card' => ['w' => 200.0, 'h' => 90.0],
    ];

    private ?FontMetrics $fontMetrics = null;

    /** @var array<string, float> */
    private array $widthCache = [];

    /** @var array<string, string|null> */
    private array $fontFileCache = [];

    /** @var array<int, array<string, mixed>> Avisos de la ultima generacion. */
    private array $warnings = [];

    /** Aire entre parrafos en curso, para que medir y pintar usen el mismo. */
    private float $spacingEm = self::PARAGRAPH_SPACING_EM;

    public function __construct(
        private readonly GiftMessageConfigService $configService,
        private readonly GiftMessageFontService $fontService
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows  Filas ya resueltas tal como las
     *                                                  vio el usuario en el listado o la
     *                                                  busqueda. Del payload solo se
     *                                                  imprimen gift_message y npedidocli.
     */
    public function generate(string $type, array $rows): PdfDocument
    {
        return $this->generateWith($this->configService->current(), $type, $rows);
    }

    /**
     * Igual que generate(), pero con una configuracion concreta en vez de la
     * guardada: asi el editor puede pedir un PDF de prueba con los cambios que
     * hay en pantalla sin tener que guardarlos antes.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function generateWith(GiftMessageConfig $config, string $type, array $rows): PdfDocument
    {
        $this->ensureFontDirectoryExists();

        $this->warnings = [];
        $size = self::SIZES[$type];

        // El PDF sale SIN la imagen de fondo, a proposito: se imprime sobre sobres
        // y tarjetas que ya vienen impresos de imprenta, asi que solo hay que
        // depositar el texto. La imagen configurada existe unicamente para
        // encuadrar las cajas en el editor de ajustes. El tamano de pagina y las
        // posiciones no cambian, de modo que el texto cae exactamente donde se
        // veia sobre la imagen.
        $pdf = Pdf::loadView('giftmessage::pdf.page', [
            'pages' => $this->buildPages($type, $rows, $config),
            'pageWidthMm' => $size['w'],
            'pageHeightMm' => $size['h'],
            'fontFaceCss' => $this->fontService->fontFaceCss(forPdf: true),
        ]);

        // Sin esto DomPDF embebe la fuente entera: una fuente china son 10 MB en
        // CADA PDF de una tarjeta de dos lineas. Con el subconjunto solo viajan
        // los glifos usados y el mismo PDF baja de 19 MB a 6 KB. Se activa aqui
        // y no en la config global para no cambiar el resto de PDF de la app.
        $pdf->setOption('enable_font_subsetting', true);

        // No pasar 'landscape' aqui: DomPDF invierte ancho/alto del array custom
        // cuando la orientacion es 'landscape', aunque el array ya sea apaisado
        // (ancho > alto), dejando la pagina en vertical. El array ya viene con el
        // ancho/alto correctos, asi que se usa la orientacion por defecto.
        return $pdf->setPaper([0, 0, $size['w'] * self::MM_PER_POINT, $size['h'] * self::MM_PER_POINT]);
    }

    /**
     * Fuente y tamano que usaria el PDF para un texto dado, para que la vista
     * previa del editor de ajustes ensene exactamente lo mismo. No basta con
     * encoger por CSS en el navegador: el PDF fuerza DejaVu Sans cuando hay
     * emojis (mas ancha y un 25% mas alta por linea que Helvetica) y mide con
     * las metricas de la fuente, asi que el navegador se quedaba corto y
     * ensenaba una letra mas grande de la que se iba a imprimir.
     *
     * @param  array<string, array{w?: float|string, h?: float|string}>  $boxes  Tamano
     *                                                                           de las cajas tal como estan EN PANTALLA (en %), que puede no ser el
     *                                                                           guardado todavia. Sin esto, mover una caja no cambiaba la vista previa.
     * @return array<string, array{font: string, font_family: string, font_size: int}>
     */
    public function previewMetrics(string $type, string $message, string $orderNumber, array $boxes = [], string $recipient = '', array $aligns = []): array
    {
        $config = $this->configService->current();
        $size = self::SIZES[$type];
        $prefix = $type === 'card' ? 'card' : 'env';
        $minSize = $this->minFontSize($config);

        $message = $this->normalizeMessage($this->t1Text($type, [
            'gift_message' => $message,
            'firstname' => $recipient,
        ], $config));
        $t1Font = $this->resolveFont($message, $config->{$prefix.'_t1_font'})['font'];
        $t2Font = $this->resolveFont($orderNumber, $config->{$prefix.'_t2_font'})['font'];

        $t1Fit = $this->fitText($message, (int) $config->{$prefix.'_t1_size'}, $this->box($config, $prefix.'_t1', $size, $boxes['t1'] ?? []), $t1Font, $minSize);
        $t2Fit = $this->fitText($orderNumber, (int) $config->{$prefix.'_t2_size'}, $this->box($config, $prefix.'_t2', $size, $boxes['t2'] ?? []), $t2Font, $minSize);

        return [
            't1' => [
                'font' => $t1Font,
                'font_family' => $this->fontStack($t1Font),
                'line_height' => $this->lineHeightRatio($t1Font, $t1Fit['line_height']),
                'font_size' => $t1Fit['size'],
                'configured_size' => (int) $config->{$prefix.'_t1_size'},
                'min_font_size' => $minSize,
                'fits' => $t1Fit['fits'],
                // La alineacion que manda es la que el usuario tiene en pantalla,
                // aunque todavia no la haya guardado.
                'align' => $this->alignment($aligns['t1']['align'] ?? $config->{$prefix.'_t1_align'} ?? null, self::ALIGNMENTS, 'center'),
                'valign' => $this->alignment($aligns['t1']['valign'] ?? $config->{$prefix.'_t1_valign'} ?? null, self::VERTICAL_ALIGNMENTS, 'middle'),
            ],
            't2' => [
                'font' => $t2Font,
                'font_family' => $this->fontStack($t2Font),
                'line_height' => $this->lineHeightRatio($t2Font, $t2Fit['line_height']),
                'font_size' => $t2Fit['size'],
                'configured_size' => (int) $config->{$prefix.'_t2_size'},
                'min_font_size' => $minSize,
                'fits' => $t2Fit['fits'],
                // La alineacion que manda es la que el usuario tiene en pantalla,
                // aunque todavia no la haya guardado.
                'align' => $this->alignment($aligns['t2']['align'] ?? $config->{$prefix.'_t2_align'} ?? null, self::ALIGNMENTS, 'center'),
                'valign' => $this->alignment($aligns['t2']['valign'] ?? $config->{$prefix.'_t2_valign'} ?? null, self::VERTICAL_ALIGNMENTS, 'middle'),
            ],
        ];
    }

    /**
     * DomPDF guarda aqui una copia de cada fuente subida y su fichero de
     * metricas (.ufm) la primera vez que la usa. Si el directorio no existe —y
     * por defecto no viene creado, porque storage/fonts no esta en el repo— la
     * generacion muere con
     * "fopen(.../<familia>_<estilo>_<hash>.ufm): Failed to open stream".
     * Crearlo aqui evita depender de que el despliegue se acuerde de hacerlo.
     */
    private function ensureFontDirectoryExists(): void
    {
        $directories = array_unique(array_filter([
            (string) config('dompdf.options.font_dir'),
            (string) config('dompdf.options.font_cache'),
        ]));

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                File::ensureDirectoryExists($directory, 0775);
            }

            if (! is_writable($directory)) {
                Log::warning('GiftMessage: el directorio de fuentes de DomPDF no es escribible; las fuentes personalizadas no se podran usar.', [
                    'directory' => $directory,
                ]);
            }
        }
    }

    public function containsEmoji(string $text): bool
    {
        return $text !== '' && preg_match(self::EMOJI_REGEX, $text) === 1;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildPages(string $type, array $rows, GiftMessageConfig $config): array
    {
        $pages = [];

        foreach ($rows as $order) {
            if (trim((string) ($order['gift_message'] ?? '')) === '') {
                continue;
            }

            $pages[] = $this->buildPage($type, $order, $config);
        }

        return $pages;
    }

    /**
     * Sobre y tarjeta imprimen lo mismo (T1 el mensaje regalo, T2 el numero de
     * gestion) y solo se diferencian en el tamano de pagina y en el juego de
     * columnas de configuracion, de ahi el prefijo.
     *
     * @param  array<string, mixed>  $order
     * @return array<string, array<string, mixed>>
     */
    private function buildPage(string $type, array $order, GiftMessageConfig $config): array
    {
        $size = self::SIZES[$type];
        $prefix = $type === 'card' ? 'card' : 'env';
        $minSize = $this->minFontSize($config);

        $this->spacingEm = $this->paragraphSpacing($config);
        $message = $this->normalizeMessage($this->t1Text($type, $order, $config));
        $t1Choice = $this->resolveFont($message, $config->{$prefix.'_t1_font'});
        $t1Font = $t1Choice['font'];
        $t1Box = $this->box($config, $prefix.'_t1', $size);

        // El tamano configurado es el maximo: si el mensaje no cabe se aprieta
        // el interlineado y, si aun asi no entra, se reduce la letra.
        $t1Fit = $this->fitText($message, (int) $config->{$prefix.'_t1_size'}, $t1Box, $t1Font, $minSize);

        $t2Text = (string) ($order['npedidocli'] ?? '');
        $t2Font = $this->resolveFont($t2Text, $config->{$prefix.'_t2_font'})['font'];
        $t2Box = $this->box($config, $prefix.'_t2', $size);
        $t2Fit = $this->fitText($t2Text, (int) $config->{$prefix.'_t2_size'}, $t2Box, $t2Font, $minSize);

        $this->collectWarning($type, $order, $message, $t1Fit, $minSize, (int) $config->{$prefix.'_t1_size'}, $t1Choice['missing']);

        return [
            't1' => [
                'html' => $this->messageToHtml($message, $t1Fit['line_height'], $this->paragraphSpacing($config)),
                'font_family' => $this->fontStack($t1Font),
                'font_size' => $t1Fit['size'],
                'line_height' => $t1Fit['line_height'],
                'color' => $this->color($config->{$prefix.'_t1_color'}),
                'opacity' => $this->opacity((int) $config->{$prefix.'_t1_opacity'}),
                'align' => $this->alignment($config->{$prefix.'_t1_align'} ?? null, self::ALIGNMENTS, 'center'),
                'valign' => $this->alignment($config->{$prefix.'_t1_valign'} ?? null, self::VERTICAL_ALIGNMENTS, 'middle'),
                'padding' => round(self::BOX_PADDING_EM * $t1Fit['size'], 2),
            ] + $t1Box,
            't2' => [
                // El personal identifica el pedido por el npedidocli del ERP (el
                // numero corto), no por el idpedidocli que guarda PrestaShop.
                'text' => $t2Text,
                'font_family' => $this->fontStack($t2Font),
                'font_size' => $t2Fit['size'],
                'line_height' => $t2Fit['line_height'],
                'color' => $this->color($config->{$prefix.'_t2_color'}),
                'opacity' => $this->opacity((int) $config->{$prefix.'_t2_opacity'}),
                'align' => $this->alignment($config->{$prefix.'_t2_align'} ?? null, self::ALIGNMENTS, 'center'),
                'valign' => $this->alignment($config->{$prefix.'_t2_valign'} ?? null, self::VERTICAL_ALIGNMENTS, 'middle'),
                'padding' => round(self::BOX_PADDING_EM * $t2Fit['size'], 2),
            ] + $t2Box,
        ];
    }

    /**
     * Que va en el texto grande de la pieza. El sobre suele llevar el nombre de
     * quien recibe el regalo —es lo que se lee al repartir— y la tarjeta el
     * mensaje, pero cada pieza se configura por separado.
     *
     * @param  array<string, mixed>  $order
     */
    private function t1Text(string $type, array $order, GiftMessageConfig $config): string
    {
        $prefix = $type === 'card' ? 'card' : 'env';

        if (($config->{$prefix.'_t1_content'} ?? 'message') !== self::CONTENT_RECIPIENT) {
            return (string) ($order['gift_message'] ?? '');
        }

        $name = trim(((string) ($order['firstname'] ?? '')).' '.((string) ($order['lastname'] ?? '')));

        // Sin nombre no se imprime una pieza en blanco: se cae al mensaje.
        return $name !== '' ? $name : (string) ($order['gift_message'] ?? '');
    }

    /**
     * Anota los mensajes que no salen como deberian, para que quien imprime se
     * entere: hasta ahora la caja recortaba lo que sobraba en silencio y el
     * cliente recibia la tarjeta con la frase a medias. Lo mismo vale para los
     * caracteres que ninguna fuente instalada sabe pintar (los chinos, sin ir
     * mas lejos), que salen como cuadros vacios.
     *
     * @param  array<string, mixed>  $order
     * @param  array{size: int, line_height: float, fits: bool}  $fit
     * @param  array<int, string>  $missing  Caracteres que no se van a imprimir.
     */
    private function collectWarning(string $type, array $order, string $message, array $fit, int $minSize, int $configuredSize, array $missing = []): void
    {
        $tooSmall = $message !== '' && ! ($fit['fits'] && $fit['size'] >= $configuredSize);

        if (! $tooSmall && $missing === []) {
            return;
        }

        $orderNumber = (string) (($order['npedidocli'] ?? null) ?: ($order['id_gestion'] ?? null) ?: ($order['id_order'] ?? ''));

        $this->warnings[] = [
            'order_number' => $orderNumber,
            'type' => $type,
            'font_size' => $fit['size'],
            'configured_size' => $configuredSize,
            'min_font_size' => $minSize,
            'truncated' => $tooSmall && ! $fit['fits'],
            'reduced' => $tooSmall && $fit['fits'],
            'unprintable' => implode('', $missing),
            'length' => mb_strlen($message),
        ];
    }

    /**
     * Fuente con la que se imprime un texto y los caracteres que se van a perder.
     *
     * DomPDF no avisa de un glifo que le falta: lo dibuja como cuadro vacio, que
     * es lo que pasaba con los mensajes en chino. Asi que si la fuente elegida no
     * cubre el texto se busca entre las subidas una que si, y si ninguna puede se
     * devuelven los caracteres afectados para avisar a quien imprime.
     *
     * @return array{font: string, missing: array<int, string>}
     */
    private function resolveFont(string $text, string $configured): array
    {
        // Con emojis se sigue prefiriendo DejaVu Sans: los mensajes que los traen
        // suelen venir con simbolos y comillas raras que las Base-14 no tienen.
        $font = $this->containsEmoji($text) ? 'dejavusans' : $configured;
        $missing = $this->unprintableCharacters($text, $font);

        if ($missing === []) {
            return ['font' => $font, 'missing' => []];
        }

        $codepoints = array_map(fn (string $char) => mb_ord($char, 'UTF-8'), $this->printableCharacters($text));
        $alternative = $this->fontService->familiesSupporting(array_values(array_filter($codepoints, fn ($cp) => $cp !== false)))[0] ?? null;

        if ($alternative !== null) {
            return ['font' => $alternative, 'missing' => []];
        }

        return ['font' => $font, 'missing' => $missing];
    }

    /**
     * Caracteres del texto que la fuente no sabe pintar. Los emojis no cuentan:
     * se imprimen como imagen (ver messageToHtml), igual que los espacios y los
     * saltos de linea, que no llevan glifo visible.
     *
     * @return array<int, string>
     */
    private function unprintableCharacters(string $text, string $font): array
    {
        $missing = [];

        foreach ($this->printableCharacters($text) as $char) {
            $codepoint = mb_ord($char, 'UTF-8');

            if ($codepoint === false || $this->fontService->supportsCodepoint($font, $codepoint)) {
                continue;
            }

            $missing[$char] = $char;
        }

        return array_values($missing);
    }

    /**
     * Caracteres del texto que necesitan glifo de la fuente.
     *
     * @return array<int, string>
     */
    private function printableCharacters(string $text): array
    {
        $plain = preg_replace(self::JOINER_REGEX, '', (string) preg_replace(self::EMOJI_REGEX, '', $text)) ?? '';
        $plain = preg_replace('/\s+/u', '', $plain) ?? '';

        return preg_split('//u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Avisos de la ultima llamada a generate(): mensajes que se han impreso mas
     * pequenos de lo configurado o que no caben ni al minimo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lastWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * @param  array<string, string>  $allowed
     */
    private function alignment(?string $value, array $allowed, string $fallback): string
    {
        return array_key_exists((string) $value, $allowed) ? (string) $value : $fallback;
    }

    private function paragraphSpacing(GiftMessageConfig $config): float
    {
        $spacing = (float) ($config->paragraph_spacing ?? self::PARAGRAPH_SPACING_EM);

        return max(0.0, min(2.0, $spacing));
    }

    private function minFontSize(GiftMessageConfig $config): int
    {
        return max(self::HARD_MIN_FONT_SIZE, (int) ($config->min_font_size ?: self::HARD_MIN_FONT_SIZE));
    }

    /**
     * Mayor tamano (<= el configurado) con el que el texto entero cabe en la
     * caja, junto al interlineado necesario y a si de verdad ha cabido.
     *
     * Se mide con las metricas reales de la fuente, no por numero de
     * caracteres: una linea de "iiii" y otra de "WWWW" ocupan muy distinto.
     * La busqueda es por biseccion —"cabe" es monotono: si entra a N puntos,
     * entra a N-1— asi que un mensaje largo se resuelve en 4 pasadas en vez de
     * las 9 que costaba bajando de punto en punto.
     *
     * @param  array{left: float, top: float, width: float, height: float}  $box  En milimetros.
     * @return array{size: int, line_height: float, fits: bool}
     */
    private function fitText(string $text, int $maxSize, array $box, string $font, int $minSize): array
    {
        $text = $this->normalizeMessage($text);
        $minSize = max(self::HARD_MIN_FONT_SIZE, $minSize);
        $maxSize = max($minSize, $maxSize);

        if ($text === '' || $box['width'] <= 0 || $box['height'] <= 0) {
            return ['size' => (float) $maxSize, 'line_height' => self::LINE_HEIGHT, 'fits' => true];
        }

        // La busqueda va en pasos de medio punto: se trabaja con el doble del
        // tamano para poder biseccionar con enteros y luego se divide.
        $lowSteps = (int) round($minSize / self::FONT_SIZE_STEP);
        $highSteps = (int) round($maxSize / self::FONT_SIZE_STEP);

        $attempt = function (int $steps) use ($text, $box, $font): ?array {
            $size = $steps * self::FONT_SIZE_STEP;
            // El aire interior come ancho y alto disponibles, asi que se
            // descuenta antes de medir; si no, el texto acabaria rozando el borde.
            $padding = self::BOX_PADDING_EM * $size;
            $widthPt = ($box['width'] * self::MM_PER_POINT) - (2 * $padding);
            // Un 3% de holgura: la estimacion y el motor no cuadran al milimetro
            // (kerning, redondeos), y pasarse significa texto cortado.
            $heightPt = (($box['height'] * self::MM_PER_POINT) - (2 * $padding)) * 0.97;

            if ($widthPt <= 0 || $heightPt <= 0) {
                return null;
            }

            $lineHeight = $this->lineHeightThatFits($text, $size, $widthPt, $heightPt, $font);

            return $lineHeight === null ? null : ['size' => $size, 'line_height' => $lineHeight, 'fits' => true];
        };

        // Caso normal: cabe al tamano configurado y no hay nada que buscar.
        $fit = $attempt($highSteps);

        if ($fit !== null) {
            return $fit;
        }

        $best = null;

        while ($lowSteps <= $highSteps) {
            $middle = intdiv($lowSteps + $highSteps, 2);
            $fit = $attempt($middle);

            if ($fit !== null) {
                $best = $fit;
                $lowSteps = $middle + 1;

                continue;
            }

            $highSteps = $middle - 1;
        }

        // Ni al minimo cabe: se imprime al minimo y se avisa, porque la caja
        // recorta lo que sobra (overflow: hidden) y el cliente recibiria el
        // mensaje a medias sin que nadie se entere.
        return $best ?? [
            'size' => (float) $minSize,
            'line_height' => self::LINE_HEIGHTS[count(self::LINE_HEIGHTS) - 1],
            'fits' => false,
        ];
    }

    /**
     * Interlineado mas holgado con el que el texto cabe a ese tamano, o null si
     * no cabe ni con el mas apretado.
     */
    private function lineHeightThatFits(string $text, float $size, float $widthPt, float $heightPt, string $font): ?float
    {
        $lines = $this->countWrappedLines($text, $size, $widthPt, $font);
        $fontHeight = $this->fontHeightPt($size, $font);
        $spacing = max(0, count($this->splitParagraphs($text)) - 1) * $this->spacingEm * $size;

        foreach (self::LINE_HEIGHTS as $lineHeight) {
            if (($lines * $lineHeight * $fontHeight) + $spacing <= $heightPt) {
                return $lineHeight;
            }
        }

        return null;
    }

    /**
     * Une las dos ultimas palabras con un espacio duro para que bajen juntas: un
     * ultimo renglon con una sola palabra suelta queda feo, sobre todo en una
     * tarjeta con el texto centrado.
     */
    private function avoidWidow(string $paragraph): string
    {
        $words = preg_split('/\s+/u', trim($paragraph), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) < 3) {
            return $paragraph;
        }

        $last = array_pop($words);
        $previous = array_pop($words);
        $words[] = $previous."\u{00A0}".$last;

        return implode(' ', $words);
    }

    /**
     * Parrafos del mensaje: bloques separados por una o mas lineas en blanco.
     *
     * @return array<int, string>
     */
    private function splitParagraphs(string $text): array
    {
        $paragraphs = preg_split('/\n{2,}/u', $text) ?: [$text];

        return array_values(array_filter(
            array_map('trim', $paragraphs),
            fn (string $paragraph) => $paragraph !== ''
        ));
    }

    /**
     * Limpia lo que solo gasta espacio: los mensajes pegados desde el movil
     * llegan con lineas en blanco de sobra y espacios repetidos que se comen
     * varias lineas de la caja sin aportar nada.
     */
    private function normalizeMessage(string $text): string
    {
        $text = preg_replace('/\R/u', "\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;
        // Como mucho una linea en blanco seguida.
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Alto de una linea sin contar el interlineado. DomPDF no usa el tamano de
     * letra sino la altura real de la fuente, y ahi cada familia manda: DejaVu
     * Sans (la que se fuerza cuando el mensaje trae emojis) mide 1.28 em frente
     * al 1.02 de Helvetica, o sea un 25% mas por linea.
     */
    private function fontHeightPt(float $size, string $font): float
    {
        $metrics = $this->fontMetrics();
        $fontFile = $this->fontFileFor($font);

        if ($metrics !== null && $fontFile !== null) {
            return (float) $metrics->getFontHeight($fontFile, $size);
        }

        // Sin metricas, se asume una fuente alta para no pasarse de optimista.
        return 1.3 * $size;
    }

    /**
     * Interlineado como multiplo del tamano de letra, que es lo que entienden
     * tanto el CSS de la plantilla del PDF como el del navegador.
     */
    private function lineHeightRatio(string $font, float $lineHeight): float
    {
        return round($lineHeight * $this->fontHeightPt(100, $font) / 100, 3);
    }

    /**
     * Cuenta las lineas que ocupa el texto al ajustarlo al ancho de la caja,
     * respetando los saltos de linea que traiga el mensaje.
     *
     * El ancho se lleva acumulado palabra a palabra en vez de medir la linea
     * entera en cada paso: medir el candidato completo hacia el coste
     * cuadratico y un mensaje de 4.000 caracteres costaba 350 ms, que en un
     * lote de 100 pedidos son mas de 30 segundos bloqueando la peticion.
     */
    private function countWrappedLines(string $text, float $size, float $maxWidthPt, string $font): int
    {
        $lines = 0;
        $spaceWidth = $this->measureWidth(' ', $size, $font);

        foreach (preg_split('/\R/u', $text) ?: [] as $paragraph) {
            $words = preg_split('/\s+/u', trim($paragraph), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            // Las lineas en blanco entre parrafos no cuentan como linea: su aire
            // se suma aparte (PARAGRAPH_SPACING_EM), que ocupa mucho menos.
            if ($words === []) {
                continue;
            }

            $current = 0.0;

            foreach ($words as $word) {
                $wordWidth = $this->measureWidth($word, $size, $font);
                $candidate = $current > 0.0 ? $current + $spaceWidth + $wordWidth : $wordWidth;

                if ($current > 0.0 && $candidate > $maxWidthPt) {
                    $lines++;
                    $current = $wordWidth;

                    continue;
                }

                $current = $candidate;
            }

            // Una palabra sola mas ancha que la caja se parte en varias lineas.
            $lines += max(1, (int) ceil($current / $maxWidthPt));
        }

        return max(1, $lines);
    }

    /**
     * Ancho del texto en puntos. Los emojis no estan en la fuente: se imprimen
     * como <img> cuadrada de 0.8 * tamano (ver messageToHtml), asi que se miden
     * aparte y el resto de caracteres se mide con la fuente real.
     *
     * Se cachea por palabra: en un mensaje largo el mismo "de" o "la" se mide
     * cientos de veces, y una vez por tamano y fuente basta.
     */
    private function measureWidth(string $text, float $size, string $font): float
    {
        if ($text === '') {
            return 0.0;
        }

        $key = $font.'|'.$size.'|'.$text;

        if (isset($this->widthCache[$key])) {
            return $this->widthCache[$key];
        }

        $emojis = preg_match_all(self::EMOJI_REGEX, $text);
        $plain = preg_replace(self::JOINER_REGEX, '', (string) preg_replace(self::EMOJI_REGEX, '', $text)) ?? '';

        return $this->widthCache[$key] = $this->measurePlainWidth($plain, $size, $font) + ($emojis * $size * 0.8);
    }

    private function measurePlainWidth(string $text, float $size, string $font): float
    {
        if ($text === '') {
            return 0.0;
        }

        $metrics = $this->fontMetrics();
        $fontFile = $this->fontFileFor($font);

        if ($metrics !== null && $fontFile !== null) {
            return (float) $metrics->getTextWidth($text, $fontFile, $size);
        }

        // Sin metricas (DomPDF sin inicializar): media conservadora de ~0.5 em
        // por caracter, que para Helvetica se queda algo por encima de lo real.
        return mb_strlen($text) * $size * 0.5;
    }

    private function fontFileFor(string $font): ?string
    {
        if (array_key_exists($font, $this->fontFileCache)) {
            return $this->fontFileCache[$font];
        }

        $metrics = $this->fontMetrics();

        if ($metrics === null) {
            return $this->fontFileCache[$font] = null;
        }

        $file = $metrics->getFont($this->fontMetricsFamily($font)) ?: $metrics->getFont('helvetica');

        return $this->fontFileCache[$font] = ($file ?: null);
    }

    /**
     * La familia que entiende DomPDF: las subidas se registran con su propio
     * nombre via @font-face y las del sistema tienen su nombre canonico.
     */
    private function fontMetricsFamily(string $font): string
    {
        return match ($font) {
            'dejavusans' => 'dejavu sans',
            'dejavuserif' => 'dejavu serif',
            'times' => 'times',
            'courier' => 'courier',
            'helvetica' => 'helvetica',
            default => $font,
        };
    }

    private function fontMetrics(): ?FontMetrics
    {
        if ($this->fontMetrics !== null) {
            return $this->fontMetrics;
        }

        try {
            return $this->fontMetrics = (new Dompdf(new Options([
                'font_dir' => config('dompdf.options.font_dir'),
                'font_cache' => config('dompdf.options.font_cache'),
            ])))->getFontMetrics();
        } catch (\Throwable $e) {
            Log::warning('GiftMessage: no se pudieron cargar las metricas de fuente; el ajuste de tamano usara la estimacion.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Caja del texto en milimetros. Es el limite duro: el ancho fuerza el salto
     * de linea y el alto recorta lo que sobre (overflow hidden en la vista).
     *
     * @param  array{w: float, h: float}  $size
     * @return array{left: float, top: float, width: float, height: float}
     */
    private function box(GiftMessageConfig $config, string $slot, array $size, array $override = []): array
    {
        $widthPercent = isset($override['w']) ? (float) $override['w'] : (float) $config->{$slot.'_w'};
        $heightPercent = isset($override['h']) ? (float) $override['h'] : (float) $config->{$slot.'_h'};

        return [
            'left' => $this->toMm((float) $config->{$slot.'_x'}, $size['w']),
            'top' => $this->toMm((float) $config->{$slot.'_y'}, $size['h']),
            'width' => $this->toMm($widthPercent, $size['w']),
            'height' => $this->toMm($heightPercent, $size['h']),
        ];
    }

    private function color(?string $hex): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $hex) === 1 ? strtolower($hex) : '#000000';
    }

    /**
     * La opacidad se aplica al contenedor y no al color del texto para que los
     * emojis del mensaje, que se pintan como <img>, se atenuen igual que la letra.
     */
    private function opacity(int $percentage): float
    {
        return round(max(0, min(100, $percentage)) / 100, 2);
    }

    private function toMm(float $percentage, float $pageSizeMm): float
    {
        return round($percentage * $pageSizeMm / 100, 2);
    }

    private function fontStack(string $font): string
    {
        $stacks = $this->fontService->cssStacks();

        return $stacks[$font] ?? GiftMessageFontService::BUILTIN_STACKS['helvetica'];
    }

    /**
     * El tamano y la alineacion del emoji los fija la clase .emoji de la
     * plantilla en em, para que sigan al tamano de letra de cada pieza sin
     * calcularlos aqui (los atributos width/height del <img> los interpreta
     * DomPDF en px y el emoji salia mas pequeno de lo medido).
     */
    /**
     * Cada parrafo va en su propio bloque con un margen pequeno, en lugar de
     * separarse con una linea vacia entera: asi el mensaje se lee como un texto
     * seguido y no como fragmentos sueltos, y sobra sitio para letra mas grande.
     */
    private function messageToHtml(string $message, float $lineHeight = self::LINE_HEIGHT, ?float $spacingEm = null): string
    {
        $paragraphs = $this->splitParagraphs($message);

        if ($paragraphs === []) {
            return '';
        }

        $spacing = $spacingEm ?? self::PARAGRAPH_SPACING_EM;
        $last = count($paragraphs) - 1;
        $html = '';

        foreach ($paragraphs as $index => $paragraph) {
            $margin = $index === $last ? '0' : $spacing.'em';

            // El interlineado se repite en cada parrafo a proposito: DomPDF no
            // lo hereda de la celda hacia los bloques hijos (ni con
            // line-height: inherit) y usaba el "normal" de la fuente, bastante
            // mayor, con lo que la ultima linea acababa cortada.
            $html .= '<div style="margin: 0 0 '.$margin.' 0; line-height: '.$lineHeight.';">'
                .nl2br($this->paragraphToHtml($paragraph))
                .'</div>';
        }

        return $html;
    }

    private function paragraphToHtml(string $paragraph): string
    {
        $html = '';

        foreach ($this->splitGraphemes($this->avoidWidow($paragraph)) as $grapheme) {
            if ($this->isJoinerOrVariant($grapheme)) {
                continue;
            }

            $base64 = $this->containsEmoji($grapheme) ? $this->emojiImageBase64($grapheme) : null;

            $html .= $base64
                ? '<img class="emoji" src="data:image/png;base64,'.$base64.'">'
                : e($grapheme);
        }

        return $html;
    }

    /**
     * @return array<int, string>
     */
    private function splitGraphemes(string $text): array
    {
        if (! function_exists('grapheme_strlen')) {
            return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        }

        $length = grapheme_strlen($text);
        $graphemes = [];

        for ($i = 0; $i < $length; $i++) {
            $graphemes[] = grapheme_substr($text, $i, 1);
        }

        return $graphemes;
    }

    /**
     * Solo se descartan los grafemas que son UNICAMENTE marcas invisibles (ZWJ,
     * selector de variante, keycap). Antes bastaba con que el grafema las
     * contuviera, asi que un emoji tan comun como ❤️ (U+2764 + U+FE0F) se
     * tiraba entero y no llegaba a imprimirse; 🎁 (sin FE0F) si salia.
     */
    private function isJoinerOrVariant(string $grapheme): bool
    {
        return $grapheme !== '' && preg_replace(self::JOINER_REGEX, '', $grapheme) === '';
    }

    private function emojiImageBase64(string $grapheme): ?string
    {
        $path = $this->emojiLocalPath($grapheme);

        return $path ? base64_encode(Storage::disk(self::DISK)->get($path)) : null;
    }

    private function emojiLocalPath(string $grapheme): ?string
    {
        $disk = Storage::disk(self::DISK);

        foreach ([false, true] as $dropFe0f) {
            $fileName = $this->twemojiFilename($grapheme, $dropFe0f);
            $path = self::EMOJI_CACHE_FOLDER.'/'.$fileName;

            if ($disk->exists($path)) {
                return $path;
            }

            try {
                $response = Http::connectTimeout(2)->timeout(5)->get(self::TWEMOJI_BASE_URL.$fileName);
            } catch (\Throwable $e) {
                // El CDN de Twemoji caido/lento no debe tumbar la generacion del
                // PDF: se cae al texto plano del emoji para este grafema.
                continue;
            }

            // put() devuelve false en un fallo de escritura (permisos, disco lleno)
            // en vez de lanzar excepción — sin comprobarlo, se devolvía el path
            // igual, y el emoji quedaba como cuadro en blanco en el PDF (get()
            // sobre un archivo que nunca llegó a escribirse).
            if ($response->successful() && strlen($response->body()) > 100 && $disk->put($path, $response->body())) {
                return $path;
            }
        }

        return null;
    }

    private function twemojiFilename(string $grapheme, bool $dropFe0f): string
    {
        $utf32 = mb_convert_encoding($grapheme, 'UTF-32BE', 'UTF-8');
        $codepoints = [];

        for ($i = 0; $i < strlen($utf32); $i += 4) {
            $codepoints[] = strtolower(dechex(unpack('N', substr($utf32, $i, 4))[1]));
        }

        if ($dropFe0f) {
            $codepoints = array_values(array_filter($codepoints, fn ($hex) => $hex !== 'fe0f'));
        }

        return implode('-', $codepoints).'.png';
    }
}
