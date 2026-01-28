<?php

namespace Modules\Mailing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Mailing\Traits\HasUid;

class Layout extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $table = 'mailing_layouts';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'user_id',
        'alias',
        'group_name',
        'code',
        'type',
        'is_protected',
        'is_enabled',
        'settings',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'settings' => 'json',
            'is_protected' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this layout.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Render the layout with provided variables.
     */
    public function render(array $variables = []): string
    {
        $content = $this->code ?? '';

        foreach ($variables as $key => $value) {
            $content = str_replace(
                ["{{ $key }}", "{{{ $key }}}", '[['.$key.']]'],
                $value,
                $content
            );
        }

        return $content;
    }

    /**
     * Get a preview of the layout.
     */
    public function preview(array $variables = []): string
    {
        return $this->render($variables);
    }

    /**
     * Get settings as an array.
     */
    public function getSettingsArray(): array
    {
        if (empty($this->settings)) {
            return [];
        }

        return is_array($this->settings) ? $this->settings : json_decode($this->settings, true) ?? [];
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->getSettingsArray();

        return $settings[$key] ?? $default;
    }

    /**
     * Set a specific setting value.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->getSettingsArray();
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    /**
     * Check if this is the default layout.
     */
    public function isDefault(): bool
    {
        return $this->getSetting('is_default', false) === true;
    }

    /**
     * Set this layout as the default for its group.
     */
    public function setAsDefault(): void
    {
        // Reset other layouts in the same group
        static::query()
            ->where('group_name', $this->group_name)
            ->where('id', '!=', $this->id)
            ->get()
            ->each(function (self $layout) {
                $layout->setSetting('is_default', false);
                $layout->save();
            });

        // Set this as default
        $this->setSetting('is_default', true);
    }

    /**
     * Check if layout is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled ?? true;
    }

    /**
     * Enable the layout.
     */
    public function enable(): void
    {
        $this->update(['is_enabled' => true]);
    }

    /**
     * Disable the layout.
     */
    public function disable(): void
    {
        $this->update(['is_enabled' => false]);
    }

    /**
     * Check if layout is protected from modification.
     */
    public function isProtected(): bool
    {
        return $this->is_protected ?? false;
    }

    /**
     * Protect the layout from modification.
     */
    public function protect(): void
    {
        $this->update(['is_protected' => true]);
    }

    /**
     * Unprotect the layout.
     */
    public function unprotect(): void
    {
        $this->update(['is_protected' => false]);
    }

    /**
     * Extract variables from the layout code.
     */
    public function getVariables(): array
    {
        $code = $this->code ?? '';

        // Find all variables in format {{ variable_name }}
        preg_match_all('/\{\{\s*(\w+)\s*\}\}|\[\[(\w+)\]\]/', $code, $matches);

        $variables = array_merge($matches[1], $matches[2]);
        $variables = array_filter($variables);

        return array_unique($variables);
    }
}
