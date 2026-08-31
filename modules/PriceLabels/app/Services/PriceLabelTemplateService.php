<?php

namespace Modules\PriceLabels\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\PriceLabels\Models\PriceLabelTemplate;

class PriceLabelTemplateService
{
    private const IMAGE_DISK = 'public';

    private const IMAGE_FOLDER = 'pricelabels/backgrounds';

    private const CANVAS_DIM = [
        'vertical' => ['w' => 700, 'h' => 990],
        'horizontal' => ['w' => 1133, 'h' => 720],
    ];

    public function list(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return PriceLabelTemplate::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data, ?UploadedFile $imageVertical, ?UploadedFile $imageHorizontal): PriceLabelTemplate
    {
        $data['field_definitions'] = $data['field_definitions'] ?? $this->defaultFieldDefinitions();
        $data['fields'] = $this->castFieldBooleans(array_replace_recursive($this->defaultFields($data['field_definitions']), $data['fields'] ?? []));
        $data['vertical_rows'] = $data['vertical_rows'] ?? 2;
        $data['vertical_columns'] = $data['vertical_columns'] ?? 2;
        $data['horizontal_rows'] = $data['horizontal_rows'] ?? 2;
        $data['horizontal_columns'] = $data['horizontal_columns'] ?? 4;

        $transient = new PriceLabelTemplate($data);
        $data['positions_vertical'] = $this->defaultPositions($transient, 'vertical');
        $data['positions_horizontal'] = $this->defaultPositions($transient, 'horizontal');

        if ($imageVertical) {
            $data['image_vertical'] = $imageVertical->store(self::IMAGE_FOLDER, self::IMAGE_DISK);
        }

        if ($imageHorizontal) {
            $data['image_horizontal'] = $imageHorizontal->store(self::IMAGE_FOLDER, self::IMAGE_DISK);
        }

        $data['created_by'] = auth()->id();

        return PriceLabelTemplate::query()->create($data);
    }

    public function update(PriceLabelTemplate $template, array $data, ?UploadedFile $imageVertical, ?UploadedFile $imageHorizontal): PriceLabelTemplate
    {
        if (isset($data['fields'])) {
            $data['fields'] = $this->castFieldBooleans(array_replace_recursive($this->fieldsWithDefaults($template), $data['fields']));
        }

        if ($imageVertical) {
            $data['image_vertical'] = $this->replaceImage($template->image_vertical, $imageVertical);
        }

        if ($imageHorizontal) {
            $data['image_horizontal'] = $this->replaceImage($template->image_horizontal, $imageHorizontal);
        }

        $template->update($data);

        return $template;
    }

    public function delete(PriceLabelTemplate $template): void
    {
        foreach ([$template->image_vertical, $template->image_horizontal] as $path) {
            if ($path && Storage::disk(self::IMAGE_DISK)->exists($path)) {
                Storage::disk(self::IMAGE_DISK)->delete($path);
            }
        }

        $template->delete();
    }

    public function duplicate(PriceLabelTemplate $template): PriceLabelTemplate
    {
        return PriceLabelTemplate::query()->create([
            'name' => $template->name.' (copia)',
            'is_active' => $template->is_active,
            'orientation' => $template->orientation,
            'label_text' => $template->label_text,
            'fields' => $template->fields,
            'positions_vertical' => $template->positions_vertical,
            'positions_horizontal' => $template->positions_horizontal,
            'vertical_rows' => $template->vertical_rows,
            'vertical_columns' => $template->vertical_columns,
            'horizontal_rows' => $template->horizontal_rows,
            'horizontal_columns' => $template->horizontal_columns,
            'field_definitions' => $template->field_definitions,
            'image_vertical' => $this->copyImage($template->image_vertical),
            'image_horizontal' => $this->copyImage($template->image_horizontal),
            'created_by' => auth()->id(),
        ]);
    }

    public function bulkAction(array $ids, string $action): void
    {
        $templates = PriceLabelTemplate::query()->whereIn('id', $ids)->get();

        foreach ($templates as $template) {
            match ($action) {
                'delete' => $this->delete($template),
                'activate' => $template->update(['is_active' => true]),
                'deactivate' => $template->update(['is_active' => false]),
                default => null,
            };
        }
    }

    public function savePositions(PriceLabelTemplate $template, string $orientation, array $positions, ?array $fields = null): void
    {
        $column = $orientation === 'horizontal' ? 'positions_horizontal' : 'positions_vertical';
        $current = $this->positionsWithDefaults($template, $orientation);

        $data = [
            $column => array_replace_recursive($current, $positions),
        ];

        if ($fields !== null) {
            $data['fields'] = $this->castFieldBooleans(array_replace_recursive($this->fieldsWithDefaults($template), $fields));
        }

        $template->update($data);
    }

