<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Locales\Models\Locale;

class MailerLang extends Model
{
    use HasFactory;

    protected $table = 'langs';

    /**
     * Cache por request del lang_id por defecto ya resuelto.
     */
    private static ?int $cachedDefaultId = null;

    protected $fillable = [
        'uid',
        'title',
        'iso_code',
        'lenguage_code',
        'locate',
        'date_format_full',
        'date_format_lite',
        'available',
        'created_at',
        'updated_at',
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeId($query, $id)
    {
        return $query->where('id', $id);
    }

    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid);
    }

    public function scopeIso($query, $iso)
    {
        return $query->where('iso_code', $iso);
    }

    public function scopeLocate($query, $iso)
    {
        return $query->where('locate', $iso);
    }

    /**
     * Available = marcado como disponible en langs Y activo en el módulo Locales.
     * Si la tabla locales no existe aún (tests/migraciones a medias) degrada al
     * comportamiento legacy (solo available=1).
     */
    public function scopeAvailable($query)
    {
        $query->where('langs.available', 1);

        try {
            $activeCodes = Locale::active()->pluck('code')->all();

            if (! empty($activeCodes)) {
                $query->whereIn('langs.iso_code', $activeCodes);
            }
        } catch (\Throwable) {
            // Fallback silencioso: si locales no está disponible, mantener solo available=1
        }

        return $query;
    }

    public static function getSelectOptions()
    {
        $options = self::available()->get()->map(function ($item) {
            return ['value' => $item->id, 'text' => $item->title];
        });

        // japan only en and ja
        if (config('custom.japan')) {
            $options = self::available()->get()->filter(function ($item) {
                return in_array($item->iso_code, ['en', 'ja']);
            })->map(function ($item) {
                return ['value' => $item->id, 'text' => $item->title];
            });
        }

        return $options;
    }

    public function scopeSearch($query, $keyword)
    {
        if (! empty(trim($keyword))) {
            $keyword = trim($keyword);
            foreach (explode(' ', $keyword) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orWhere('langs.title', 'like', '%'.$keyword.'%')
                        ->orWhere('langs.iso_code', 'like', '%'.$keyword.'%')
                        ->orWhere('langs.lenguage_code', 'like', '%'.$keyword.'%');
                });
            }
        }
    }

    public function getBuilderLang()
    {
        return include $this->languageDir().DIRECTORY_SEPARATOR.'builder.php';
    }

    public function languageDir()
    {
        return resource_path(join_paths('lang', $this->iso_code));
    }

    public static function getDefaultLangId(): int
    {
        return self::resolveDefaultId();
    }

    /**
     * Resolver el lang_id por defecto. Delegamos al helper canónico
     * en el módulo Locales para que los módulos Attention/Mailrelay/Mailer
     * compartan la misma lógica.
     */
    public static function resolveDefaultId(): int
    {
        return Locale::resolveLegacyLangId();
    }

    /**
     * Vaciar cache en memoria (usado por el LocaleObserver).
     */
    public static function clearResolvedDefault(): void
    {
        Locale::clearResolvedLegacyLangId();
    }
}
