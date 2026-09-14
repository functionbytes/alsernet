<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_companies';

    protected $fillable = [
        'name',
        'domain',
        'industry',
        'size',
        'website',
        'notes',
        'health_score',
        'total_revenue',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'health_score' => 'integer',
            'total_revenue' => 'decimal:2',
        ];
    }

    public const SIZES = [
        '1-10' => '1-10 empleados',
        '11-50' => '11-50 empleados',
        '51-200' => '51-200 empleados',
        '201-1000' => '201-1000 empleados',
        '1000+' => 'Mas de 1000 empleados',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'company_id');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('domain', 'like', "%{$term}%")
                ->orWhere('industry', 'like', "%{$term}%");
        });
    }

    /**
     * Find a company by matching an email domain.
     */
    public static function findByEmailDomain(string $email): ?self
    {
        $domain = substr(strrchr($email, '@'), 1);

        if (! $domain || in_array($domain, ['gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com'], true)) {
            return null;
        }

        return self::where('domain', $domain)->first();
    }

    public function getHealthLabel(): string
    {
        if ($this->health_score === null) {
            return 'Sin datos';
        }

        return match (true) {
            $this->health_score >= 80 => 'Saludable',
            $this->health_score >= 50 => 'Regular',
            default => 'En riesgo',
        };
    }

    public function getHealthColor(): string
    {
        if ($this->health_score === null) {
            return 'secondary';
        }

        return match (true) {
            $this->health_score >= 80 => 'success',
            $this->health_score >= 50 => 'warning',
            default => 'danger',
        };
    }
}
