<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;

class FormChangeLog extends Model
{
    public const UPDATED_AT = null;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_form_changes';

    protected $fillable = [
        'form_id',
        'form_key',
        'user_id',
        'user_name',
        'action',
        'changes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Registra un cambio. $changes es un array plano [field => [old, new]];
     * se omiten pares donde old === new para que el diff sea legible.
     */
    public static function record(string $formKey, string $action, ?int $formId, array $changes = []): self
    {
        $user = auth()->user();

        return self::create([
            'form_id' => $formId,
            'form_key' => $formKey,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'changes' => $changes !== [] ? $changes : null,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function diff(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [$oldValue, $newValue];
            }
        }

        return $changes;
    }
}
