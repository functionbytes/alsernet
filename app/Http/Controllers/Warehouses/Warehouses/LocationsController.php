<?php

namespace App\Http\Controllers\Warehouses\Warehouses;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Shop;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Warehouse\WarehouseInventoryOperation;
use App\Models\Warehouse\WarehouseLocationSection;
use App\Services\Inventories\BarcodeReadingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocationsController extends Controller
{
    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $warehouses = Warehouse::latest();

        if ($searchKey != null) {
            $warehouses = $warehouses->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($available != null) {
            $warehouses = $warehouses->where('available', $available);
        }

        $warehouses = $warehouses->paginate(paginationNumber());

        return view('warehouses.views.warehouses.warehouses.index')->with([
            'warehouses' => $warehouses,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);

    }

    public function content($uid){

        $warehouse = Warehouse::uid($uid);

        return view('warehouses.views.warehouses.warehouses.content')->with([
            'inventarie' => $warehouse,
        ]);

    }



    public function section($warehouse, $location, $section){

        $warehouse = Warehouse::uid($warehouse);
        $location = WarehouseLocation::uid($location);
        $section = WarehouseLocationSection::uid($section);

        // Validar que los registros existan
        if (!$warehouse || !$location || !$section) {
            return abort(404, 'Warehouse, Location o Section no encontrado');
        }

        // Redirigir a modalitie para seleccionar automático o manual
        return redirect()->route('warehouse.warehouses.location.modalitie', [
            'warehouse' => $warehouse->uid,
            'location' => $location->uid,
            'section' => $section->uid,
        ]);
    }

    public function modalitie($warehouse, $location, $section){

        $warehouse = Warehouse::uid($warehouse);
        $location = WarehouseLocation::uid($location);
        $section = WarehouseLocationSection::uid($section);

        // Validar que los registros existan
        if (!$warehouse || !$location || !$section) {
            return abort(404, 'Warehouse, Location o Section no encontrado');
        }
//
//        // Verificar si existe una operación activa
//        $operationValidate = WarehouseInventoryOperation::getActiveByWarehouse($warehouse->id);
//
//        if ($operationValidate) {
//            // Si existe una operación activa, mostrar la ubicación completa
//            return view('warehouses.views.warehouses.warehouses.complete')->with([
//                'location' => $location,
//                'warehouse' => $warehouse,
//                'section' => $section,
//                'operation' => $operationValidate,
//            ]);
//        }

        // Si no existe operación, mostrar vista para seleccionar modalidad
        return view('warehouses.views.warehouses.warehouses.modalities.modalitie')->with([
            'warehouse' => $warehouse,
            'location' => $location,
            'section' => $section,
        ]);
    }


    public function automatic($warehouse, $location, $section){

        $warehouse = Warehouse::uid($warehouse);
        $location = WarehouseLocation::uid($location);
        $section = WarehouseLocationSection::uid($section);

        // Validar que los registros existan
        if (!$warehouse || !$location || !$section) {
            return abort(404, 'Warehouse, Location o Section no encontrado');
        }

//        // Crear u obtener operación activa
//        $operation = WarehouseInventoryOperation::getActiveByWarehouse($warehouse->id);
//
//        if (!$operation) {
//            $operation = WarehouseInventoryOperation::create([
//                'warehouse_id' => $warehouse->id,
//                'user_id' => auth()->id(),
//                'started_at' => now(),
//            ]);
//        }

        return view('warehouses.views.warehouses.warehouses.modalities.automatic')->with([
            'warehouse' => $warehouse,
            'location' => $location,
            'section' => $section,
            //'operation' => $operation,
        ]);
    }

    public function manual($warehouse, $location, $section){

        $warehouse = Warehouse::uid($warehouse);
        $location = WarehouseLocation::uid($location);
        $section = WarehouseLocationSection::uid($section);

        // Validar que los registros existan
        if (!$warehouse || !$location || !$section) {
            return abort(404, 'Warehouse, Location o Section no encontrado');
        }

        // Crear u obtener operación activa
        $operation = WarehouseInventoryOperation::getActiveByWarehouse($warehouse->id);

        if (!$operation) {
            $operation = WarehouseInventoryOperation::create([
                'warehouse_id' => $warehouse->id,
                'user_id' => auth()->id(),
                'started_at' => now(),
            ]);
        }

        return view('warehouses.views.warehouses.warehouses.modalities.manual')->with([
            'warehouse' => $warehouse,
            'location' => $location,
            'section' => $section,
            'operation' => $operation,
        ]);
    }

    public function validateGenerate(Request $request){
        // NOTE: This method needs refactoring - validateExits method is deprecated
        // For new warehouse system, use WarehouseLocation with floor_id instead
        $shop  = Shop::uid($request->shop);

        return response()->json([
            'success' => false,
            'message' => 'Método deprecado. Por favor usar nueva API de Warehouse',
        ]);
    }
    public function validateLocation(Request $request){

        $warehouse = app('warehouses');

        $location = WarehouseLocation::uid($request->location);

        if ($location && $location->floor->warehouse_id == $warehouse->id) {

            return response()->json([
                'success' => true,
                'uid'   => $location->uid
            ]);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ubicación no encontrada.'
            ]);
        }

    }



    public function validateSection(Request $request){

        $warehouse = app('warehouses');

        $section = WarehouseLocationSection::barcode($request->section);

        if ($section) {

            $location = $section->location;
            $warehoouse = $section->location->warehouse;

            return response()->json([
                'success' => true,
                'section'   => $section->uid,
                'location'   => $location->uid,
                'warehouse'   => $warehoouse->uid
            ]);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ubicación no encontrada.'
            ]);
        }

    }


    public function validateProduct(Request $request, BarcodeReadingService $barcodeService){

        $request->validate([
            'product' => 'required|string|min:1',
        ]);

        // Usar el servicio centralizado de lectura de códigos
        $result = $barcodeService->validate($request->product);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'code' => $result['code'] ?? 'unknown_error',
        ]);

    }

    public function close(Request $request){

        $user = app('warehouses');
        $locationValidate = Location::uid($request->location);
        $locationItem = InventarieLocation::uid($request->item);

        if ($request->modalitie == 'automatic') {

            $products = json_decode($request->products, true);;


            foreach ($products as $product) {

                $productItem = Product::uid($product['uid']);
                // $locationProductItem = $productItem->localization;
                //$locationProductItem = 1;

                $warehouseItem = new InventarieLocationItem();
                $warehouseItem->uid = $this->generate_uid('inventarie_locations_items');
                $warehouseItem->product_id = $productItem->id;
                $warehouseItem->location_id = $locationItem->id;
                $warehouseItem->original_id = null;
                $warehouseItem->validate_id = $locationValidate->id;
                $warehouseItem->user_id = $user->id;
                $warehouseItem->count = 1;
                $warehouseItem->condition_id = 1;

                // if($warehouseItem->validate_id == $warehouseItem->original_id){
                //    $warehouseItem->condition_id = 1;
                // }

                $warehouseItem->save();
            }


            $itemsGroupedByProduct = $locationItem->items() // Relación de items
            ->select('product_id', DB::raw('count(*) as product_count'))
                ->groupBy('product_id')
                ->get();

            foreach ($itemsGroupedByProduct as $itemGroup) {

                //$product = Product::id($itemGroup->product_id);
                //$shopId = $user->shop_id;

                //if ($product) {
                //    $location = ProductLocation::where('product_id', $product->id)->first();
                //    $location->count+= $itemGroup->product_count;
                //    $location->update();
                //}

                $product = Product::id($itemGroup->product_id);
                $shopId = $user->shop_id;

                $locations = $product->localizations->filter(function($localization) use ($shopId) {
                    return $localization->shop_id == $shopId;
                });$locations->first();

                if ($product) {
                    foreach ($locations as $location) {
                        $location->count+= $itemGroup->product_count;
                        $location->update();
                    }
                }


            }


        }elseif ($request->modalitie == 'manual') {

            $productItem = Product::barcode($request->product);

            // $locationProductItem = $productItem->localization;
            $locationProductItem = 1;

            $warehouseItem = new InventarieLocationItem();
            $warehouseItem->uid = $this->generate_uid('inventarie_locations_items');
            $warehouseItem->product_id = $productItem->id;
            $warehouseItem->location_id = $locationItem->id;
            $warehouseItem->original_id = $locationProductItem;
            $warehouseItem->validate_id = $locationValidate->id;
            $warehouseItem->user_id = $user->id;
            $warehouseItem->count = $request->count;
            $warehouseItem->condition_id = 1;

            // if($warehouseItem->validate_id == $warehouseItem->original_id){
            //    $warehouseItem->condition_id = 1;
            // }
            $warehouseItem->save();

            $itemsGroupedByProduct = $locationItem->items() // Relación de items
            ->select('product_id', DB::raw('SUM(count) as total_count'))
                ->groupBy('product_id')  // Agrupar por 'product_id'
                ->get();

            foreach ($itemsGroupedByProduct as $itemGroup) {

                //$product = Product::id($itemGroup->product_id);
                // $shopId = $user->shop_id;

                // if ($product) {
                //     $location = ProductLocation::where('product_id', $product->id)->first();
                //     $location->count+= $itemGroup->total_count;
                //     $location->update();
                // }

                $product = Product::id($itemGroup->product_id);
                $shopId = $user->shop_id;

                $locations = $product->localizations->filter(function($localization) use ($shopId) {
                    return $localization->shop_id == $shopId;
                });

                if ($product) {
                    foreach ($locations as $location) {
                        $location->count+= $itemGroup->total_count;
                        $location->update();
                    }
                }
            }

        }

        //$locationItem->complete = 1;
        $locationItem->update();

        return response()->json([
            'success' => true,
            'message' => 'Se creo el curso correctamente',
        ]);

    }


}

