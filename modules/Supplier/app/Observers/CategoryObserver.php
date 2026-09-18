<?php

namespace Modules\Supplier\Observers;

use Modules\Supplier\Helpers\CategoryTreeHelper;
use Modules\Supplier\Models\Category\Category;

/**
 * Observer que invalida la caché del árbol de categorías
 * cuando una categoría es creada, actualizada o eliminada.
 */
class CategoryObserver
{
    public function saved(Category $category): void
    {
        CategoryTreeHelper::flushCache();
    }

    public function deleted(Category $category): void
    {
        CategoryTreeHelper::flushCache();
    }
}
