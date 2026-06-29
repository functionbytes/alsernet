<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Resources\Api\V1\LegalPageResource;
use Modules\Ecommerce\Models\LegalPage;

/**
 * @group Páginas legales
 *
 * Términos y condiciones, política de privacidad y otros documentos legales. Acceso público.
 */
class LegalPageController extends BaseApiController
{
    /**
     * Ver página legal
     *
     * Devuelve el contenido de una página legal publicada (p.ej. términos y condiciones, privacidad).
     *
     * @unauthenticated
     *
     * @urlParam slug string required Slug de la página. Example: terminos-y-condiciones
     */
    public function show(string $slug): JsonResponse
    {
        $page = LegalPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return $this->ok(new LegalPageResource($page));
    }
}
