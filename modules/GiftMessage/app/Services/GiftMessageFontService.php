<?php

namespace Modules\GiftMessage\Services;

use FontLib\Font;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\GiftMessage\Models\GiftMessageFont;

class GiftMessageFontService
{
    private const DISK = 'public';

    private const FOLDER = 'giftmessage/fonts';

    /**
     * Familias que DomPDF resuelve sin embeber ningun fichero. Las dos DejaVu se
     * mantienen porque son las que cubren los emojis y los acentos raros de los
     * mensajes regalo.
     *
     * @var array<string, string>
     */
    public const BUILTIN_STACKS = [
        'helvetica' => 'Helvetica, Arial, sans-serif',
        'times' => '"Times New Roman", Times, serif',
        'courier' => '"Courier New", Courier, monospace',
        'dejavusans' => '"DejaVu Sans", sans-serif',
        'dejavuserif' => '"DejaVu Serif", serif',
    ];

    /**
     * @var array<string, string>
     */
    public const BUILTIN_LABELS = [
        'helvetica' => 'Helvetica',
        'times' => 'Times',
        'courier' => 'Courier',
        'dejavusans' => 'DejaVu Sans (Unicode)',
        'dejavuserif' => 'DejaVu Serif (Unicode)',
    ];

    /**
     * Ficheros de las familias del sistema que si traen tabla de caracteres. Las
     * Base-14 (helvetica/times/courier) no tienen fichero: DomPDF las codifica
     * en WinAnsi, ver WIN_ANSI_EXTRAS.
     *
     * @var array<string, string>
     */
    private const BUILTIN_FILES = [
        'dejavusans' => 'lib/fonts/DejaVuSans.ttf',
        'dejavuserif' => 'lib/fonts/DejaVuSerif.ttf',
    ];

    /** @var array<int, string> */
    private const WIN_ANSI_FAMILIES = ['helvetica', 'times', 'courier'];

    /**
     * Lo que WinAnsi (cp1252) anade por encima de Latin-1: sin esta lista, un
     * simple "…" o "€" contaria como no imprimible en Helvetica y cambiaria la
     * fuente del mensaje sin necesidad.
     *
     * @var array<int, int>
     */
    private const WIN_ANSI_EXTRAS = [
        0x20AC, 0x201A, 0x0192, 0x201E, 0x2026, 0x2020, 0x2021, 0x02C6, 0x2030,
        0x0160, 0x2039, 0x0152, 0x017D, 0x2018, 0x2019, 0x201C, 0x201D, 0x2022,
        0x2013, 0x2014, 0x02DC, 0x2122, 0x0161, 0x203A, 0x0153, 0x017E, 0x0178,
    ];

    private ?Collection $cachedFonts = null;

    /** @var array<string, array<int, bool>|null> */
    private array $characterMaps = [];

    /**
     * @param  array{name: string, family: string, weight: string, style: string}  $data
     */
    public function store(array $data, UploadedFile $file): GiftMessageFont
    {
        // Nombre con extension explicita: store() la deduce del MIME detectado y
        // en un TTF/OTF suele salir vacio, dejando el fichero sin extension. De
        // ahi dependen tanto el format() del @font-face como el MIME con el que
        // el navegador recibe la fuente en la vista previa del editor.
        $extension = strtolower($file->getClientOriginalExtension() ?: 'ttf');
        $path = $file->storeAs(self::FOLDER, Str::random(40).'.'.$extension, self::DISK);

        return GiftMessageFont::query()->create([
            'name' => $data['name'],
            'family' => $data['family'],
            'weight' => $data['weight'],
            'style' => $data['style'],
            'file_path' => $path,
            'created_by' => auth()->id(),
        ]);
    }

    public function delete(GiftMessageFont $font): void
    {
        if ($font->file_path && Storage::disk(self::DISK)->exists($font->file_path)) {
            Storage::disk(self::DISK)->delete($font->file_path);
        }

        $font->delete();
    }

    /**
     * @return Collection<int, GiftMessageFont>
     */
    public function all(): Collection
    {
        return $this->cachedFonts ??= GiftMessageFont::query()
            ->orderBy('name')
            ->orderBy('weight')
            ->orderBy('style')
            ->get();
    }

    /**
     * Opciones del desplegable de fuentes: las del sistema mas las subidas.
     *
     * @return array<string, string>
     */
    public function familyOptions(): array
    {
        $custom = $this->all()
            ->unique('family')
            ->mapWithKeys(fn (GiftMessageFont $font) => [$font->family => $font->name.' (personalizada)'])
            ->all();

        return self::BUILTIN_LABELS + $custom;
    }

