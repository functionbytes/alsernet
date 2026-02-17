<?php

namespace Modules\Page\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageVersion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'page_versions';

    /**
     * Disable updated_at timestamp (we only track creation).
     *
     * @var bool
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'page_id',
        'version_number',
        'title',
        'content',
        'description',
        'user_id',
        'template',
        'status',
        'slug',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'version_number' => 'integer',
        'created_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the page that owns this version.
     */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Get the user who created this version.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to order versions by version number descending.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('version_number', 'desc');
    }

    /**
     * Scope to order versions by version number ascending.
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('version_number', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get a human-readable label for this version.
     */
    public function getVersionLabel(): string
    {
        return "v{$this->version_number}";
    }

    /**
     * Get a human-readable date string.
     */
    public function getFormattedDate(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    /**
     * Get the content size in bytes.
     */
    public function getContentSize(): int
    {
        return strlen($this->content ?? '');
    }

    /**
     * Get a summary of changes for this version.
     */
    public function getSummary(): string
    {
        $changes = [];

        if ($this->title) {
            $changes[] = $this->title;
        }

        if ($this->status) {
            $changes[] = ucfirst($this->status);
        }

        $userName = $this->user ? $this->user->name : 'Sistema';

        return implode(' - ', array_filter([
            $this->getVersionLabel(),
            $userName,
            $this->getFormattedDate(),
        ]));
    }
}
