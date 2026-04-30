<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Requests\Api\V1\Review\StoreReviewRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Review\UpdateReviewRequest;
use Modules\Ecommerce\Http\Resources\Api\V1\ReviewResource;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Review;

/**
 * @group Reseñas
 *
 * Lectura pública de reseñas. Creación y edición requieren autenticación y compra verificada.
 */
class ReviewController extends BaseApiController
{
    /**
     * Listar reseñas de un producto
     *
     * Devuelve las reseñas aprobadas de un producto, ordenadas por más recientes.
     *
     * @unauthenticated
     *
     * @urlParam product string required Slug del producto. Example: camiseta-polo-azul
     */
    public function index(Product $product): JsonResponse
    {
        $reviews = Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->latest()
            ->paginate((int) request('per_page', 15));

        return $this->paginated($reviews, ReviewResource::class);
    }

    /**
     * Crear reseña
     *
     * Crea una reseña para un producto. El cliente debe haber completado una orden con ese producto.
     * La reseña queda en estado `pending` hasta que un administrador la apruebe.
     *
     * @urlParam product string required Slug del producto. Example: camiseta-polo-azul
     */
    public function store(StoreReviewRequest $request, Product $product): JsonResponse
    {
        $customer = $request->user();

        // Verified buyer check
        $hasPurchased = Order::query()
            ->where('customer_id', $customer->id)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->where('status', 'completed')
            ->exists();

        if (! $hasPurchased) {
            return $this->errorResponse(
                'Solo compradores verificados pueden dejar reseñas.',
                'NOT_VERIFIED_BUYER',
                403
            );
        }

        $review = Review::query()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'star' => (int) $request->input('star'),
            'comment' => $request->input('comment'),
            'is_verified_buyer' => true,
            'status' => 'pending',
        ]);

        return $this->created(new ReviewResource($review));
    }

    /**
     * Actualizar reseña
     *
     * Edita una reseña propia. Solo es posible dentro de los 7 días posteriores a su creación.
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        if ($review->customer_id !== auth()->id()) {
            return $this->errorResponse('No autorizado.', 'FORBIDDEN', 403);
        }

        // Allow edits only within 7 days of creation
        if ($review->created_at->diffInDays(now()) > 7) {
            return $this->errorResponse('Ya no puedes editar esta reseña.', 'REVIEW_LOCKED', 422);
        }

        $review->update($request->validated());

        return $this->ok(new ReviewResource($review->fresh()));
    }

    /**
     * Eliminar reseña
     *
     * Elimina una reseña propia permanentemente.
     */
    public function destroy(Review $review): JsonResponse
    {
        if ($review->customer_id !== auth()->id()) {
            return $this->errorResponse('No autorizado.', 'FORBIDDEN', 403);
        }

        $review->delete();

        return $this->noContent('Reseña eliminada.');
    }
}
