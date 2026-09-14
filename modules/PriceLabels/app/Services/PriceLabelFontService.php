<?php

namespace Modules\PriceLabels\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\PriceLabels\Models\PriceLabelFont;

class PriceLabelFontService
{
    private const DISK = 'public';

    private const FOLDER = 'pricelabels/fonts';

    /**
     * Familias Base-14 que DomPDF resuelve sin embeber ningun fichero.
     *
     * @var array<string, string>
     */
    public const BUILTIN_STACKS = [
        'helvetica' => 'Helvetica, Arial, sans-serif',
        'times' => '"Times New Roman", Times, serif',
        'courier' => '"Courier New", Courier, monospace',
    ];

    /**
     * @var array<string, string>
     */
    public const BUILTIN_LABELS = [
        'helvetica' => 'Helvetica',
        'times' => 'Times',
        'courier' => 'Courier',
    ];

    private ?Collection $cachedFonts = null;

    public function store(array $data, UploadedFile $file): PriceLabelFont
    {
        return PriceLabelFont::query()->create([
            'name' => $data['name'],
            'family' => $data['family'],
            'weight' => $data['weight'],
            'style' => $data['style'],
            'file_path' => $file->store(self::FOLDER, self::DISK),
            'created_by' => auth()->id(),
        ]);
    }

    public function delete(PriceLabelFont $font): void
    {
        if ($font->file_path && Storage::disk(self::DISK)->exists($font->file_path)) {
            Storage::disk(self::DISK)->delete($font->file_path);
        }

        $family = $font->family;
        $font->delete();

        $this->forgetDompdfFont($family);
    }

    /**
     * Borra la familia del registro de fuentes de DomPDF (installed-fonts.json).
     *
     * Sin esto, al eliminar o reemplazar el fichero de una fuente el registro
     * sigue apuntando al .ttf viejo: DomPDF cree que la fuente esta instalada,
     * no encuentra el fichero y cae en Helvetica SIN AVISAR, con lo que el PDF
     * sale con otra tipografia (y otro ancho de texto) que el editor.
     */
    private function forgetDompdfFont(string $family): void
    {
        $registry = rtrim((string) config('dompdf.options.font_dir', storage_path('fonts')), '/')
            .'/installed-fonts.json';

        if (! is_file($registry) || ! is_writable($registry)) {
            return;
        }

        $fonts = json_decode((string) file_get_contents($registry), true);

        if (! is_array($fonts) || ! array_key_exists($family, $fonts)) {
            return;
        }

        unset($fonts[$family]);

        file_put_contents($registry, json_encode($fonts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Fuentes personalizadas agrupadas por familia, ordenadas para mostrar.
     */
    public function all(): Collection
    {
        return $this->cachedFonts ??= PriceLabelFont::query()
            ->orderBy('name')
            ->orderBy('weight')
            ->orderBy('style')
            ->get();
    }

    /**
     * Opciones del desplegable de fuentes: Base-14 + personalizadas.
     *
     * @return array<string, string>
     */
    public function familyOptions(): array
    {
        $custom = $this->all()
            ->unique('family')
            ->mapWithKeys(fn (PriceLabelFont $font) => [$font->family => $font->name.' (personalizada)'])
            ->all();

        return self::BUILTIN_LABELS + $custom;
    }

    /**
     * Claves validas para `fields.*.font_family`.
     *
     * @return array<int, string>
     */
    public function allowedFamilies(): array
    {
        return array_keys($this->familyOptions());
    }

    /**
     * Stacks CSS por familia, usados por el PDF y por la previsualizacion del editor.
     *
     * @return array<string, string>
     */
    public function cssStacks(): array
    {
        $custom = $this->all()
            ->unique('family')
            ->mapWithKeys(fn (PriceLabelFont $font) => [$font->family => "'{$font->family}', sans-serif"])
            ->all();

        return self::BUILTIN_STACKS + $custom;
    }

    /**
     * Bloques @font-face para las fuentes subidas.
     *
     * DomPDF corre con `enable_remote => false`, asi que el PDF necesita rutas
     * absolutas `file://` (dentro del chroot = base_path). El navegador, en
     * cambio, necesita la URL publica.
     */
    public function fontFaceCss(bool $forPdf): string
    {
        return $this->all()
            ->map(function (PriceLabelFont $font) use ($forPdf): string {
                if ($forPdf) {
                    // DomPDF lee el fichero directo del disco (file://), sin
                    // capa de cache HTTP de por medio: no hace falta cache-busting.
                    $src = 'file://'.Storage::disk(self::DISK)->path($font->file_path);
                } else {
                    // El storage sirve estos ficheros con Cache-Control
                    // "immutable" de 1 anyo. Si se reemplaza el archivo de una
                    // fuente ya usada (p.ej. tras recortarla con subsetting),
                    // el navegador jamas vuelve a pedirlo sin este `?v=`: se
                    // quedaria sirviendo la version vieja en cache para
                    // siempre, aunque el usuario fuerce un hard refresh.
                    $version = Storage::disk(self::DISK)->exists($font->file_path)
                        ? Storage::disk(self::DISK)->lastModified($font->file_path)
                        : 0;
                    $src = Storage::disk(self::DISK)->url($font->file_path).'?v='.$version;
                }

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
}
