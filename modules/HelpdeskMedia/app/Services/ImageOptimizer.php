<?php

namespace Modules\HelpdeskMedia\Services;

/**
 * Reencoda una imagen: autorrota por EXIF, descarta TODOS los metadatos (GPS
 * incluido), reduce el lado largo a max_px y escribe JPG progresivo (sin
 * transparencia) o PNG comprimido (con canal alfa). Imagick si está (HEIC,
 * AVIF, TIFF…), GD si no. Escribe en un temporal; quien llama decide si
 * sustituye al original.
 */
class ImageOptimizer
{
    /** Formatos que cualquier cliente/canal muestra sin problema. */
    private const COMPATIBLE = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const MULTI_FRAME = ['image/gif', 'image/webp', 'image/avif'];

    private const MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/x-ms-bmp',
        'image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence',
        'image/avif', 'image/tiff',
    ];

    public function supportsMime(string $mime): bool
    {
        return in_array($mime, self::MIMES, true);
    }

    public function usesImagick(): bool
    {
        $driver = config('helpdeskmedia.images.driver', 'auto');

        if ($driver === 'gd') {
            return false;
        }

        return extension_loaded('imagick');
    }

    public function optimize(string $sourcePath, string $mime): ImageResult
    {
        $incompatible = ! in_array($mime, self::COMPATIBLE, true);

        try {
            return $this->usesImagick()
                ? $this->withImagick($sourcePath, $mime, $incompatible)
                : $this->withGd($sourcePath, $mime, $incompatible);
        } catch (\Throwable $e) {
            return new ImageResult(ImageResult::FAILED, sourceIncompatible: $incompatible, reason: $e->getMessage());
        }
    }

    private function withImagick(string $path, string $mime, bool $incompatible): ImageResult
    {
        $maxPx = (int) config('helpdeskmedia.images.max_px', 2048);
        $quality = (int) config('helpdeskmedia.images.quality', 82);

        $probe = new \Imagick;
        $probe->setResourceLimit(\Imagick::RESOURCETYPE_AREA, (int) config('helpdeskmedia.images.max_pixels', 50000000));
        $probe->pingImage($path);
        $frames = $probe->getNumberImages();
        $probe->clear();

        if ($frames > 1 && in_array($mime, self::MULTI_FRAME, true)) {
            return new ImageResult(ImageResult::ANIMATED, sourceIncompatible: $incompatible);
        }

        $image = new \Imagick;
        $image->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 268435456);
        $image->setResourceLimit(\Imagick::RESOURCETYPE_AREA, (int) config('helpdeskmedia.images.max_pixels', 50000000));

        try {
            // [0]: primer fotograma (TIFF multipágina, HEIC con ráfaga…).
            $image->readImage($path.'[0]');

            $hadGps = $image->getImageProperties('exif:GPS*', false) !== [];

            $this->orientImagick($image);

            if ($image->getImageColorspace() === \Imagick::COLORSPACE_CMYK) {
                $image->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
            }

            if (max($image->getImageWidth(), $image->getImageHeight()) > $maxPx) {
                $image->thumbnailImage($maxPx, $maxPx, true);
            }

            $image->stripImage();

            $hasAlpha = (bool) $image->getImageAlphaChannel();

            if ($hasAlpha) {
                $format = 'png';
                $image->setImageFormat('png');
                $image->setOption('png:compression-level', '9');
            } else {
                $format = 'jpeg';
                $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $image->setImageFormat('jpeg');
                $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality($quality);
                $image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
            }

            $temp = $this->tempFile();
            $image->writeImage($format.':'.$temp);

            return new ImageResult(
                ImageResult::OPTIMIZED,
                tempPath: $temp,
                mime: $hasAlpha ? 'image/png' : 'image/jpeg',
                extension: $hasAlpha ? 'png' : 'jpg',
                width: $image->getImageWidth(),
                height: $image->getImageHeight(),
                bytes: (int) filesize($temp),
                sourceIncompatible: $incompatible,
                sourceHadGps: $hadGps,
            );
        } finally {
            $image->clear();
        }
    }

    private function orientImagick(\Imagick $image): void
    {
        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();

            return;
        }

        match ($image->getImageOrientation()) {
            \Imagick::ORIENTATION_BOTTOMRIGHT => $image->rotateImage('#000', 180),
            \Imagick::ORIENTATION_RIGHTTOP => $image->rotateImage('#000', 90),
            \Imagick::ORIENTATION_LEFTBOTTOM => $image->rotateImage('#000', -90),
            default => null,
        };

        $image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
    }

    private function withGd(string $path, string $mime, bool $incompatible): ImageResult
    {
        $loader = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/gif' => 'imagecreatefromgif',
            'image/webp' => 'imagecreatefromwebp',
            'image/bmp', 'image/x-ms-bmp' => 'imagecreatefrombmp',
            'image/avif' => 'imagecreatefromavif',
            default => null,
        };

        // HEIC/HEIF/TIFF sin Imagick: se deja el original.
        if ($loader === null || ! function_exists($loader)) {
            return new ImageResult(ImageResult::UNSUPPORTED, sourceIncompatible: $incompatible, reason: 'no_decoder');
        }

        if ($this->isAnimatedWithoutImagick($path, $mime)) {
            return new ImageResult(ImageResult::ANIMATED, sourceIncompatible: $incompatible);
        }

        $size = @getimagesize($path);

        if ($size === false) {
            return new ImageResult(ImageResult::FAILED, sourceIncompatible: $incompatible, reason: 'unreadable');
        }

        if ((int) config('helpdeskmedia.images.max_pixels', 50000000) < $size[0] * $size[1]) {
            return new ImageResult(ImageResult::FAILED, sourceIncompatible: $incompatible, reason: 'too_many_pixels');
        }

        $hadGps = $mime === 'image/jpeg' && $this->jpegHasGps($path);
        $orientation = $mime === 'image/jpeg' ? $this->jpegOrientation($path) : 1;

        $image = @$loader($path);

        if ($image === false) {
            return new ImageResult(ImageResult::FAILED, sourceIncompatible: $incompatible, reason: 'decode_failed');
        }

        imagepalettetotruecolor($image);
        $image = $this->orientGd($image, $orientation);
        $image = $this->resizeGd($image, (int) config('helpdeskmedia.images.max_px', 2048));

        $hasAlpha = $this->gdHasAlpha($image);
        $temp = $this->tempFile();

        if ($hasAlpha) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, $temp, 9);
        } else {
            $image = $this->flattenOnWhite($image);
            imageinterlace($image, true);
            imagejpeg($image, $temp, (int) config('helpdeskmedia.images.quality', 82));
        }

        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        return new ImageResult(
            ImageResult::OPTIMIZED,
            tempPath: $temp,
            mime: $hasAlpha ? 'image/png' : 'image/jpeg',
            extension: $hasAlpha ? 'png' : 'jpg',
            width: $width,
            height: $height,
            bytes: (int) filesize($temp),
            sourceIncompatible: $incompatible,
            sourceHadGps: $hadGps,
        );
    }

    private function isAnimatedWithoutImagick(string $path, string $mime): bool
    {
        if (! in_array($mime, ['image/gif', 'image/webp'], true)) {
            return false;
        }

        $head = (string) file_get_contents($path, false, null, 0, 1024 * 1024);

        if ($mime === 'image/webp') {
            return str_contains($head, 'ANIM');
        }

        // Cada fotograma GIF lleva una extensión de control gráfico (21 F9 04).
        return substr_count($head, "\x00\x21\xF9\x04") > 1;
    }

    private function jpegHasGps(string $path): bool
    {
        if (! function_exists('exif_read_data')) {
            return false;
        }

        $exif = @exif_read_data($path, 'GPS');

        return is_array($exif) && isset($exif['GPSLatitude']);
    }

    private function jpegOrientation(string $path): int
    {
        if (! function_exists('exif_read_data')) {
            return 1;
        }

        $exif = @exif_read_data($path, 'IFD0');

        return is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
    }

    private function orientGd(\GdImage $image, int $orientation): \GdImage
    {
        $flip = static function (\GdImage $img, int $mode): \GdImage {
            imageflip($img, $mode);

            return $img;
        };
        $rotate = static fn (\GdImage $img, int $degrees): \GdImage => imagerotate($img, $degrees, imagecolorallocatealpha($img, 0, 0, 0, 127)) ?: $img;

        return match ($orientation) {
            2 => $flip($image, IMG_FLIP_HORIZONTAL),
            3 => $rotate($image, 180),
            4 => $flip($image, IMG_FLIP_VERTICAL),
            5 => $flip($rotate($image, 90), IMG_FLIP_VERTICAL),
            6 => $rotate($image, -90),
            7 => $flip($rotate($image, -90), IMG_FLIP_VERTICAL),
            8 => $rotate($image, 90),
            default => $image,
        };
    }

    private function resizeGd(\GdImage $image, int $maxPx): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) <= $maxPx) {
            return $image;
        }

        $ratio = $maxPx / max($width, $height);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /**
     * Muestrea una rejilla de ~200x200 puntos: si alguno tiene alfa > 0 hay
     * transparencia real. Un PNG con canal alfa totalmente opaco (capturas de
     * pantalla) cuenta como sin transparencia y acaba en JPG.
     */
    private function gdHasAlpha(\GdImage $image): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $stepX = max(1, intdiv($width, 200));
        $stepY = max(1, intdiv($height, 200));

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function flattenOnWhite(\GdImage $image): \GdImage
    {
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);

        return $canvas;
    }

    private function tempFile(): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'hdimg_');

        if ($temp === false) {
            throw new \RuntimeException('No se puede crear un fichero temporal');
        }

        return $temp;
    }
}
