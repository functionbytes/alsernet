<?php

namespace Alsernet\Etiquetas;

use TCPDF;

class PdfBuilder
{
    private $settings;
    private $bgPath;

    /**
     * Constructor flexible:
     *
     * - new PdfBuilder($bgPath, $backups)
     * - new PdfBuilder($backups)
     */
    public function __construct($arg1 = null, $arg2 = null)
    {
        // Caso 1: (string $bgPath, array $backups)
        if (is_string($arg1) && is_array($arg2)) {
            $this->bgPath  = $arg1;
            $this->settings = $arg2;
            error_log('[PdfBuilder::__construct] Modo 1 (bgPath + backups)');
            return;
        }

        // Caso 2: (array $backups)
        if (is_array($arg1) && $arg2 === null) {
            $this->settings = $arg1;
            $this->bgPath   = null;
            error_log('[PdfBuilder::__construct] Modo 2 (solo backups)');
            return;
        }

        // Fallback por si acaso
        $this->settings = is_array($arg1) ? $arg1 : (is_array($arg2) ? $arg2 : array());
        $this->bgPath   = is_string($arg1) ? $arg1 : null;
        error_log('[PdfBuilder::__construct] Modo fallback');
    }

    /**
     * VERTICAL clásico (no se está usando normalmente, pero lo dejo)
     */
    public function build(array $rows, $outputPdf)
    {
        error_log('[PdfBuilder::build] INICIO (clásico)');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Alsernet');
        $pdf->SetAuthor('Alsernet');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        // canvas 700x990 px -> 210x297 mm
        $mmx = function ($px) {
            if (!is_numeric($px)) {
                error_log('[PdfBuilder::build] mmx valor NO numérico: ' . var_export($px, true));
                throw new \Exception('VERTICAL clásico: mmx recibió un valor no numérico');
            }
            $px = (float)$px;
            return ($px * 210.0) / 700.0;
        };
        $mmy = function ($px) {
            if (!is_numeric($px)) {
                error_log('[PdfBuilder::build] mmy valor NO numérico: ' . var_export($px, true));
                throw new \Exception('VERTICAL clásico: mmy recibió un valor no numérico');
            }
            $px = (float)$px;
            return ($px * 297.0) / 990.0;
        };

        $chunks = array_chunk($rows, 4);
        foreach ($chunks as $chunkIdx => $chunk) {
            error_log('[PdfBuilder::build] Página vertical clásica chunk #' . $chunkIdx);
            $pdf->AddPage();
            if ($this->bgPath) {
                $pdf->Image($this->bgPath, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
            }

            for ($slot = 1; $slot <= 4; $slot++) {
                if (!isset($chunk[$slot - 1])) {
                    continue;
                }
                $data = $chunk[$slot - 1];

                error_log("[PdfBuilder::build] Slot={$slot} referencia=" . (isset($data['referencia']) ? $data['referencia'] : ''));

                // LABEL
                $pdf->SetFont(
                    !empty($this->settings['font_label_family']) ? $this->settings['font_label_family'] : 'helvetica',
                    '',
                    (int)(!empty($this->settings['font_label_size']) ? $this->settings['font_label_size'] : 12)
                );
                $this->text(
                    $pdf,
                    $mmx((float)$this->settings['pos_label_x' . $slot]),
                    $mmy((float)$this->settings['pos_label_y' . $slot]),
                    (string)(!empty($this->settings['label_text']) ? $this->settings['label_text'] : 'Precio recomendado:'),
                    !empty($this->settings['color_label']) ? $this->settings['color_label'] : '#000000',
                    false
                );

                // REFERENCIA
                $pdf->SetFont(
                    !empty($this->settings['font_referencia_family']) ? $this->settings['font_referencia_family'] : 'helvetica',
                    '',
                    (int)(!empty($this->settings['font_referencia_size']) ? $this->settings['font_referencia_size'] : 12)
                );
                $this->text(
                    $pdf,
                    $mmx((float)$this->settings['pos_referencia_x' . $slot]),
                    $mmy((float)$this->settings['pos_referencia_y' . $slot]),
                    (string)$data['referencia'],
                    !empty($this->settings['color_referencia']) ? $this->settings['color_referencia'] : '#000000',
                    false
                );

                // DESCRIPCIÓN
                $pdf->SetFont(
                    !empty($this->settings['font_descripcion_family']) ? $this->settings['font_descripcion_family'] : 'helvetica',
                    '',
                    (int)(!empty($this->settings['font_descripcion_size']) ? $this->settings['font_descripcion_size'] : 12)
                );
                $this->textBoxCentered(
                    $pdf,
                    $mmx((float)$this->settings['pos_descripcion_x' . $slot]),
                    $mmy((float)$this->settings['pos_descripcion_y' . $slot]),
                    80.0,
                    (string)$data['descripcion'],
                    !empty($this->settings['color_descripcion']) ? $this->settings['color_descripcion'] : '#000000'
                );

                // PVPRP
                $pdf->SetFont(
                    !empty($this->settings['font_pvprp_family']) ? $this->settings['font_pvprp_family'] : 'helvetica',
                    '',
                    (int)(!empty($this->settings['font_pvprp_size']) ? $this->settings['font_pvprp_size'] : 12)
                );
                $this->text(
                    $pdf,
                    $mmx((float)$this->settings['pos_pvprp_x' . $slot]),
                    $mmy((float)$this->settings['pos_pvprp_y' . $slot]),
                    $this->formatEuro((string)$data['pvprp']),
                    !empty($this->settings['color_pvprp']) ? $this->settings['color_pvprp'] : '#000000',
                    false
                );

                // PVP
                $pdf->SetFont(
                    !empty($this->settings['font_pvp_family']) ? $this->settings['font_pvp_family'] : 'helvetica',
                    '',
                    (int)(!empty($this->settings['font_pvp_size']) ? $this->settings['font_pvp_size'] : 12)
                );

                $this->text(
                    $pdf,
                    $mmx((float)$this->settings['pos_pvp_x' . $slot]),
                    $mmy((float)$this->settings['pos_pvp_y' . $slot]),
                    $this->formatEuro((string)$data['pvp']),
                    !empty($this->settings['color_pvp']) ? $this->settings['color_pvp'] : '#000000',
                    false
                );
            }
        }

        $pdf->Output($outputPdf, 'F');
        error_log('[PdfBuilder::build] FIN OK (clásico)');
    }

    /**
     * HORIZONTAL: Oficio apaisado, 8 etiquetas por página
     */
    public function buildHorizontal(array $rows, $outputPdf)
    {
        error_log('[PdfBuilder::buildHorizontal] INICIO');

        // Oficio apaisado: 340 x 216 mm
        $pageW = 340.0;
        $pageH = 216.0;

        $pdf = new TCPDF('L', 'mm', array($pageW, $pageH), true, 'UTF-8');
        $pdf->SetCreator('Alsernet');
        $pdf->SetAuthor('Alsernet');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        // canvas horizontal 1133x720 px -> 340x216 mm
        $mmx = function ($px) use ($pageW) {
            if (!is_numeric($px)) {
                error_log('[PdfBuilder::buildHorizontal] mmx valor NO numérico: ' . var_export($px, true));
                throw new \Exception('HORIZONTAL: mmx recibió un valor no numérico');
            }
            $px = (float)$px;
            return ($px * $pageW) / 1133.0;
        };
        $mmy = function ($px) use ($pageH) {
            if (!is_numeric($px)) {
                error_log('[PdfBuilder::buildHorizontal] mmy valor NO numérico: ' . var_export($px, true));
                throw new \Exception('HORIZONTAL: mmy recibió un valor no numérico');
            }
            $px = (float)$px;
            return ($px * $pageH) / 720.0;
        };

        $chunks = array_chunk($rows, 8);
        foreach ($chunks as $chunkIdx => $chunk) {
            error_log('[PdfBuilder::buildHorizontal] Página chunk #' . $chunkIdx);
            $pdf->AddPage();
            if ($this->bgPath) {
                $pdf->Image($this->bgPath, 0, 0, $pageW, $pageH, '', '', '', false, 300, '', false, false, 0);
            }

            for ($slot = 1; $slot <= 8; $slot++) {
                if (!isset($chunk[$slot - 1])) {
                    continue;
                }
                $data = $chunk[$slot - 1];
                error_log("[PdfBuilder::buildHorizontal] Slot={$slot} referencia=" . (isset($data['referencia']) ? $data['referencia'] : ''));

                // LABEL
                list($fam, $sty) = $this->fontSpec(
                    'font_label_h_family',
                    $this->settings['font_label_family'] ?? 'helvetica'
                );

                $pdf->SetFont(
                    $fam,
                    $sty,
                    $this->n('font_label_h_size', $this->n('font_label_size', 12))
                );
                $this->text(
                    $pdf,
                    $mmx($this->n("pos_label_hx{$slot}")),
                    $mmy($this->n("pos_label_hy{$slot}")),
                    (string)($this->settings['label_text'] ?? 'Precio recomendado:'),
                    $this->c('color_label'),
                    false
                );

                // REFERENCIA
                list($fam, $sty) = $this->fontSpec(
                    'font_referencia_h_family',
                    $this->settings['font_referencia_family'] ?? 'helvetica'
                );

                $pdf->SetFont(
                    $fam,
                    $sty,
                    $this->n('font_referencia_h_size', $this->n('font_referencia_size', 12))
                );
                $this->text(
                    $pdf,
                    $mmx($this->n("pos_referencia_hx{$slot}")),
                    $mmy($this->n("pos_referencia_hy{$slot}")),
                    (string)$data['referencia'],
                    $this->c('color_referencia'),
                    false
                );

                // DESCRIPCIÓN (word wrap manual centrado)
                list($fam, $sty) = $this->fontSpec(
                    'font_descripcion_h_family',
                    $this->settings['font_descripcion_family'] ?? 'helvetica'
                );

                $pdf->SetFont(
                    $fam,
                    $sty,
                    $this->n('font_descripcion_h_size', $this->n('font_descripcion_size', 12))
                );

                // ✅ Después: ancho fijo solo para horizontal (en mm)
                $anchoCajaMm = 60.0; // prueba con 50–70 para afinar

                $this->textBoxCentered(
                    $pdf,
                    $mmx($this->n("pos_descripcion_hx{$slot}")),
                    $mmy($this->n("pos_descripcion_hy{$slot}")),
                    $anchoCajaMm,
                    (string)$data['descripcion'],
                    $this->c('color_descripcion')
                );

                // PVPRP
                list($fam, $sty) = $this->fontSpec(
                    'font_pvprp_h_family',
                    $this->settings['font_pvprp_family'] ?? 'helvetica'
                );

                $pdf->SetFont(
                    $fam,
                    $sty,
                    $this->n('font_pvprp_h_size', $this->n('font_pvprp_size', 12))
                );
                $this->text(
                    $pdf,
                    $mmx($this->n("pos_pvprp_hx{$slot}")),
                    $mmy($this->n("pos_pvprp_hy{$slot}")),
                    $this->formatEuro((string)$data['pvprp']),
                    $this->c('color_pvprp'),
                    false
                );

                // PVP — centrado y con 2 decimales
                list($fam, $sty) = $this->fontSpec(
                    'font_pvp_h_family',
                    $this->settings['font_pvp_family'] ?? 'helvetica'
                );

                $pdf->SetFont(
                    $fam,
                    $sty,
                    $this->n('font_pvp_h_size', $this->n('font_pvp_size', 12))
                );

                // redondeado a 2 decimales reales
                $pvpValue = number_format((float)$data['pvp'], 2, '.', '');

                $this->textBoxCentered(
                    $pdf,
                    $mmx($this->n("pos_pvp_hx{$slot}")),
                    $mmy($this->n("pos_pvp_hy{$slot}")),
                    $mmx($this->n('box_pvp_w', 120)),   // ancho de caja para alinear centro
                    $this->formatEuro($pvpValue),
                    $this->c('color_pvp')
                );
            }
        }

        $pdf->Output($outputPdf, 'F');
        error_log('[PdfBuilder::buildHorizontal] FIN OK');
    }

    /**
     * VERTICAL nuevo: 4 por página (A4 Portrait) recibiendo bgPath explícito.
     */
    public function buildVertical(array $rows, $bgPath, $outputPdf)
    {
        error_log('[PdfBuilder::buildVertical] INICIO');
        if (!is_file($bgPath)) {
            error_log('[PdfBuilder::buildVertical] bgPath NO existe: ' . $bgPath);
            throw new \Exception('No existe la imagen base (vertical).');
        }

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Alsernet');
        $pdf->SetAuthor('Alsernet');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        // canvas 700x990 px -> 210x297 mm
        $mmx = function ($px) {
            if (!is_numeric($px)) {
                error_log('[PdfBuilder::buildVertical] mmx valor NO numérico: ' . var_export($px, true));
                throw new \Exception('VERTICAL: mmx recibió un valor no numérico');
            }
            $px = (float)$px;
            return ($px * 210.0) / 700.0;
        };
        $mmy = function ($px) {
            if (!is_numeric($px)) {
                error_log('[PdfBuilder::buildVertical] mmy valor NO numérico: ' . var_export($px, true));
                throw new \Exception('VERTICAL: mmy recibió un valor no numérico');
            }
            $px = (float)$px;
            return ($px * 297.0) / 990.0;
        };

        // ---- Tamaño de caja para DESCRIPCIÓN (VERTICAL) ----
        // Se aplica IGUAL a los 4 slots (2 arriba, 2 abajo; izq y dcha).
        // En BD está en "px" de canvas 700x990, lo convertimos a mm una sola vez.
        $boxDescWidthPx  = $this->n('box_descripcion_w', 280);  // px en canvas vertical
        $boxDescWidthMm  = $mmx($boxDescWidthPx);               // mm reales en A4
        $boxDescHeightMm = $this->n('box_descripcion_h', 30);   // alto máximo en mm (opcional, no limitamos de momento)

        // Caja PVP (para centrar precio)
        $boxPvpWidthPx = $this->n('box_pvp_w', 200);
        $boxPvpWidthMm = $mmx($boxPvpWidthPx);

        $chunks = array_chunk($rows, 4);
        foreach ($chunks as $chunkIdx => $chunk) {
            error_log('[PdfBuilder::buildVertical] Página chunk #' . $chunkIdx);
            $pdf->AddPage();
            $pdf->Image($bgPath, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);

            for ($slot = 1; $slot <= 4; $slot++) {
                if (!isset($chunk[$slot - 1])) {
                    continue;
                }
                $data = $chunk[$slot - 1];
                error_log("[PdfBuilder::buildVertical] Slot={$slot} referencia=" . (isset($data['referencia']) ? $data['referencia'] : ''));

                // ================= LABEL =================
                list($fam, $sty) = $this->fontSpec('font_label_family', 'helvetica');
                $pdf->SetFont($fam, $sty, $this->n('font_label_size', 12));
                $this->text(
                    $pdf,
                    $mmx($this->n("pos_label_x{$slot}")),
                    $mmy($this->n("pos_label_y{$slot}")),
                    (string)($this->settings['label_text'] ?? 'Precio recomendado:'),
                    $this->c('color_label'),
                    false // alineado a la izquierda respecto a X
                );

                // ================= REFERENCIA =================
                list($fam, $sty) = $this->fontSpec('font_referencia_family', 'helvetica');
                $pdf->SetFont($fam, $sty, $this->n('font_referencia_size', 12));
                $this->text(
                    $pdf,
                    $mmx($this->n("pos_referencia_x{$slot}")),
                    $mmy($this->n("pos_referencia_y{$slot}")),
                    (string)$data['referencia'],
                    $this->c('color_referencia'),
                    false
                );

                // ================= DESCRIPCIÓN (VERTICAL) =================
                list($fam, $sty) = $this->fontSpec('font_descripcion_family', 'helvetica');
                $pdf->SetFont($fam, $sty, $this->n('font_descripcion_size', 12));

                $desc = (string)$data['descripcion'];
                error_log('[PdfBuilder::buildVertical] Descripción slot ' . $slot . ': ' . mb_substr($desc, 0, 80));

                // X / Y específicos de cada slot (tal como están en BD)
                $xDesc = $mmx($this->n("pos_descripcion_x{$slot}"));
                $yDesc = $mmy($this->n("pos_descripcion_y{$slot}"));
                $boxH = 20; // o lo que estés usando de alto de caja en mm

                // Ancho de la caja en mm (el mismo para todos los slots, pero puedes cambiarlo desde box_descripcion_w)
                // $boxDescWidthPx = $this->n('box_descripcion_w', 330);  // el valor en px del canvas
                // $boxDescWidthMm = $mmx($boxDescWidthPx);

                $this->textBoxCenteredVerticalWrapped(
                    $pdf,
                    $xDesc,
                    $yDesc,
                    $boxDescWidthMm,
                    $boxH,
                    $desc,
                    $this->c('color_descripcion')
                );

                // ================= PVPRP =================
                list($fam, $sty) = $this->fontSpec('font_pvprp_family', 'helvetica');
                $pdf->SetFont($fam, $sty, $this->n('font_pvprp_size', 12));
                $this->text(
                    $pdf,
                    $mmx($this->n("pos_pvprp_x{$slot}")),
                    $mmy($this->n("pos_pvprp_y{$slot}")),
                    $this->formatEuro((string)$data['pvprp']),
                    $this->c('color_pvprp'),
                    false  // lo dejamos alineado a la izquierda como hasta ahora
                );

                // ================= PVP (centrado + 2 decimales) =================
                list($fam, $sty) = $this->fontSpec(
                    'font_pvp_family',
                    $this->settings['font_pvp_family'] ?? 'helvetica'
                );
                $pdf->SetFont($fam, $sty, $this->n('font_pvp_size', 12));

                $pvpValue = number_format((float)$data['pvp'], 2, '.', '');
                $this->textBoxCentered(
                    $pdf,
                    $mmx($this->n("pos_pvp_x{$slot}")),
                    $mmy($this->n("pos_pvp_y{$slot}")),
                    $boxPvpWidthMm,
                    $this->formatEuro($pvpValue),
                    $this->c('color_pvp')
                );
            }
        }

        $pdf->Output($outputPdf, 'F');
        error_log('[PdfBuilder::buildVertical] FIN OK');
    }


    /** -------- helpers -------- */
    private function f($k, $def = 'helvetica')
    {
        $v = isset($this->settings[$k]) ? trim((string)$this->settings[$k]) : '';
        return $v !== '' ? $v : $def;
    }

    private function n($k, $def = 0)
    {
        if (!isset($this->settings[$k]) || $this->settings[$k] === '' || $this->settings[$k] === null) {
            return (float)$def;
        }

        if (!is_numeric($this->settings[$k])) {
            error_log('[PdfBuilder::n] Valor NO numérico para clave "' . $k . '": ' . var_export($this->settings[$k], true));
            // En vez de romper, usamos el default pero dejamos rastro
            return (float)$def;
        }

        return (float)$this->settings[$k];
    }

    private function c($k, $def = '#000000')
    {
        $v = isset($this->settings[$k]) ? trim((string)$this->settings[$k]) : '';
        return $v !== '' ? $v : $def;
    }

    // Texto 1 línea, alineado según $center (pero respetando X/Y)
    private function text(TCPDF $pdf, $x, $y, $txt, $hexColor, $center = false)
    {
        list($r, $g, $b) = $this->hexToRgb($hexColor ?: '#000000');
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetXY($x, $y);

        // altura de línea razonable según font size
        $fontSizePt = $pdf->getFontSize(); // puntos
        $lineHeight = max(3.5, $fontSizePt * 0.35); // mm aprox

        $pdf->Cell(
            0, // ancho hasta margen derecho; x marca el inicio
            $lineHeight,
            trim($txt),
            0,
            1,
            $center ? 'C' : 'L',
            false,
            '',
            0,
            false,
            'T',
            $center ? 'C' : 'L'
        );
    }

    /**
     * Caja con texto centrado horizontalmente, con word-wrap manual.
     * Respeta (x, y) como esquina superior izquierda y widthMm como tope de ancho.
     */
    /**
     * Caja con texto centrado horizontalmente, con word-wrap manual.
     * Respeta (x, y) como esquina superior izquierda y widthMm como tope de ancho.
     * Añadimos más separación vertical entre líneas para que no se “peguen” al texto superior.
     */
    private function textBoxCentered(TCPDF $pdf, $x, $y, $widthMm, $txt, $hexColor)
    {
        if (!is_numeric($widthMm) || $widthMm <= 0) {
            error_log('[PdfBuilder::textBoxCentered] widthMm NO numérico o <=0: ' . var_export($widthMm, true));
            $widthMm = 50.0;
        }

        list($r, $g, $b) = $this->hexToRgb($hexColor ?: '#000000');
        $pdf->SetTextColor($r, $g, $b);

        $txt = trim((string)$txt);
        error_log('[PdfBuilder::textBoxCentered] x=' . $x . ' y=' . $y . ' widthMm=' . $widthMm . ' txt_len=' . mb_strlen($txt));

        if ($txt === '') {
            return;
        }

        // --- Word-wrap manual (como ya tenías) ---
        $words   = preg_split('/\s+/', $txt);
        $lines   = [];
        $current = '';

        foreach ($words as $w) {
            $candidate = ($current === '') ? $w : ($current . ' ' . $w);
            $wWidth = $pdf->GetStringWidth($candidate);

            if ($wWidth <= $widthMm) {
                $current = $candidate;
            } else {
                if ($current === '') {
                    // Palabra más larga que la caja: la dejamos sola
                    $lines[] = $w;
                    $current = '';
                } else {
                    $lines[] = $current;
                    $current = $w;
                }
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        // --- Ajuste anti-huérfanas (igual filosofía que en vertical) ---
        if (count($lines) >= 2) {
            $lastIdx  = count($lines) - 1;
            $lastLine = $lines[$lastIdx];

            // Si la última línea tiene una sola palabra…
            if (strpos($lastLine, ' ') === false) {
                $prevIdx   = $lastIdx - 1;
                $prevLine  = $lines[$prevIdx];
                $prevWords = explode(' ', $prevLine);

                // Solo tiene sentido si la línea anterior tiene >1 palabra
                if (count($prevWords) > 1) {
                    // Movemos la ÚLTIMA palabra de la línea anterior a la última
                    $movedWord      = array_pop($prevWords);
                    $candidatePrev  = implode(' ', $prevWords);
                    $candidateLast  = $movedWord . ' ' . $lastLine;

                    $wPrev = $pdf->GetStringWidth($candidatePrev);
                    $wLast = $pdf->GetStringWidth($candidateLast);

                    // Solo aplicamos si ambas caben dentro del ancho de la caja
                    if ($wPrev <= $widthMm && $wLast <= $widthMm) {
                        $lines[$prevIdx] = $candidatePrev;
                        $lines[$lastIdx] = $candidateLast;
                    }
                }
            }
        }

        // --- Pintado (igual que antes) ---
        $fontSizePt = $pdf->getFontSize();    // tamaño de fuente en pt
        $lineHeight = max(4.5, $fontSizePt * 1); // separación entre líneas

        // pequeño offset para despegar la primera línea del texto de arriba
        $yBase = $y + ($lineHeight * 0.2);

        $lineNo = 0;
        foreach ($lines as $line) {
            $pdf->SetXY($x, $yBase + $lineNo * $lineHeight);
            $pdf->Cell(
                (float)$widthMm,
                $lineHeight,
                $line,
                0,
                0,
                'C',
                false,
                '',
                0,
                false,
                'T',
                'C'
            );
            $lineNo++;
        }
    }


    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        return array(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        );
    }

    private function formatEuro($raw)
    {
        $s = trim(str_replace(array('€', 'EUR', 'eur', 'Eur'), '', (string)$raw));

        // Formato europeo 1.234,56
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }

        // Formato USA 1,234.56
        if (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $s)) {
            $s = str_replace(',', '', $s);
        }

        if ($s === '' || !is_numeric($s)) {
            error_log('[PdfBuilder::formatEuro] Valor NO numérico tras limpiar. raw=' . var_export($raw, true) . ' s=' . var_export($s, true));
            $num = 0.0;
        } else {
            $num = (float)$s;
        }

        if (fmod($num, 1.0) != 0.0) {
            $formatted = number_format($num, 2, ',', '.');
        } else {
            $formatted = number_format($num, 0, ',', '.');
        }

        return $formatted . '€';
    }

