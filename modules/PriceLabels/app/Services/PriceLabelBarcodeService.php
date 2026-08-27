<?php

namespace Modules\PriceLabels\Services;

use TCPDF2DBarcode;
use TCPDFBarcode;

class PriceLabelBarcodeService
{
    /**
     * Simbologias 1D ofrecidas al usuario. La clave es lo que se guarda en la
     * definicion del campo; el valor es lo que se muestra en el desplegable.
     *
     * @var array<string, string>
     */
    public const SYMBOLOGIES = [
        'C128' => 'Code 128 (acepta cualquier texto)',
        'EAN13' => 'EAN-13 (12 o 13 digitos)',
        'C39' => 'Code 39 (letras y numeros)',
    ];

    /**
     * Tipos de campo que se renderizan como imagen en vez de como texto.
     *
     * @var array<int, string>
     */
    public const IMAGE_TYPES = ['barcode', 'qr'];

    /**
     * PNG en data URI listo para incrustar en el PDF o en el editor.
     *
     * Devuelve null si el valor no es codificable con esa simbologia (por
     * ejemplo un EAN-13 con digitos de menos), para que quien llama pueda caer
     * a mostrar el texto plano en vez de romper la etiqueta entera.
     */
    public function pngDataUri(string $type, string $value, string $symbology = 'C128'): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $png = $type === 'qr'
                ? (new TCPDF2DBarcode($value, 'QRCODE,M'))->getBarcodePngData(4, 4, [0, 0, 0])
                : (new TCPDFBarcode($value, $symbology))->getBarcodePngData(2, 60, [0, 0, 0]);
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($png) || $png === '') {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function isImageType(?string $type): bool
    {
        return in_array($type, self::IMAGE_TYPES, true);
    }
}
