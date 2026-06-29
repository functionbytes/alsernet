<?php

namespace Modules\Shortcode\Shortcodes;

use Illuminate\Support\Facades\Auth;
use Modules\Shortcode\Compiler\ShortcodeCompiler;

/**
 * Shortcodes lógicos y contextuales.
 *
 * Lógicos: [if], [for].
 * Contextuales (non-cacheable): [user-name], [user-email], [current-year], etc.
 */
class LogicShortcodes
{
    /** Separador opaco entre rama "if" y "else" dentro de [if]. */
    protected const ELSE_MARKER = "\x02SCELSE\x02";

    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->registerIf();
        $this->registerFor();
        $this->registerUserContext();
        $this->registerSiteContext();
    }

    // ---------------------------------------------------------------------
    // [if role="admin"]contenido[/if]  con [else] opcional dentro.
    // ---------------------------------------------------------------------

    protected function registerIf(): void
    {
        // [else] solo como separador interno; el procesamiento real lo hace [if].
        $this->compiler->register('else', function () {
            return self::ELSE_MARKER;
        }, [
            'description' => 'Separador para la rama alternativa dentro de [if]. Uso: [if...]A[else]B[/if].',
            'example' => '[if user-logged]Hola[else]Inicia sesión[/if]',
            'attributes' => [],
            'raw' => false,
        ]);

        $this->compiler->register('if', function (array $attrs, string $content) {
            $pass = $this->evaluateCondition($attrs);

            // Divide en ramas por ELSE_MARKER si existe.
            if (str_contains($content, self::ELSE_MARKER)) {
                [$then, $else] = explode(self::ELSE_MARKER, $content, 2);

                return $pass ? $then : $else;
            }

            return $pass ? $content : '';
        }, [
            'description' => 'Renderiza el contenido sólo si se cumple la condición.',
            'example' => '[if role="admin"]Panel admin[else]Visitante[/if]',
            'attributes' => [
                'role' => 'Rol Spatie a exigir (admin, editor, etc.)',
                'permission' => 'Permiso Spatie a exigir',
                'user-logged' => 'true: sólo autenticados; false: sólo invitados',
                'user-id' => 'Coincide con el ID del usuario autenticado',
            ],
            'cacheable' => false,
        ]);
    }

    /**
     * @param  array<string, string>  $attrs
     */
    protected function evaluateCondition(array $attrs): bool
    {
        $user = Auth::user();

        if (isset($attrs['user-logged'])) {
            $want = $attrs['user-logged'] === 'true' || $attrs['user-logged'] === '1';

            return $want ? $user !== null : $user === null;
        }

        if (isset($attrs['user-id'])) {
            return $user && (int) $user->id === (int) $attrs['user-id'];
        }

        if (isset($attrs['role']) && $user && method_exists($user, 'hasRole')) {
            return (bool) $user->hasRole($attrs['role']);
        }

        if (isset($attrs['permission']) && $user && method_exists($user, 'can')) {
            return (bool) $user->can($attrs['permission']);
        }

        return false;
    }

    // ---------------------------------------------------------------------
    // [for range="1-5"]Item {i}[/for]
    // ---------------------------------------------------------------------

    protected function registerFor(): void
    {
        $this->compiler->register('for', function (array $attrs, string $content) {
            $range = $attrs['range'] ?? '';

            if (! preg_match('/^(\d+)-(\d+)$/', $range, $m)) {
                return '';
            }

            $from = (int) $m[1];
            $to = (int) $m[2];

            // Límite defensivo.
            if ($to - $from > 200) {
                return '';
            }

            $step = $from <= $to ? 1 : -1;
            $output = '';

            for ($i = $from; $step > 0 ? $i <= $to : $i >= $to; $i += $step) {
                $output .= str_replace(['{i}', '{index}'], [(string) $i, (string) ($i - $from)], $content);
            }

            return $output;
        }, [
            'description' => 'Itera un rango numérico reemplazando {i} (valor) e {index} (contador desde 0).',
            'example' => '[for range="1-3"]<li>Item {i}</li>[/for]',
            'attributes' => [
                'range' => 'Rango inclusivo "inicio-fin" (ej: 1-5). Max 200 iteraciones.',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // Contexto usuario: [user-name], [user-email], [user-id]
    // ---------------------------------------------------------------------

    protected function registerUserContext(): void
    {
        $common = ['cacheable' => false, 'attributes' => []];

        $this->compiler->register('user-name', function () {
            return htmlspecialchars(Auth::user()?->name ?? '');
        }, $common + [
            'description' => 'Nombre del usuario autenticado (vacío si invitado).',
            'example' => '[user-name /]',
        ]);

        $this->compiler->register('user-email', function () {
            return htmlspecialchars(Auth::user()?->email ?? '');
        }, $common + [
            'description' => 'Email del usuario autenticado.',
            'example' => '[user-email /]',
        ]);

        $this->compiler->register('user-id', function () {
            return (string) (Auth::user()?->id ?? '');
        }, $common + [
            'description' => 'ID del usuario autenticado.',
            'example' => '[user-id /]',
        ]);
    }

    // ---------------------------------------------------------------------
    // Contexto sitio: [site-name], [current-year], [current-date]
    // ---------------------------------------------------------------------

    protected function registerSiteContext(): void
    {
        $this->compiler->register('site-name', function () {
            return htmlspecialchars((string) config('app.name', ''));
        }, [
            'description' => 'Nombre de la aplicación (config app.name).',
            'example' => '[site-name /]',
            'attributes' => [],
            // Sí cacheable: cambia rara vez y clearCache() ya lo invalida.
        ]);

        $this->compiler->register('current-year', function () {
            return (string) now()->year;
        }, [
            'description' => 'Año actual (útil en footers).',
            'example' => '© [current-year /] [site-name /]',
            'attributes' => [],
            'cacheable' => false,
        ]);

        $this->compiler->register('current-date', function (array $attrs) {
            $format = $attrs['format'] ?? 'Y-m-d';

            return htmlspecialchars(now()->format($format));
        }, [
            'description' => 'Fecha actual con formato PHP date().',
            'example' => '[current-date format="d/m/Y" /]',
            'attributes' => [
                'format' => 'Formato PHP date (ej: Y-m-d, d/m/Y H:i).',
            ],
            'cacheable' => false,
        ]);
    }
}