    /**
     * Claves validas para los campos `*_font` de la configuracion.
     *
     * @return array<int, string>
     */
    public function allowedFamilies(): array
    {
        return array_keys($this->familyOptions());
    }

    /**
     * Stacks CSS por familia, para el PDF y para la previsualizacion del editor.
     *
     * @return array<string, string>
     */
    public function cssStacks(): array
    {
        $custom = $this->all()
            ->unique('family')
            ->mapWithKeys(fn (GiftMessageFont $font) => [$font->family => "'{$font->family}', sans-serif"])
            ->all();

        return self::BUILTIN_STACKS + $custom;
    }

    /**
     * Bloques @font-face de las fuentes subidas.
     *
     * DomPDF corre con `enable_remote => false`, asi que el PDF necesita rutas
     * absolutas `file://`. El navegador, en cambio, necesita la URL publica.
     */
    public function fontFaceCss(bool $forPdf): string
    {
        return $this->all()
            ->map(function (GiftMessageFont $font) use ($forPdf): string {
                $src = $forPdf
                    ? 'file://'.Storage::disk(self::DISK)->path($font->file_path)
                    : Storage::disk(self::DISK)->url($font->file_path);

                $format = str_ends_with(strtolower($font->file_path), '.otf') ? 'opentype' : 'truetype';

                return <<<CSS
                @font-face {
                    font-family: '{$font->family}';
                    font-weight: {$font->weight};
                    font-style: {$font->style};
                    src: url('{$src}') format('{$format}');
                }
                CSS;
            })
            ->implode("\n");
    }

    /**
     * Si la familia sabe pintar ese caracter. Sirve para no imprimir cuadros en
     * silencio: DomPDF no avisa de un glifo que le falta, lo dibuja como caja
     * vacia (los caracteres chinos con DejaVu Sans) o como "?".
     */
    public function supportsCodepoint(string $family, int $codepoint): bool
    {
        if (in_array($family, self::WIN_ANSI_FAMILIES, true)) {
            return $codepoint <= 0xFF || in_array($codepoint, self::WIN_ANSI_EXTRAS, true);
        }

        $map = $this->characterMap($family);

        // Sin tabla de caracteres no se puede afirmar que falte: se da por bueno
        // para no cambiar de fuente ni avisar por una lectura fallida.
        return $map === null || isset($map[$codepoint]);
    }

    /**
     * Familias subidas capaces de pintar todos esos caracteres, en el orden en
     * que se ofrecen al usuario.
     *
     * @param  array<int, int>  $codepoints
     * @return array<int, string>
     */
    public function familiesSupporting(array $codepoints): array
    {
        $families = [];

        foreach ($this->all()->pluck('family')->unique() as $family) {
            foreach ($codepoints as $codepoint) {
                if (! $this->supportsCodepoint($family, $codepoint)) {
                    continue 2;
                }
            }

            $families[] = $family;
        }

        return $families;
    }

    /**
     * Tabla de caracteres de la familia, o null si no se puede leer. Se cachea
     * en memoria porque leerla cuesta (~70 ms en una fuente china de 10 MB) y
     * un lote de PDF la consulta una vez por pedido.
     *
     * @return array<int, bool>|null
     */
    private function characterMap(string $family): ?array
    {
        if (array_key_exists($family, $this->characterMaps)) {
            return $this->characterMaps[$family];
        }

        $path = $this->fontFilePath($family);

        if ($path === null || ! is_file($path)) {
            return $this->characterMaps[$family] = null;
        }

        try {
            $font = Font::load($path);

            if ($font === null) {
                return $this->characterMaps[$family] = null;
            }

            $font->parse();
            $map = $font->getUnicodeCharMap();
            $font->close();
        } catch (\Throwable $e) {
            Log::warning('GiftMessage: no se pudo leer la tabla de caracteres de la fuente.', [
                'family' => $family,
                'error' => $e->getMessage(),
            ]);

            return $this->characterMaps[$family] = null;
        }

        return $this->characterMaps[$family] = is_array($map)
            ? array_fill_keys(array_keys($map), true)
            : null;
    }

    private function fontFilePath(string $family): ?string
    {
        if (isset(self::BUILTIN_FILES[$family])) {
            return base_path('vendor/dompdf/dompdf/'.self::BUILTIN_FILES[$family]);
        }

        $font = $this->all()->firstWhere('family', $family);

        return $font ? Storage::disk(self::DISK)->path($font->file_path) : null;
    }
}