    /**
     * Clon en memoria (nunca persistido) con las posiciones y estilos que el
     * usuario tiene ahora mismo en el editor, para poder previsualizar el PDF
     * sin guardar los cambios.
     */
    public function withOverrides(PriceLabelTemplate $template, string $orientation, ?array $positions, ?array $fields): PriceLabelTemplate
    {
        $clone = clone $template;

        if ($fields !== null) {
            $clone->fields = $this->castFieldBooleans(
                array_replace_recursive($this->fieldsWithDefaults($template), $fields)
            );
        }

        if ($positions !== null) {
            $column = $orientation === 'horizontal' ? 'positions_horizontal' : 'positions_vertical';
            $clone->{$column} = array_replace_recursive(
                $this->positionsWithDefaults($template, $orientation),
                $positions
            );
        }

        return $clone;
    }

    /**
     * Fila de ejemplo para previsualizar: la ultima importada si existe, si no
     * un valor legible por campo segun su tipo.
     */
    public function sampleRowFor(PriceLabelTemplate $template, ?array $lastSampleRow): array
    {
        $definitions = $template->field_definitions ?: $this->defaultFieldDefinitions();
        $row = [];

        foreach ($definitions as $definition) {
            $key = $definition['key'];
            $stored = $lastSampleRow[$key] ?? null;

            $row[$key] = $stored !== null && $stored !== ''
                ? $stored
                : match ($definition['type'] ?? 'text') {
                    'price' => '149,99',
                    'barcode' => '8412345678905',
                    'qr' => 'https://example.com/'.$key,
                    default => strtoupper($definition['label']),
                };
        }

        return $row;
    }

    public function slotsPerPage(PriceLabelTemplate $template, string $orientation): int
    {
        return $orientation === 'horizontal'
            ? max((int) $template->horizontal_rows * (int) $template->horizontal_columns, 1)
            : max((int) $template->vertical_rows * (int) $template->vertical_columns, 1);
    }

    public function addField(PriceLabelTemplate $template, array $data): string
    {
        $definitions = $template->field_definitions ?? $this->defaultFieldDefinitions();

        $baseKey = Str::slug($data['label'], '_') ?: 'campo';
        $existingKeys = collect($definitions)->pluck('key')->all();
        $key = $baseKey;
        $suffix = 1;
        while ($key === 'label' || in_array($key, $existingKeys, true)) {
            $key = $baseKey.'_'.(++$suffix);
        }

        $definition = [
            'key' => $key,
            'label' => $data['label'],
            'excel_column' => strtoupper($data['excel_column']),
            'type' => $data['type'],
            'order' => count($definitions) + 1,
        ];

        if ($data['type'] === 'barcode') {
            $definition['barcode_type'] = $data['barcode_type'] ?? 'C128';
        }

        $definitions[] = $definition;

        $template->update(['field_definitions' => $definitions]);

        return $key;
    }

    public function removeField(PriceLabelTemplate $template, string $key): void
    {
        $definitions = collect($template->field_definitions ?? $this->defaultFieldDefinitions())
            ->reject(fn ($field) => $field['key'] === $key)
            ->values()
            ->all();

        $strip = fn (?array $data) => collect($data ?? [])->except($key)->all();

        $template->update([
            'field_definitions' => $definitions,
            'fields' => $strip($template->fields),
            'positions_vertical' => $strip($template->positions_vertical),
            'positions_horizontal' => $strip($template->positions_horizontal),
        ]);
    }

    public function fieldKeys(?array $fieldDefinitions): array
    {
        $definitions = $fieldDefinitions ?: $this->defaultFieldDefinitions();
        $keys = collect($definitions)->sortBy('order')->pluck('key')->values()->all();

        return [...$keys, 'label'];
    }

    public function fieldsWithDefaults(PriceLabelTemplate $template): array
    {
        $fields = array_replace_recursive($this->defaultFields($template->field_definitions), $template->fields ?? []);

        // Plantillas anteriores a que la caja fuese por orientacion: heredan
        // su propio tamano vertical (no el del default) para que el
        // horizontal siga saliendo exactamente igual que hasta ahora.
        $stored = $template->fields ?? [];
        foreach ($fields as $key => &$style) {
            if (! isset($stored[$key]['box_w_h']) && isset($stored[$key]['box_w'])) {
                $style['box_w_h'] = $stored[$key]['box_w'];
            }
            if (! isset($stored[$key]['box_h_h']) && isset($stored[$key]['box_h'])) {
                $style['box_h_h'] = $stored[$key]['box_h'];
            }
        }

        return $fields;
    }

    public function positionsWithDefaults(PriceLabelTemplate $template, string $orientation): array
    {
        $stored = $orientation === 'horizontal' ? $template->positions_horizontal : $template->positions_vertical;

        return array_replace_recursive($this->defaultPositions($template, $orientation), $stored ?? []);
    }

    /**
     * @return array<string, string>
     */
    public function columnMap(PriceLabelTemplate $template): array
    {
        return collect($template->field_definitions ?: $this->defaultFieldDefinitions())
            ->pluck('excel_column', 'key')
            ->all();
    }

