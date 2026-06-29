<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Http\Requests\Api\V1\Address\StoreAddressRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Address\UpdateAddressRequest;
use Modules\Ecommerce\Http\Resources\Api\V1\AddressResource;
use Modules\Ecommerce\Models\CustomerAddress;

/**
 * @group Direcciones
 *
 * Gestión de direcciones de envío y facturación del cliente.
 */
class AddressController extends BaseApiController
{
    /**
     * Listar direcciones
     *
     * Devuelve todas las direcciones del cliente, con la predeterminada primero.
     */
    public function index(): JsonResponse
    {
        $addresses = CustomerAddress::query()
            ->where('customer_id', auth()->id())
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return $this->ok(AddressResource::collection($addresses)->toArray(request()));
    }

    /** Crear dirección */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $isDefault = (bool) ($data['is_default'] ?? false);

            if ($isDefault) {
                CustomerAddress::query()
                    ->where('customer_id', $request->user()->id)
                    ->update(['is_default' => false]);
            }

            return CustomerAddress::query()->create([
                ...$data,
                'customer_id' => $request->user()->id,
                'is_default' => $isDefault,
            ]);
        });

        return $this->created(new AddressResource($address));
    }

    /** Ver dirección */
    public function show(CustomerAddress $address): JsonResponse
    {
        $this->authorize('view', $address);

        return $this->ok(new AddressResource($address));
    }

    /** Actualizar dirección */
    public function update(UpdateAddressRequest $request, CustomerAddress $address): JsonResponse
    {
        DB::transaction(function () use ($request, $address) {
            $data = $request->validated();

            if (! empty($data['is_default'])) {
                CustomerAddress::query()
                    ->where('customer_id', $address->customer_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update($data);
        });

        return $this->ok(new AddressResource($address->fresh()));
    }

    /** Eliminar dirección */
    public function destroy(CustomerAddress $address): JsonResponse
    {
        $this->authorize('delete', $address);
        $address->delete();

        return $this->noContent('Dirección eliminada.');
    }

    /**
     * Establecer dirección predeterminada
     *
     * Marca la dirección como predeterminada y desmarca la anterior.
     */
    public function setDefault(CustomerAddress $address): JsonResponse
    {
        $this->authorize('update', $address);

        DB::transaction(function () use ($address) {
            CustomerAddress::query()
                ->where('customer_id', $address->customer_id)
                ->update(['is_default' => false]);

            $address->forceFill(['is_default' => true])->save();
        });

        return $this->ok(new AddressResource($address->fresh()));
    }
}
