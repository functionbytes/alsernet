<?php

namespace Modules\HelpdeskBirthday\Services;

use Modules\Mailer\Models\MailerLang;

/**
 * En qué idioma se le escribe a cada cliente.
 *
 * El ERP devuelve `language` como su id interno de idioma (columna IDIDIOMA de
 * Oracle), que no tiene nada que ver con los ids de la tabla `langs` del módulo
 * Mailer. La correspondencia se declara en `helpdeskbirthday.erp_language_map`
 * (id del ERP → ISO), porque no hay forma de deducirla desde aquí.
 *
 * Mientras ese mapa esté vacío todo el mundo recibe el correo en el idioma por
 * defecto, que es exactamente el comportamiento anterior: así activar esto no
 * cambia nada hasta que alguien confirme los ids reales del ERP.
 */
class BirthdayLanguageResolver
{
    /** @var array<string, int|null> caché por ISO dentro de la misma campaña */
    private array $langIds = [];

    /**
     * ISO del idioma en el que hay que escribir a este cliente.
     */
    public function isoFor(mixed $erpLanguageId): string
    {
        $fallback = $this->fallbackIso();

        if ($erpLanguageId === null || $erpLanguageId === '') {
            return $fallback;
        }

        $map = (array) config('helpdeskbirthday.erp_language_map', []);
        $iso = $map[(string) $erpLanguageId] ?? null;

        if (! is_string($iso) || $iso === '') {
            return $fallback;
        }

        // Un ISO mapeado a un idioma que no existe en Mailer no sirve de nada:
        // la plantilla no tendría traducción y saldría en blanco.
        return $this->langId($iso) !== null ? $iso : $fallback;
    }

    /**
     * Id de `langs` para ese ISO, que es lo que necesita el renderer del
     * módulo Mailer. null si ese idioma no está dado de alta.
     */
    public function langId(?string $iso): ?int
    {
        if ($iso === null || $iso === '') {
            return null;
        }

        $iso = mb_strtolower($iso);

        if (! array_key_exists($iso, $this->langIds)) {
            $this->langIds[$iso] = MailerLang::query()
                ->where('iso_code', $iso)
                ->value('id');
        }

        return $this->langIds[$iso] !== null ? (int) $this->langIds[$iso] : null;
    }

    public function fallbackIso(): string
    {
        return (string) config('helpdeskbirthday.fallback_language', 'es');
    }
}