    private function fontSpec($key, $defFamily = 'helvetica')
    {
        // Valor crudo que viene de la BD (ej: 'helveticaB', 'dejavusansbi', etc.)
        $val = isset($this->settings[$key]) ? trim((string)$this->settings[$key]) : '';

        if ($val === '') {
            $val = $defFamily;
        }

        $valLower = strtolower($val);

        // Familias base conocidas en TCPDF
        $baseFamilies = array(
            'helvetica',
            'times',
            'courier',
            'dejavusans',
            'dejavuserif',
            'freesans',
            'freeserif'
        );

        foreach ($baseFamilies as $base) {
            $baseLen = strlen($base);

            // Coincide exactamente con la familia: 'helvetica'
            if ($valLower === $base) {
                return array($base, '');
            }

            // Empieza por la familia: 'helveticaB', 'dejavusansbi', etc.
            if (substr($valLower, 0, $baseLen) === $base) {
                $suffix = substr($valLower, $baseLen); // lo que hay después de la familia

                switch ($suffix) {
                    case 'b':
                        return array($base, 'B');
                    case 'i':
                        return array($base, 'I');
                    case 'bi':
                    case 'ib':
                        return array($base, 'BI');
                    default:
                        // Si el sufijo es raro, devolvemos tal cual como familia sin estilo
                        return array($valLower, '');
                }
            }
        }

        // Si no encaja en nada de lo anterior, usar el valor tal cual como familia sin estilo
        return array($valLower, '');
    }

