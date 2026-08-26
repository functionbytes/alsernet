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
        $this->ensureFontDirectoryExists();

        $this->warnings = [];
        $config = $this->configService->current();
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
    public function previewMetrics(string $type, string $message, string $orderNumber, array $boxes = []): array
    {
        $config = $this->configService->current();
        $size = self::SIZES[$type];
        $prefix = $type === 'card' ? 'card' : 'env';
        $minSize = $this->minFontSize($config);

        $message = $this->normalizeMessage($message);
        $t1Font = $this->containsEmoji($message) ? 'dejavusans' : $config->{$prefix.'_t1_font'};
        $t2Font = $config->{$prefix.'_t2_font'};

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
            ],
            't2' => [
                'font' => $t2Font,
                'font_family' => $this->fontStack($t2Font),
                'line_height' => $this->lineHeightRatio($t2Font, $t2Fit['line_height']),
                'font_size' => $t2Fit['size'],
                'configured_size' => (int) $config->{$prefix.'_t2_size'},
                'min_font_size' => $minSize,
                'fits' => $t2Fit['fits'],
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

        $message = $this->normalizeMessage((string) $order['gift_message']);
        $t1Font = $this->containsEmoji($message) ? 'dejavusans' : $config->{$prefix.'_t1_font'};
        $t1Box = $this->box($config, $prefix.'_t1', $size);

        // El tamano configurado es el maximo: si el mensaje no cabe se aprieta
        // el interlineado y, si aun asi no entra, se reduce la letra.
        $t1Fit = $this->fitText($message, (int) $config->{$prefix.'_t1_size'}, $t1Box, $t1Font, $minSize);

        $t2Text = (string) ($order['npedidocli'] ?? '');
        $t2Font = $config->{$prefix.'_t2_font'};
        $t2Box = $this->box($config, $prefix.'_t2', $size);
        $t2Fit = $this->fitText($t2Text, (int) $config->{$prefix.'_t2_size'}, $t2Box, $t2Font, $minSize);

        $this->collectWarning($type, $order, $message, $t1Fit, $minSize, (int) $config->{$prefix.'_t1_size'});

        return [
            't1' => [
                'html' => $this->messageToHtml($message, $t1Fit['size']),
                'font_family' => $this->fontStack($t1Font),
                'font_size' => $t1Fit['size'],
                'line_height' => $t1Fit['line_height'],
                'color' => $this->color($config->{$prefix.'_t1_color'}),
                'opacity' => $this->opacity((int) $config->{$prefix.'_t1_opacity'}),
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
            ] + $t2Box,
        ];
    }

    /**
     * Anota los mensajes que no salen como deberian, para que quien imprime se
     * entere: hasta ahora la caja recortaba lo que sobraba en silencio y el
     * cliente recibia la tarjeta con la frase a medias.
     *
     * @param  array<string, mixed>  $order
     * @param  array{size: int, line_height: float, fits: bool}  $fit
     */
    private function collectWarning(string $type, array $order, string $message, array $fit, int $minSize, int $configuredSize): void
    {
        if ($message === '' || ($fit['fits'] && $fit['size'] >= $configuredSize)) {
            return;
        }

        $orderNumber = (string) (($order['npedidocli'] ?? null) ?: ($order['id_gestion'] ?? null) ?: ($order['id_order'] ?? ''));

        $this->warnings[] = [
            'order_number' => $orderNumber,
            'type' => $type,
            'font_size' => $fit['size'],
            'configured_size' => $configuredSize,
            'min_font_size' => $minSize,
            'truncated' => ! $fit['fits'],
            'length' => mb_strlen($message),
        ];
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
            return ['size' => $maxSize, 'line_height' => self::LINE_HEIGHT, 'fits' => true];
        }

        $widthPt = $box['width'] * self::MM_PER_POINT;
        $heightPt = $box['height'] * self::MM_PER_POINT;

        // Caso normal: cabe al tamano configurado y no hay nada que buscar.
        $lineHeight = $this->lineHeightThatFits($text, $maxSize, $widthPt, $heightPt, $font);

        if ($lineHeight !== null) {
            return ['size' => $maxSize, 'line_height' => $lineHeight, 'fits' => true];
        }

        $low = $minSize;
        $high = $maxSize;
        $best = null;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);
            $lineHeight = $this->lineHeightThatFits($text, $middle, $widthPt, $heightPt, $font);

            if ($lineHeight !== null) {
                $best = ['size' => $middle, 'line_height' => $lineHeight, 'fits' => true];
                $low = $middle + 1;

                continue;
            }

            $high = $middle - 1;
        }

        // Ni al minimo cabe: se imprime al minimo y se avisa, porque la caja
        // recorta lo que sobra (overflow: hidden) y el cliente recibiria el
        // mensaje a medias sin que nadie se entere.
        return $best ?? [
            'size' => $minSize,
            'line_height' => self::LINE_HEIGHTS[count(self::LINE_HEIGHTS) - 1],
            'fits' => false,
        ];
    }

    /**
     * Interlineado mas holgado con el que el texto cabe a ese tamano, o null si
     * no cabe ni con el mas apretado.
     */
    private function lineHeightThatFits(string $text, int $size, float $widthPt, float $heightPt, string $font): ?float
    {
        $lines = $this->countWrappedLines($text, $size, $widthPt, $font);
        $fontHeight = $this->fontHeightPt($size, $font);

        foreach (self::LINE_HEIGHTS as $lineHeight) {
            if ($lines * $lineHeight * $fontHeight <= $heightPt) {
                return $lineHeight;
            }
        }

        return null;
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
    private function fontHeightPt(int $size, string $font): float
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
    private function countWrappedLines(string $text, int $size, float $maxWidthPt, string $font): int
    {
        $lines = 0;
        $spaceWidth = $this->measureWidth(' ', $size, $font);

        foreach (preg_split('/\R/u', $text) ?: [] as $paragraph) {
            $words = preg_split('/\s+/u', trim($paragraph), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            if ($words === []) {
                $lines++;

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
    private function measureWidth(string $text, int $size, string $font): float
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

    private function measurePlainWidth(string $text, int $size, string $font): float
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

    private function messageToHtml(string $message, int $fontSize): string
    {
        $emojiSize = max(8, (int) round($fontSize * 0.8));
        $html = '';

        foreach ($this->splitGraphemes($message) as $grapheme) {
            if ($this->isJoinerOrVariant($grapheme)) {
                continue;
            }

            $base64 = $this->containsEmoji($grapheme) ? $this->emojiImageBase64($grapheme) : null;

            $html .= $base64
                ? '<img src="data:image/png;base64,'.$base64.'" width="'.$emojiSize.'" height="'.$emojiSize.'">'
                : e($grapheme);
        }

        return nl2br($html);
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

            if ($response->successful() && strlen($response->body()) > 100) {
                $disk->put($path, $response->body());

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
