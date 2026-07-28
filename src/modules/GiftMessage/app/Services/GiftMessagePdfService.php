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

    private const FONT_STACKS = [
        'helvetica' => 'Helvetica, Arial, sans-serif',
        'times' => '"Times New Roman", Times, serif',
        'courier' => '"Courier New", Courier, monospace',
        'dejavusans' => '"DejaVu Sans", sans-serif',
        'dejavuserif' => '"DejaVu Serif", serif',
    ];

    public function __construct(
        private readonly GiftMessageConfigService $configService
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows  Filas ya resueltas (id_order,
     *                                                  gift_message, firstname, lastname,
     *                                                  id_gestion) tal como las vio el
     *                                                  usuario en el listado/busqueda.
     */
    public function generate(string $type, array $rows): PdfDocument
    {
        $config = $this->configService->current();
        $size = self::SIZES[$type];

        $pdf = Pdf::loadView($type === 'card' ? 'giftmessage::pdf.card' : 'giftmessage::pdf.envelope', [
            'pages' => $this->buildPages($type, $rows, $config),
            'backgroundPath' => $this->imagePath($type === 'card' ? $config->card_image : $config->envelope_image),
            'pageWidthMm' => $size['w'],
            'pageHeightMm' => $size['h'],
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

            $pages[] = $type === 'card'
                ? $this->buildCardPage($order, $config)
                : $this->buildEnvelopePage($order, $config);
        }

        return $pages;
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, array<string, mixed>>
     */
    private function buildEnvelopePage(array $order, GiftMessageConfig $config): array
    {
        $size = self::SIZES['envelope'];

        return [
            't1' => [
                'text' => trim($order['firstname'].' '.$order['lastname']),
                'left' => $this->toMm((float) $config->env_t1_x, $size['w']),
                'top' => $this->toMm((float) $config->env_t1_y, $size['h']),
                'font_family' => $this->fontStack($config->env_t1_font),
                'font_size' => $config->env_t1_size,
            ],
            't2' => [
                'text' => (string) ($order['id_gestion'] ?? ''),
                'left' => $this->toMm((float) $config->env_t2_x, $size['w']),
                'top' => $this->toMm((float) $config->env_t2_y, $size['h']),
                'font_family' => $this->fontStack($config->env_t2_font),
                'font_size' => $config->env_t2_size,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, array<string, mixed>>
     */
    private function buildCardPage(array $order, GiftMessageConfig $config): array
    {
        $size = self::SIZES['card'];
        $message = trim((string) $order['gift_message']);
        $font = $this->containsEmoji($message) ? 'dejavusans' : $config->card_t1_font;

        return [
            't1' => [
                'html' => $this->messageToHtml($message, (int) $config->card_t1_size),
                'left' => $this->toMm((float) $config->card_t1_x, $size['w']),
                'top' => $this->toMm((float) $config->card_t1_y, $size['h']),
                'font_family' => $this->fontStack($font),
                'font_size' => $config->card_t1_size,
            ],
            't2' => [
                'text' => (string) ($order['id_gestion'] ?? ''),
                'left' => $this->toMm((float) $config->card_t2_x, $size['w']),
                'top' => $this->toMm((float) $config->card_t2_y, $size['h']),
                'font_family' => $this->fontStack($config->card_t2_font),
                'font_size' => $config->card_t2_size,
            ],
        ];
    }

    private function toMm(float $percentage, float $pageSizeMm): float
    {
        return round($percentage * $pageSizeMm / 100, 2);
    }

    private function fontStack(string $font): string
    {
        return self::FONT_STACKS[$font] ?? self::FONT_STACKS['helvetica'];
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
