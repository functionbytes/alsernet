<?php

namespace Modules\GiftMessage\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Facades\Http;
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

    private const SIZES = [
        'envelope' => ['w' => 220.0, 'h' => 110.0],
        'card' => ['w' => 200.0, 'h' => 90.0],
    ];

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
        $config = $this->configService->current();
        $size = self::SIZES[$type];

        $pdf = Pdf::loadView('giftmessage::pdf.page', [
            'pages' => $this->buildPages($type, $rows, $config),
            'backgroundPath' => $this->imagePath($type === 'card' ? $config->card_image : $config->envelope_image),
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

        $message = trim((string) $order['gift_message']);
        $t1Size = (int) $config->{$prefix.'_t1_size'};
        $t1Font = $this->containsEmoji($message) ? 'dejavusans' : $config->{$prefix.'_t1_font'};

        return [
            't1' => [
                'html' => $this->messageToHtml($message, $t1Size),
                'font_family' => $this->fontStack($t1Font),
                'font_size' => $t1Size,
                'color' => $this->color($config->{$prefix.'_t1_color'}),
                'opacity' => $this->opacity((int) $config->{$prefix.'_t1_opacity'}),
            ] + $this->box($config, $prefix.'_t1', $size),
            't2' => [
                // El personal identifica el pedido por el npedidocli del ERP (el
                // numero corto), no por el idpedidocli que guarda PrestaShop.
                'text' => (string) ($order['npedidocli'] ?? ''),
                'font_family' => $this->fontStack($config->{$prefix.'_t2_font'}),
                'font_size' => $config->{$prefix.'_t2_size'},
                'color' => $this->color($config->{$prefix.'_t2_color'}),
                'opacity' => $this->opacity((int) $config->{$prefix.'_t2_opacity'}),
            ] + $this->box($config, $prefix.'_t2', $size),
        ];
    }

    /**
     * Caja del texto en milimetros. Es el limite duro: el ancho fuerza el salto
     * de linea y el alto recorta lo que sobre (overflow hidden en la vista).
     *
     * @param  array{w: float, h: float}  $size
     * @return array{left: float, top: float, width: float, height: float}
     */
    private function box(GiftMessageConfig $config, string $slot, array $size): array
    {
        return [
            'left' => $this->toMm((float) $config->{$slot.'_x'}, $size['w']),
            'top' => $this->toMm((float) $config->{$slot.'_y'}, $size['h']),
            'width' => $this->toMm((float) $config->{$slot.'_w'}, $size['w']),
            'height' => $this->toMm((float) $config->{$slot.'_h'}, $size['h']),
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

    private function imagePath(?string $relativePath): ?string
    {
        return $relativePath ? Storage::disk(self::DISK)->path($relativePath) : null;
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

    private function isJoinerOrVariant(string $grapheme): bool
    {
        return preg_match(self::JOINER_REGEX, $grapheme) === 1;
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
