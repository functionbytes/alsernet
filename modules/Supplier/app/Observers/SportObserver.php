<?php

namespace Modules\Supplier\Observers;

use Modules\Supplier\Helpers\CategoryTreeHelper;
use Modules\Supplier\Models\Category\Sport;

/**
 * Observer que invalida la caché del árbol de categorías
 * cuando un deporte es creado, actualizado o eliminado.
 *
 * El árbol agrupa categorías por deporte, así que un cambio
 * de deporte también requiere invalidar la caché.
 */
class SportObserver
{
    public function saved(Sport $sport): void
    {
        CategoryTreeHelper::flushCache();
    }

    public function deleted(Sport $sport): void
    {
        CategoryTreeHelper::flushCache();
    }
}
