<?php

use Illuminate\Support\Facades\Route;
use Modules\Returns\Http\Controllers\Callcenters\ComponentController;
use Modules\Returns\Http\Controllers\Callcenters\InspectionController;
use Modules\Returns\Http\Controllers\Callcenters\PdfDocumentController;
use Modules\Returns\Http\Controllers\Callcenters\ReturnCommunicationController;
use Modules\Returns\Http\Controllers\Callcenters\ReturnCostController;
use Modules\Returns\Http\Controllers\Callcenters\ReturnsController;
use Modules\Returns\Http\Controllers\Callcenters\ReturnTrackingController;
use Modules\Returns\Http\Controllers\Callcenters\WarrantyClaimController;
use Modules\Returns\Http\Controllers\Callcenters\WarrantyController;

/*
|--------------------------------------------------------------------------
| Callcenter Return Routes
|--------------------------------------------------------------------------
|
| Routes for callcenter return requests management
|
*/

// Main returns management
Route::group(['prefix' => 'returns', 'name' => 'returns.'], function () {
    Route::get('/', [ReturnsController::class, 'index'])->name('index');
    Route::get('/create', [ReturnsController::class, 'create'])->name('create');
    Route::post('/store', [ReturnsController::class, 'store'])->name('store');
    Route::post('/update/{id}', [ReturnsController::class, 'update'])->name('update');
    Route::get('/edit/{uid}', [ReturnsController::class, 'edit'])->name('edit');
    Route::get('/show/{id}', [ReturnsController::class, 'show'])->name('show');
    Route::get('/destroy/{uid}', [ReturnsController::class, 'destroy'])->name('destroy');

    Route::post('/validateorder', [ReturnsController::class, 'validateOrder'])->name('validate.order');
    Route::post('/proceed-to-generate', [ReturnsController::class, 'proceedToGenerate'])->name('proceed.to.generate');

    // AJAX endpoints for advanced functionality
    Route::post('/validate-inventaries', [ReturnsController::class, 'validateProducts'])->name('validate-inventaries');
    Route::get('/generate/{uid}', [ReturnsController::class, 'generate'])->name('generate');
    Route::get('/available-inventaries/{orderId}', [ReturnsController::class, 'getAvailableProducts'])->name('available.inventaries');

    // Complete return process routes
    Route::get('/review/{returnId}', [ReturnsController::class, 'review'])->name('review');
    Route::post('/confirm/{returnId}', [ReturnsController::class, 'confirm'])->name('confirm');
    Route::get('/success/{returnId}', [ReturnsController::class, 'success'])->name('success');

    // Return status and management
    Route::post('/{id}/status', [ReturnsController::class, 'updateStatus'])->name('status.update');
    Route::post('/{id}/approve', [ReturnsController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [ReturnsController::class, 'reject'])->name('reject');
    Route::post('/{id}/assign', [ReturnsController::class, 'assign'])->name('assign');
    Route::post('/{id}/cancel', [ReturnsController::class, 'cancel'])->name('cancel');

    // Return order products
    Route::get('/order/{orderId}/inventaries', [ReturnsController::class, 'getOrderProducts'])->name('order-inventaries');

    // Carrier and logistics
    Route::post('/carrier-time-slots', [ReturnsController::class, 'getCarrierTimeSlots']);
    Route::post('/inpost-lockers', [ReturnsController::class, 'getNearbyInPostLockers']);
    Route::post('/inpost-locker-details', [ReturnsController::class, 'getInPostLockerDetails']);
    Route::post('/available-stores', [ReturnsController::class, 'getAvailableStores']);

    // Tracking and documents
    Route::get('/{id}/tracking', [ReturnsController::class, 'getTrackingStatus']);
    Route::post('/{id}/cancel-pickup', [ReturnsController::class, 'cancelPickup']);
    Route::get('/document/{id}/download', [ReturnsController::class, 'downloadDocument'])->name('documents.download');
    Route::post('/scan-barcode', [ReturnsController::class, 'scanBarcode']);
});

// Return costs management
Route::group(['prefix' => 'returns/{return}/costs', 'name' => 'costs.'], function () {
    Route::get('/', [ReturnCostController::class, 'index'])->name('index');
    Route::post('/', [ReturnCostController::class, 'store'])->name('store');
    Route::post('/automatic', [ReturnCostController::class, 'applyAutomatic'])->name('automatic');
    Route::delete('/{cost}', [ReturnCostController::class, 'destroy'])->name('destroy');
    Route::get('/summary', [ReturnCostController::class, 'summary'])->name('summary');
});

// Return communication management
Route::group(['prefix' => 'returns/{return}/communications', 'name' => 'communications.'], function () {
    Route::get('/', [ReturnCommunicationController::class, 'index'])->name('index');
    Route::get('/create', [ReturnCommunicationController::class, 'create'])->name('create');
    Route::post('/', [ReturnCommunicationController::class, 'store'])->name('store');
    Route::get('/{communication}', [ReturnCommunicationController::class, 'show'])->name('show');
    Route::post('/{communication}/resend', [ReturnCommunicationController::class, 'resend'])->name('resend');
    Route::post('/preview', [ReturnCommunicationController::class, 'preview'])->name('preview');
    Route::get('/track', [ReturnCommunicationController::class, 'track'])->name('track');
    Route::get('/stats', [ReturnCommunicationController::class, 'stats'])->name('stats');
});

// Return inspection management
Route::group(['prefix' => 'inspections', 'name' => 'inspections.'], function () {
    Route::get('/', [InspectionController::class, 'index'])->name('index');
    Route::post('/scan', [InspectionController::class, 'scan'])->name('scan');
    Route::get('/create', [InspectionController::class, 'create'])->name('create');
    Route::post('/', [InspectionController::class, 'store'])->name('store');
    Route::get('/{inspection}', [InspectionController::class, 'show'])->name('show');
    Route::get('/{inspection}/review', [InspectionController::class, 'review'])->name('review');
    Route::post('/{inspection}/process-review', [InspectionController::class, 'processReview'])->name('process-review');
    Route::post('/bulk-inspect', [InspectionController::class, 'bulkInspect'])->name('bulk-inspect');
    Route::get('/{returnRequest}/report', [InspectionController::class, 'report'])->name('report');
});

// Return tracking
Route::group(['prefix' => 'tracking', 'name' => 'tracking.'], function () {
    Route::get('/', [ReturnTrackingController::class, 'index'])->name('index');
    Route::post('/search', [ReturnTrackingController::class, 'search'])->name('search');
    Route::get('/{return}', [ReturnTrackingController::class, 'show'])->name('show');
    Route::get('/{return}/document/{type}', [ReturnTrackingController::class, 'downloadDocument'])->name('download-document');
    Route::post('/{return}/update-email', [ReturnTrackingController::class, 'updateEmail'])->name('update-email');
    Route::post('/{return}/check-updates', [ReturnTrackingController::class, 'checkUpdates'])->name('check-updates');
});

// PDF Document management
Route::group(['prefix' => 'pdf-documents', 'name' => 'pdf-documents.'], function () {
    Route::get('/', [PdfDocumentController::class, 'index'])->name('index');
    Route::get('/create', [PdfDocumentController::class, 'create'])->name('create');
    Route::post('/', [PdfDocumentController::class, 'store'])->name('store');
    Route::get('/{ReturnPdfDocument}', [PdfDocumentController::class, 'show'])->name('show');
    Route::get('/{ReturnPdfDocument}/download', [PdfDocumentController::class, 'download'])->name('download');
    Route::get('/{ReturnPdfDocument}/preview', [PdfDocumentController::class, 'preview'])->name('preview');
    Route::post('/{ReturnPdfDocument}/regenerate', [PdfDocumentController::class, 'regenerate'])->name('regenerate');
    Route::delete('/{ReturnPdfDocument}', [PdfDocumentController::class, 'destroy'])->name('destroy');
});

// Component management
Route::group(['prefix' => 'components', 'name' => 'components.'], function () {
    Route::get('/inventory', [ComponentController::class, 'inventory'])->name('inventory');
    Route::get('/inventory-report', [ComponentController::class, 'inventoryReport'])->name('inventory-report');
    Route::post('/inventory-export', [ComponentController::class, 'exportInventory'])->name('inventory-export');
    Route::post('/sync-physical-stock', [ComponentController::class, 'syncPhysicalStock'])->name('sync-physical-stock');
    Route::get('/optimize-allocations', [ComponentController::class, 'optimizeAllocations'])->name('optimize-allocations');
    Route::post('/search', [ComponentController::class, 'search'])->name('search');

    Route::get('/{component}', [ComponentController::class, 'show'])->name('show');
    Route::post('/{component}/update-stock', [ComponentController::class, 'updateStock'])->name('update-stock');
    Route::get('/{component}/alternatives', [ComponentController::class, 'getAlternatives'])->name('alternatives');

    Route::get('/order/{order}', [ComponentController::class, 'orderComponents'])->name('order-components');
    Route::get('/order-component/{orderComponent}', [ComponentController::class, 'showOrderComponent'])->name('show-order-component');
    Route::post('/order-component/{orderComponent}/apply-missing-strategy', [ComponentController::class, 'applyMissingStrategy'])->name('apply-missing-strategy');
    Route::post('/order/{order}/process-partial-shipment', [ComponentController::class, 'processPartialShipment'])->name('process-partial-shipment');
});

// Warranty management
Route::group(['prefix' => 'warranties', 'name' => 'warranties.'], function () {
    Route::get('/', [WarrantyController::class, 'index'])->name('index');
    Route::get('/{warranty}', [WarrantyController::class, 'show'])->name('show');
    Route::post('/{warranty}/activate', [WarrantyController::class, 'activate'])->name('activate');
    Route::post('/{warranty}/extend', [WarrantyController::class, 'extend'])->name('extend');
    Route::post('/{warranty}/transfer', [WarrantyController::class, 'transfer'])->name('transfer');
    Route::get('/{warranty}/certificate', [WarrantyController::class, 'downloadCertificate'])->name('certificate');
    Route::post('/{warranty}/register-manufacturer', [WarrantyController::class, 'registerWithManufacturer'])->name('register-manufacturer');
    Route::post('/lookup', [WarrantyController::class, 'lookup'])->name('lookup');
    Route::get('/expiring/list', [WarrantyController::class, 'getExpiringWarranties'])->name('expiring');

    // Warranty claims
    Route::get('/{warranty}/claims/create', [WarrantyController::class, 'createClaim'])->name('claims.create');
    Route::post('/{warranty}/claims', [WarrantyController::class, 'storeClaim'])->name('claims.store');
});

// Warranty claims management
Route::group(['prefix' => 'warranty-claims', 'name' => 'warranty-claims.'], function () {
    Route::get('/', [WarrantyClaimController::class, 'index'])->name('index');
    Route::get('/{claim}', [WarrantyClaimController::class, 'show'])->name('show');
    Route::post('/{claim}', [WarrantyClaimController::class, 'update'])->name('update');
    Route::post('/{claim}/cancel', [WarrantyClaimController::class, 'cancel'])->name('cancel');
    Route::post('/{claim}/evaluate', [WarrantyClaimController::class, 'evaluate'])->name('evaluate');
    Route::post('/{claim}/reopen', [WarrantyClaimController::class, 'reopen'])->name('reopen');
    Route::get('/{claim}/status', [WarrantyClaimController::class, 'getManufacturerStatus'])->name('status');
    Route::get('/{claim}/communication-log', [WarrantyClaimController::class, 'getCommunicationLog'])->name('communication-log');
    Route::get('/stats', [WarrantyClaimController::class, 'getStats'])->name('stats');
});
