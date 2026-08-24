<?php

namespace Modules\GiftMessage\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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

    private ?Collection $cachedFonts = null;

    /**
     * @param  array{name: string, family: string, weight: string, style: string}  $data
     */
    public function store(array $data, UploadedFile $file): GiftMessageFont
    {
        return GiftMessageFont::query()->create([
            'name' => $data['name'],
            'family' => $data['family'],
            'weight' => $data['weight'],
            'style' => $data['style'],
            'file_path' => $file->store(self::FOLDER, self::DISK),
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
}
