<?php

namespace Modules\Erp\Tests\Unit;

use Illuminate\Http\Request;
use Modules\Erp\Http\Resources\CategoriaClResource;
use Modules\Erp\Http\Resources\FamiliaClResource;
use Modules\Erp\Http\Resources\GrupoClResource;
use Modules\Erp\Http\Resources\ModeloDetalleResource;
use Modules\Erp\Http\Resources\ProveedorProductosResource;
use Modules\Erp\Http\Resources\SubfamiliaClResource;
use PHPUnit\Framework\TestCase;

/**
 * Regresión del bug de evaluación eager en `$this->when($cond, $valor)`.
 *
 * PHP evalúa TODOS los argumentos de una función antes de invocarla. El
 * patrón `$this->when($this->relationLoaded('x'), $this->x->map(...))`
 * evaluaba `$this->x->map(...)` como argumento SIEMPRE, sin importar la
 * condición — disparando un lazy-load a Oracle aunque la relación no
 * estuviera cargada. El fix envuelve el segundo argumento en `fn () => ...`
 * para que solo se evalúe cuando `when()` internamente lo invoca.
 *
 * Cada test usa un stand-in que simula un modelo Eloquent con la relación
 * SIN cargar: `relationLoaded()` devuelve false y el acceso a la propiedad
 * de la relación lanza una excepción. Si el bug reaparece, el test falla
 * porque la relación se toca aunque no esté cargada — sin necesitar una
 * conexión Oracle real.
 */
class ResourceEagerEvaluationRegressionTest extends TestCase
{
    private function request(): Request
    {
        return new Request;
    }

    public function test_familia_resource_does_not_touch_unloaded_subfamilias(): void
    {
        $model = new class
        {
            public $idfamilia_cl = 1;

            public $descripcion = 'Familia';

            public $desc_corta = 'Fam';

            public $estado = 1;

            public $sonarmas = 0;

            public $sonarmasfogueo = 0;

            public $soncartuchos = 0;

            public $categoriaCl = null;

            public $fcreacion = null;

            public $fmodificacion = null;

            public function relationLoaded($key)
            {
                return false;
            }

            public function __get($name)
            {
                if ($name === 'subfamilias') {
                    throw new \RuntimeException('subfamilias fue accedida sin relationLoaded()');
                }

                return null;
            }
        };

        (new FamiliaClResource($model))->toArray($this->request());
        $this->assertTrue(true); // No exception thrown = relation was never eagerly touched.
    }

    public function test_grupo_resource_does_not_touch_unloaded_articulos(): void
    {
        $model = new class
        {
            public $idgrupo_cl = 1;

            public $descripcion = 'Grupo';

            public $desc_corta = 'Gr';

            public $estado = 1;

            public $prefijo = 'G';

            public $prox_num = 1;

            public $pedir_numero_serie = 0;

            public $intrastat = null;

            public $subfamiliaCl = null;

            public $fcreacion = null;

            public $fmodificacion = null;

            public function relationLoaded($key)
            {
                return false;
            }

            public function __get($name)
            {
                if ($name === 'articulos') {
                    throw new \RuntimeException('articulos fue accedida sin relationLoaded()');
                }

                return null;
            }
        };

        (new GrupoClResource($model))->toArray($this->request());
        $this->assertTrue(true);
    }

    public function test_subfamilia_resource_does_not_touch_unloaded_grupos(): void
    {
        $model = new class
        {
            public $idsubfamilia_cl = 1;

            public $descripcion = 'Subfamilia';

            public $desc_corta = 'Sub';

            public $estado = 1;

            public $escartucheria = 0;

            public $esmunicionmetalica = 0;

            public $mostrarlotes = 0;

            public $familiaCl = null;

            public $fcreacion = null;

            public $fmodificacion = null;

            public function relationLoaded($key)
            {
                return false;
            }

            public function __get($name)
            {
                if ($name === 'grupos') {
                    throw new \RuntimeException('grupos fue accedida sin relationLoaded()');
                }

                return null;
            }
        };

        (new SubfamiliaClResource($model))->toArray($this->request());
        $this->assertTrue(true);
    }

    public function test_categoria_resource_does_not_touch_unloaded_familias(): void
    {
        $model = new class
        {
            public $idcategoria_cl = 1;

            public $descripcion = 'Categoria';

            public $desc_corta = 'Cat';

            public $estado = 1;

            public $aparece_inf_stock = 0;

            public $deporteCl = null;

            public $fcreacion = null;

            public $fmodificacion = null;

            public function relationLoaded($key)
            {
                return false;
            }

            public function __get($name)
            {
                if ($name === 'familias') {
                    throw new \RuntimeException('familias fue accedida sin relationLoaded()');
                }

                return null;
            }
        };

        (new CategoriaClResource($model))->toArray($this->request());
        $this->assertTrue(true);
    }

    public function test_proveedor_productos_resource_does_not_touch_unloaded_artiprovs(): void
    {
        $model = new class
        {
            public $idproveedor = 1;

            public $nombre = 'Proveedor';

            public $cif = 'B00000000';

            public $email = 'a@b.com';

            public $telefono1 = '600000000';

            public $estado = 1;

            public $fcreacion = null;

            public $fmodificacion = null;

            public function relationLoaded($key)
            {
                return false;
            }

            public function __get($name)
            {
                if ($name === 'artiprovs') {
                    throw new \RuntimeException('artiprovs fue accedida sin relationLoaded()');
                }

                return null;
            }
        };

        (new ProveedorProductosResource($model))->toArray($this->request());
        $this->assertTrue(true);
    }

    public function test_modelo_detalle_resource_does_not_touch_unloaded_articulos(): void
    {
        $model = new class
        {
            public $idmodelo = 1;

            public $codigo = 'M1';

            public $nombre = 'Modelo';

            public $descripcion = 'Desc';

            public $estado = 1;

            public $estado_publicado_web = 1;

            public $venta_telefono = 0;

            public $precio_consultar_ficha = 0;

            public $marca = null;

            public $grupoCl = null;

            public $fcreacion = null;

            public $fmodificacion = null;

            public function relationLoaded($key)
            {
                return false;
            }

            public function __get($name)
            {
                if ($name === 'articulos') {
                    throw new \RuntimeException('articulos fue accedida sin relationLoaded()');
                }

                return null;
            }
        };

        (new ModeloDetalleResource($model))->toArray($this->request());
        $this->assertTrue(true);
    }
}