    /**
     * Caja con texto centrado y word-wrap SOLO para la descripción VERTICAL.
     * (x, y) = esquina superior izquierda de la caja en mm.
     */
    private function textBoxCenteredVerticalWrapped(TCPDF $pdf, $x, $y, $widthMm, $heightMm, $txt, $hexColor)
    {
        // Seguridad: ancho/alto válidos
        if (!is_numeric($widthMm) || $widthMm <= 0) {
            error_log('[PdfBuilder::textBoxCenteredVerticalWrapped] widthMm NO válido: ' . var_export($widthMm, true));
            $widthMm = 50.0;
        }
        if (!is_numeric($heightMm) || $heightMm <= 0) {
            error_log('[PdfBuilder::textBoxCenteredVerticalWrapped] heightMm NO válido: ' . var_export($heightMm, true));
            $heightMm = 20.0;
        }

        // Color
        list($r, $g, $b) = $this->hexToRgb($hexColor ?: '#000000');
        $pdf->SetTextColor($r, $g, $b);

        // Normalizar texto
        $txt = trim(preg_replace('/\s+/', ' ', (string)$txt));
        if ($txt === '') {
            return;
        }

        // Altura de línea
        $lineHeight = 7; // aquí puedes subir/bajar si quieres más/menos separación

        $words   = explode(' ', $txt);
        $lines   = [];
        $current = '';

        // Word-wrap manual respetando el ancho de la caja
        foreach ($words as $word) {
            $test = ($current === '') ? $word : ($current . ' ' . $word);
            $w = $pdf->GetStringWidth($test);

            if ($w <= $widthMm) {
                $current = $test;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = $word;
                } else {
                    // Palabra más larga que la caja: la dejamos sola en una línea
                    $lines[] = $word;
                    $current = '';
                }
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        // --- Ajuste anti-huérfanas: si la ÚLTIMA línea tiene solo 1 palabra,
        // intentamos mover una palabra desde la línea anterior, siempre
        // respetando el ancho máximo de la caja.
        if (count($lines) >= 2) {
            $lastIdx = count($lines) - 1;
            $lastLine = $lines[$lastIdx];

            // ¿última línea con una sola palabra?
            if (strpos($lastLine, ' ') === false) {
                $prevIdx   = $lastIdx - 1;
                $prevLine  = $lines[$prevIdx];
                $prevWords = explode(' ', $prevLine);

                if (count($prevWords) > 1) {
                    // intentamos pasar la ÚLTIMA palabra de la línea anterior a la última
                    $movedWord = array_pop($prevWords);
                    $candidatePrev = implode(' ', $prevWords);
                    $candidateLast = $movedWord . ' ' . $lastLine;

                    $wPrev = $pdf->GetStringWidth($candidatePrev);
                    $wLast = $pdf->GetStringWidth($candidateLast);

                    // solo aplicamos si ambos siguen cabiendo en la caja
                    if ($wPrev <= $widthMm && $wLast <= $widthMm) {
                        $lines[$prevIdx] = $candidatePrev;
                        $lines[$lastIdx] = $candidateLast;
                    }
                }
            }
        }

        $numLines    = count($lines);
        $totalHeight = $numLines * $lineHeight;

        // Centramos verticalmente dentro de la altura de la caja
        $startY = $y + max(0, ($heightMm - $totalHeight) / 2.0);

        error_log(
            '[PdfBuilder::textBoxCenteredVerticalWrapped] x=' . $x .
                ' y=' . $y .
                ' startY=' . $startY .
                ' widthMm=' . $widthMm .
                ' heightMm=' . $heightMm .
                ' lines=' . $numLines
        );

        // Dibujamos líneas controlando X e Y a mano (como antes)
        $currentY = $startY;
        foreach ($lines as $line) {
            $pdf->SetXY($x, $currentY);  // SIEMPRE empezamos la línea en la X de la caja
            $pdf->Cell(
                $widthMm,       // ancho de la caja
                $lineHeight,    // alto de línea
                $line,
                0,
                0,              // ln = 0 (NO cambiamos línea automáticamente)
                'C',            // centrado horizontal
                false,
                '',
                0,
                false,
                'M',
                'C'
            );
            $currentY += $lineHeight;    // bajamos manualmente a la siguiente línea
        }
    }


    /**
     * Envuelve texto en varias líneas respetando un ancho máximo en mm,
     * usando la fuente actual del TCPDF.
     * Aplica una corrección para evitar que la última línea quede con
     * una sola palabra (huérfana) cuando sea posible.
     *
     * IMPORTANTE: NO toca la posición Y de inicio, solo devuelve líneas.
     */
    private function wrapTextAvoidOrphan(TCPDF $pdf, $text, $maxWidthMm)
    {
        $text = trim((string)$text);
        if ($text === '') {
            return [];
        }

        $words = preg_split('/\s+/', $text);
        if (!$words) {
            return [$text];
        }

        $lines = [];
        $current = '';

        foreach ($words as $w) {
            $candidate = ($current === '') ? $w : ($current . ' ' . $w);
            $wmm = $pdf->GetStringWidth($candidate);

            if ($wmm <= $maxWidthMm) {
                $current = $candidate;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $w;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        // --- corrección de huérfana: si la última línea tiene 1 palabra
        // y la anterior tiene más de 1, movemos una palabra.
        if (count($lines) > 1) {
            $lastIdx = count($lines) - 1;
            $lastWords = preg_split('/\s+/', $lines[$lastIdx]);

            if (count($lastWords) === 1) {
                $prevIdx = $lastIdx - 1;
                $prevWords = preg_split('/\s+/', $lines[$prevIdx]);

                if (count($prevWords) > 1) {
                    $moving = array_pop($prevWords);
                    $lines[$prevIdx] = implode(' ', $prevWords);
                    $lines[$lastIdx] = $moving . ' ' . $lines[$lastIdx];
                    // No recalculamos anchura para no complicar:
                    // visualmente suele ir bien.
                }
            }
        }

        return $lines;
    }

    /**
     * Dibuja varias líneas centradas horizontalmente dentro de una caja,
     * ANCLADAS ARRIBA (no centradas verticalmente).
     *
     * Se usa SOLO para la descripción VERTICAL.
     */
    private function drawVerticalWrappedTopAligned(
        TCPDF $pdf,
        $xMm,
        $yMm,
        $boxWidthMm,
        $text,
        $hexColor
    ) {
        list($r, $g, $b) = $this->hexToRgb($hexColor ?: '#000000');
        $pdf->SetTextColor($r, $g, $b);

        $lines = $this->wrapTextAvoidOrphan($pdf, $text, $boxWidthMm);
        if (empty($lines)) {
            return;
        }

        // ALTURA DE LÍNEA: aquí estaba el problema
        $lineHeight = 8; // mm (ajusta 6 / 6.5 / 7 según veas)

        $pdf->SetXY($xMm, $yMm);

        foreach ($lines as $line) {
            $pdf->Cell(
                $boxWidthMm,
                $lineHeight,
                $line,
                0,
                1,      // siguiente línea
                'C',    // centrado
                false,
                '',
                0,
                false,
                'T',
                'C'
            );
        }
    }
}
