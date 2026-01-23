<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Mailer\Traits\HasUid;

/**
 * @property int $id
 * @property string $uid
 * @property string $key
 * @property string $name
 * @property int|null $layout_id
 * @property bool $is_enabled
 * @property bool $is_protected
 * @property array|null $variables
 * @property string $module
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Modules\Mailer\Models\MailLayout|null $layout
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Mailer\Models\MailerTemplateLang> $translations
 * @property-read string|null $subject (magic getter - from current translation)
 * @property-read string|null $content (magic getter - from current translation)
 */
class MailerTemplate extends Model
{
    use HasFactory, HasUid;

    protected $table = 'mailer_templates';

    protected $fillable = [
        'uid',
        'key',
        'name',
        'layout_id',
        'is_enabled',
        'is_protected',
        'variables',
        'module',
        'description',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_enabled' => 'boolean',
        'is_protected' => 'boolean',
    ];

    /**
     * Relación con Layout (header/footer)
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo('Modules\Mailer\Models\MailerLayout', 'layout_id', 'id');
    }

    /**
     * Relación con traducciones
     */
    public function translations(): HasMany
    {
        return $this->hasMany(MailerTemplateLang::class, 'mailer_template_id', 'id');
    }

    /**
     * Obtener traducción para un idioma específico con fallback
     * Si no existe la traducción, intenta con lang_id 1 (idioma Por defecto)
     */
    public function translate(?int $langId = null): ?MailerTemplateLang
    {
        $langId = $langId ?? 1;

        // Buscar traducción para el idioma solicitado
        $translation = $this->translations()
            ->where('lang_id', $langId)
            ->first();

        if ($translation) {
            return $translation;
        }

        // Si no existe, intentar con el idioma Por defecto (1)
        if ($langId !== 1) {
            return $this->translations()
                ->where('lang_id', 1)
                ->first();
        }

        return null;
    }

    /**
     * Magic getter para subject (backwards compatibility)
     * Si existe traducción, devuelve subject de la traducción
     */
    public function getSubjectAttribute()
    {
        // Si estamos creando/editando y no tenemos una traducción cargada,
        // intentar obtenerla del atributo original
        if (isset($this->attributes['subject'])) {
            $translation = $this->translate();
            if ($translation && $translation->subject) {
                return $translation->subject;
            }
        }

        return $this->attributes['subject'] ?? null;
    }

    /**
     * Magic getter para content (backwards compatibility)
     * Si existe traducción, devuelve content de la traducción
     */
    public function getContentAttribute()
    {
        if (isset($this->attributes['content'])) {
            $translation = $this->translate();
            if ($translation && $translation->content) {
                return $translation->content;
            }
        }

        return $this->attributes['content'] ?? null;
    }

    /**
     * Scope: Filtrar por módulo
     */
    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: Solo templates habilitadas
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: Buscar por palabra clave
     */
    public function scopeSearch($query, $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where('name', 'like', '%'.$keyword.'%')
            ->orWhere('key', 'like', '%'.$keyword.'%')
            ->orWhere('description', 'like', '%'.$keyword.'%');
    }

    /**
     * Scope: Filtrar por módulo múltiple
     */
    public function scopeInModules($query, array $modules)
    {
        return $query->whereIn('module', $modules);
    }

    /**
     * Scope: Filtrar por idioma (busca en traducciones)
     */
    public function scopeLang($query, $langId)
    {
        if (is_null($langId)) {
            return $query->whereDoesntHave('translations');
        }

        return $query->whereHas('translations', function ($q) use ($langId) {
            $q->where('lang_id', $langId);
        });
    }

    /**
     * Obtener todas las variables disponibles Por defecto
     * Estas se pueden sobrescribir por template
     */
    public static function defaultVariables($module = 'core'): array
    {
        // Get variables from database using MailVariableService
        // Include both module-specific and core variables
        $variables = MailerVariable::query()
            ->where('is_enabled', true)
            ->where(function ($query) use ($module) {
                $query->where('module', $module)
                    ->orWhere('module', 'core');
            })
            ->orderBy('category')
            ->orderBy('key')
            ->get();

        $result = [];
        foreach ($variables as $variable) {
            $result[] = [
                'name' => $variable->key,
                'required' => $variable->is_system, // System variables are considered required
                'description' => $variable->description,
                'category' => $variable->category,
            ];
        }

        return $result;
    }

    /**
     * Obtener variables de este template
     */
    public function getAvailableVariables(): array
    {
        // Si el template tiene variables definidas, usarlas
        if ($this->variables && is_array($this->variables)) {
            return $this->variables;
        }

        // Si no, usar las variables Por defecto del módulo
        return self::defaultVariables($this->module);
    }

    /**
     * Verificar si template está completo (tiene todas las variables requeridas)
     */
    public function isComplete(): bool
    {
        $variables = $this->getAvailableVariables();

        foreach ($variables as $variable) {
            if ($variable['required']) {
                if (! str_contains($this->content, '{'.$variable['name'].'}')) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Obtener variables faltantes (requeridas pero no en contenido)
     */
    public function getMissingVariables(): array
    {
        $variables = $this->getAvailableVariables();
        $missing = [];

        foreach ($variables as $variable) {
            if ($variable['required']) {
                if (! str_contains($this->content, '{'.$variable['name'].'}')) {
                    $missing[] = $variable;
                }
            }
        }

        return $missing;
    }

    /**
     * Validar template antes de guardar
     */
    public function validate(): bool
    {
        // Template debe tener contenido
        if (empty($this->content)) {
            return false;
        }

        // Subject debe tener contenido
        if (empty($this->subject)) {
            return false;
        }

        return true;
    }

    /**
     * Obtener próxima estructura de template (para nuevo template)
     */
    public static function getStructureForModule($module = 'core'): string
    {
        $variables = self::defaultVariables($module);

        $varsList = implode(', ', array_map(function ($var) {
            return '{'.$var['name'].'}';
        }, array_filter($variables, fn ($v) => $v['required'])));

        $baseStructure = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Hola {CUSTOMER_NAME}</h1>
                <p>Mensaje del template aquí...</p>
                <p>Variables disponibles: $varsList</p>
            </div>
        </body>
        </html>
        HTML;

        return $baseStructure;
    }
}
