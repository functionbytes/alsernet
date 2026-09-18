<?php

namespace Modules\Core\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use Tests\TestCase;

/**
 * El valor por defecto de un ajuste es de quien pregunta, no del que preguntó
 * primero.
 *
 * `Setting::get()` cachea diez minutos. Cuando la clave no existe en la tabla,
 * lo que se cacheaba era el $defaultValue del primer llamador, así que el
 * siguiente módulo que preguntara por esa misma clave con OTRO default recibía
 * el ajeno — durante diez minutos y sin ninguna pista.
 *
 * Pasó de verdad: una consulta de diagnóstico con default 'NULL' dejó
 * `helpdesk_birthday.bono_type_id` valiendo la cadena 'NULL', que al castear a
 * entero es 0, es decir «sin tipo de bono configurado».
 */
class SettingDefaultCacheTest extends TestCase
{
    private string $key;

    protected function setUp(): void
    {
        parent::setUp();

        // Clave inexistente y propia de este test: no se toca ningún ajuste real.
        $this->key = 'core.test.'.uniqid();
    }

    /**
     * Setting::set() escribe fuera de cualquier transacción de test (y en la
     * caché real), así que la limpieza es explícita: si un test se cae a mitad,
     * la fila no puede quedarse en la tabla de ajustes de verdad.
     */
    protected function tearDown(): void
    {
        Setting::query()->where('key', $this->key)->delete();
        Cache::forget("setting_{$this->key}");

        parent::tearDown();
    }

    public function test_cada_llamador_recibe_su_propio_valor_por_defecto(): void
    {
        $this->assertSame('NULL', Setting::get($this->key, 'NULL'));

        // Segunda llamada, otro default: tiene que ganar el suyo, no el cacheado.
        $this->assertSame('4', Setting::get($this->key, '4'));
        $this->assertNull(Setting::get($this->key));
    }

    public function test_un_ajuste_que_existe_se_sigue_cacheando(): void
    {
        Setting::set($this->key, 'guardado');

        $this->assertSame('guardado', Setting::get($this->key, 'otro'));

        // Y el default no lo pisa aunque se pida con uno distinto.
        $this->assertSame('guardado', Setting::get($this->key));
    }
}
