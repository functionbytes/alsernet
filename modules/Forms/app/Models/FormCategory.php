<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Forms\Database\Factories\FormCategoryFactory;

class FormCategory extends Model
{
    use HasFactory;

    protected $table = 'form_categories';

    protected static function newFactory(): Factory
    {
        return FormCategoryFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function forms(): HasMany
    {
        return $this->hasMany(Form::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFormsCountAttribute(): int
    {
        return $this->forms()->count();
    }
}
