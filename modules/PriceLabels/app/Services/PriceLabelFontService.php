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

        $font->delete();
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
}