    public function defaultFieldDefinitions(): array
    {
        return [
            ['key' => 'referencia', 'label' => 'Referencia', 'excel_column' => 'A', 'type' => 'text', 'order' => 1],
            ['key' => 'descripcion', 'label' => 'Descripcion', 'excel_column' => 'B', 'type' => 'text', 'order' => 2],
            ['key' => 'pvprp', 'label' => 'PVP recomendado proveedor', 'excel_column' => 'C', 'type' => 'price', 'order' => 3],
            ['key' => 'pvp', 'label' => 'PVP', 'excel_column' => 'D', 'type' => 'price', 'order' => 4],
        ];
    }

    public function defaultFields(?array $fieldDefinitions = null): array
    {
        $boxes = [
            'referencia' => ['box_w' => 100, 'box_h' => 20],
            'descripcion' => ['box_w' => 330, 'box_h' => 30],
            'pvprp' => ['box_w' => 120, 'box_h' => 33],
            'pvp' => ['box_w' => 200, 'box_h' => 90],
            'label' => ['box_w' => 220, 'box_h' => 30],
        ];

        // Los codigos necesitan una caja mayor que un texto para ser legibles.
        $typeByKey = collect($fieldDefinitions ?: $this->defaultFieldDefinitions())->pluck('type', 'key');
        foreach ($typeByKey as $key => $type) {
            $boxes[$key] = match ($type) {
                'barcode' => ['box_w' => 200, 'box_h' => 60],
                'qr' => ['box_w' => 90, 'box_h' => 90],
                default => $boxes[$key] ?? ['box_w' => 150, 'box_h' => 30],
            };
        }

        $defaults = [];
        foreach ($this->fieldKeys($fieldDefinitions) as $key) {
            $box = $boxes[$key] ?? ['box_w' => 150, 'box_h' => 30];

            $defaults[$key] = array_merge([
                'color' => '#000000',
                'font_family' => 'helvetica',
                'font_size' => 12,
                'bold' => false,
                'italic' => false,
                'font_family_h' => 'helvetica',
                'font_size_h' => 12,
                // Centrado como hasta ahora: era fijo en la plantilla del PDF y
                // pasa a ser una opcion mas de cada campo.
                'align' => 'center',
                // La caja es por orientacion, igual que la fuente y el tamano:
                // las hojas vertical y horizontal tienen proporciones distintas
                // y redimensionar en una no debe alterar la otra. Arrancan con
                // el mismo valor que la vertical para no mover plantillas ya
                // guardadas (fieldsWithDefaults rellena las que no lo tengan).
                'box_w_h' => $box['box_w'],
                'box_h_h' => $box['box_h'],
            ], $box);
        }

        return $defaults;
    }

    public function defaultPositions(PriceLabelTemplate $template, string $orientation): array
    {
        $isHorizontal = $orientation === 'horizontal';
        $rows = max((int) ($isHorizontal ? $template->horizontal_rows : $template->vertical_rows) ?: 1, 1);
        $columns = max((int) ($isHorizontal ? $template->horizontal_columns : $template->vertical_columns) ?: 1, 1);
        $dim = self::CANVAS_DIM[$isHorizontal ? 'horizontal' : 'vertical'];

        $cellW = $dim['w'] / $columns;
        $cellH = $dim['h'] / $rows;
        $keys = $this->fieldKeys($template->field_definitions);

        $positions = [];
        $slot = 1;
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $columns; $c++) {
                foreach ($keys as $i => $key) {
                    $positions[$key][$slot] = [
                        'x' => (int) round($c * $cellW + 10),
                        'y' => (int) round($r * $cellH + 10 + $i * 25),
                    ];
                }
                $slot++;
            }
        }

        return $positions;
    }

    private function castFieldBooleans(array $fields): array
    {
        foreach ($fields as &$style) {
            foreach (['bold', 'italic'] as $key) {
                if (array_key_exists($key, $style)) {
                    $style[$key] = filter_var($style[$key], FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        return $fields;
    }

    private function replaceImage(?string $oldPath, UploadedFile $file): string
    {
        if ($oldPath && Storage::disk(self::IMAGE_DISK)->exists($oldPath)) {
            Storage::disk(self::IMAGE_DISK)->delete($oldPath);
        }

        return $file->store(self::IMAGE_FOLDER, self::IMAGE_DISK);
    }

    private function copyImage(?string $path): ?string
    {
        if (! $path || ! Storage::disk(self::IMAGE_DISK)->exists($path)) {
            return null;
        }

        $newPath = self::IMAGE_FOLDER.'/'.Str::uuid().'.'.pathinfo($path, PATHINFO_EXTENSION);
        Storage::disk(self::IMAGE_DISK)->copy($path, $newPath);

        return $newPath;
    }
}
