<?php

namespace Modules\HelpdeskErp\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un ajuste de «Ajustes de Gestión» (helpdesk_erp_settings): clave lógica
 * (p. ej. "chat_ttl.ok") y su valor en JSON. Solo existen las filas que
 * cambian el valor por defecto de config/.env.
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property int|null $updated_by
 */
class ErpAdminSetting extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_erp_settings';

    protected $fillable = ['key', 'value', 'updated_by'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
            'updated_by' => 'integer',
        ];
    }
}
