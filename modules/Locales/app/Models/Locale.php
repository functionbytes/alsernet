<?php

namespace Modules\Locales\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Locale extends Model
{
    use SoftDeletes;

    /**
     * Cache por request del lang_id legacy resuelto (tabla `langs`).
     */
    private static ?int $cachedLegacyLangId = null;

    protected $fillable = [
        'code',
        'language_code',
        'name',
        'native_name',
        'flag',
        'rtl',
        'is_default',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'rtl' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /** Scope: active locales ordered by display order */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('order');
    }

    /** Scope: all locales ordered by display order then name */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /** Get the default locale, or null if none is set */
    public static function getDefault(): ?static
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Get codes of all active locales.
     *
     * @return array<string>
     */
    public static function getActiveCodes(): array
    {
        return static::active()->pluck('code')->toArray();
    }

    /**
     * Resuelve el `langs.id` legacy correspondiente al locale por defecto.
     *
     * Todos los módulos que persisten FKs contra la tabla `langs`
     * (Mailer, Attention, Mailrelay, etc.) deben usar este helper en lugar
     * de hardcodear `1`. Prioriza:
     *   1. Locale con is_default=true cruzado con langs.iso_code
     *   2. app()->getLocale() cruzado con langs.iso_code
     *   3. Primer row en langs con available=1
     *   4. 1 como fallback final
     *
     * El resultado se cachea en memoria por request.
     */
    public static function resolveLegacyLangId(): int
    {
        if (self::$cachedLegacyLangId !== null) {
            return self::$cachedLegacyLangId;
        }

        if (! Schema::hasTable('langs')) {
            return self::$cachedLegacyLangId = 1;
        }

        try {
            $defaultLocale = static::active()->firstWhere('is_default', true);

            if ($defaultLocale) {
                $match = DB::table('langs')
                    ->where('iso_code', $defaultLocale->code)
                    ->where('available', 1)
                    ->value('id');

                if ($match) {
                    return self::$cachedLegacyLangId = (int) $match;
                }
            }
        } catch (\Throwable) {
            // locales puede no existir aún (tests/migraciones parciales)
        }

        $appLocale = app()->getLocale();
        if ($appLocale) {
            $match = DB::table('langs')
                ->where('iso_code', $appLocale)
                ->where('available', 1)
                ->value('id');
            if ($match) {
                return self::$cachedLegacyLangId = (int) $match;
            }
        }

        $first = DB::table('langs')
            ->where('available', 1)
            ->value('id');

        return self::$cachedLegacyLangId = (int) ($first ?? 1);
    }

    /**
     * Vaciar cache in-memory (útil en tests y en el Observer).
     */
    public static function clearResolvedLegacyLangId(): void
    {
        self::$cachedLegacyLangId = null;
    }
}
