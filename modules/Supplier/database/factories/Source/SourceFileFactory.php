<?php

namespace Modules\Supplier\Database\Factories\Source;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Source\SourceFile;

class SourceFileFactory extends Factory
{
    protected $model = SourceFile::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['pdf', 'xlsx', 'docx', 'csv']);

        return [
            'uid' => Str::ulid(),
            'source_id' => null,
            'supplier_id' => null,
            'original_name' => $this->faker->word().'.'.$type,
            'stored_path' => 'supplier-files/'.$this->faker->numberBetween(1, 100).'/'.Str::random(16).'.'.$type,
            'file_type' => $type,
            'file_size' => $this->faker->numberBetween(1000, 5000000),
            'mime_type' => match ($type) {
                'pdf' => 'application/pdf',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'csv' => 'text/csv',
                default => 'application/octet-stream',
            },
            'origin' => $this->faker->randomElement(['upload', 'extraction', 'webhook']),
            'extraction_status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'extracted_at' => null,
            'extraction_result_id' => null,
            'error_message' => null,
            'metadata' => null,
        ];
    }
}
