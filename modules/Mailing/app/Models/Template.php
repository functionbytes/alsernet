<?php

namespace Modules\Mailing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Mailing\Traits\HasUid;

class Template extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $table = 'mailing_templates';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'description',
        'subject',
        'html_content',
        'text_content',
        'settings',
        'category',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'html_content' => 'json',
            'settings' => 'json',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all forms associated with this template.
     */
    public function forms(): HasMany
    {
        return $this->hasMany(TemplateForm::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Render the template with provided variables.
     */
    public function render(array $variables = []): string
    {
        $content = $this->html_content;

        if (is_array($content)) {
            $content = json_encode($content);
        }

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
     * Get a preview of the template.
     */
    public function preview(array $variables = []): string
    {
        return $this->render($variables);
    }

    /**
     * Get rendered subject line with variables.
     */
    public function renderSubject(array $variables = []): string
    {
        $subject = $this->subject;

        foreach ($variables as $key => $value) {
            $subject = str_replace(
                ["{{ $key }}", "{{{ $key }}}", '[['.$key.']]'],
                $value,
                $subject
            );
        }

        return $subject;
    }

    /**
     * Get rendered plain text content with variables.
     */
    public function renderText(array $variables = []): string
    {
        $content = $this->text_content ?? strip_tags($this->html_content ?? '');

        if (is_array($content)) {
            $content = json_encode($content);
        }

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
     * Clone this template with a new name.
     */
    public function duplicate(string $newName): static
    {
        $duplicate = $this->replicate();
        $duplicate->name = $newName;
        $duplicate->save();

        return $duplicate;
    }

    /**
     * Activate this template.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate this template.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Check if template is active.
     */
    public function isActive(): bool
    {
        return $this->is_active ?? false;
    }

    /**
     * Get template variables from content.
     */
    public function getVariables(): array
    {
        $content = $this->html_content ?? '';

        if (is_array($content)) {
            $content = json_encode($content);
        }

        $content .= ' '.$this->subject;

        // Find all variables in format {{ variable_name }}
        preg_match_all('/\{\{\s*(\w+)\s*\}\}|\[\[(\w+)\]\]/', $content, $matches);

        $variables = array_merge($matches[1], $matches[2]);
        $variables = array_filter($variables);

        return array_unique($variables);
    }
}
