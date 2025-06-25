<?php

use App\Http\Controllers\Callcenters\DashboardController;
use App\Http\Controllers\Callcenters\Faqs\FaqsController;
use App\Http\Controllers\Callcenters\Faqs\CategoriesController as FaqsCategoriesController;
use App\Http\Controllers\Callcenters\Returns\ReturnController;
use App\Http\Controllers\Callcenters\Settings\SettingsController;
use App\Http\Controllers\Callcenters\Tickets\CommentsController;
use App\Http\Controllers\Callcenters\Tickets\TicketsController;
use Illuminate\Support\Facades\Route;

Route::prefix('callcenter')->middleware(['auth',  'check.roles.permissions:callcenter'])->name('callcenter.')->group(function () {

        Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');

        Route::prefix('returns')->name('returns.')->group(function () {

            Route::get('/', [ReturnController::class, 'index'])->name('index');
            Route::get('/validate', [ReturnController::class, 'validate'])->name('validate');
            Route::get('/generate/{uid}', [ReturnController::class, 'generate'])->name('generate');
            Route::get('/create', [ReturnController::class, 'create'])->name('create');
            Route::post('/store', [ReturnController::class, 'store'])->name('store');
            Route::post('/update/{id}', [ReturnController::class, 'update'])->name('update');
            Route::get('/edit/{uid}', [ReturnController::class, 'edit'])->name('edit');
            // Ver detalle de devolución
            Route::get('/show/{id}', [ReturnController::class, 'show'])->name('show');
            Route::get('/destroy/{uid}', [ReturnController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/status', [ReturnController::class, 'updateStatus'])->name('status.update');
            Route::post('/{id}/approve', [ReturnController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [ReturnController::class, 'reject'])->name('reject');
            Route::post('/{id}/assign', [ReturnController::class, 'assign'])->name('assign');
            Route::post('/{id}/discussion', [ReturnController::class, 'addDiscussion'])->name('discussion.add');
            Route::post('/{id}/attachment', [ReturnController::class, 'uploadAttachment'])->name('attachment.upload');
            Route::get('/{id}/payments', [ReturnController::class, 'getPayments'])->name('payments');
            Route::post('/{id}/payment', [ReturnController::class, 'addPayment'])->name('payment.add');
            Route::get('/export', [ReturnController::class, 'export'])->name('export');
            Route::get('/{id}/pdf', [ReturnController::class, 'downloadPDF'])->name('pdf');
            Route::post('/bulk-update', [ReturnController::class, 'bulkUpdate'])->name('bulk.update');

            Route::post('/validate-products', [ReturnController::class, 'validateProducts'])->name('validate-products');

            // Cancelar devolución
            Route::post('/{id}/cancel', [ReturnController::class, 'cancel'])->name('cancel');

            // Obtener productos de una orden (AJAX)
            Route::get('/order/{orderId}/products', [ReturnController::class, 'getOrderProducts'])->name('order-products');

                // AJAX endpoints
                Route::post('/carrier-time-slots', [ReturnController::class, 'getCarrierTimeSlots']);
                Route::post('/inpost-lockers', [ReturnController::class, 'getNearbyInPostLockers']);
                Route::post('/inpost-locker-details', [ReturnController::class, 'getInPostLockerDetails']);
                Route::post('/available-stores', [ReturnController::class, 'getAvailableStores']);
                Route::get('/{id}/tracking', [ReturnController::class, 'getTrackingStatus']);
                Route::post('/{id}/cancel-pickup', [ReturnController::class, 'cancelPickup']);
                Route::get('/document/{id}/download', [ReturnController::class, 'downloadDocument'])
                    ->name('returns.documents.download');
                Route::post('/scan-barcode', [ReturnController::class, 'scanBarcode']);


        });

        Route::middleware(['auth'])->group(function () {
            Route::prefix('returns/{return}/costs')->name('returns.costs.')->group(function () {
                Route::get('/', [ReturnCostController::class, 'index'])->name('index');
                Route::post('/', [ReturnCostController::class, 'store'])->name('store');
                Route::post('/apply-automatic', [ReturnCostController::class, 'applyAutomatic'])->name('apply-automatic');
                Route::delete('/{cost}', [ReturnCostController::class, 'destroy'])->name('destroy');
            });
        });

        Route::middleware(['auth', 'check.return.status'])->group(function () {
            // rutas de costos aquí
        });

        Route::middleware(['auth'])->group(function () {
            // Comunicaciones
            Route::prefix('returns/{return}/communications')->name('returns.communications.')->group(function () {
                Route::get('/', [ReturnCommunicationController::class, 'index'])->name('index');
                Route::get('/create', [ReturnCommunicationController::class, 'create'])->name('create');
                Route::post('/', [ReturnCommunicationController::class, 'store'])->name('store');
                Route::get('/{communication}', [ReturnCommunicationController::class, 'show'])->name('show');
                Route::post('/{communication}/resend', [ReturnCommunicationController::class, 'resend'])->name('resend');
                Route::get('/preview/{template}', [ReturnCommunicationController::class, 'preview'])->name('preview');
            });

            // Estadísticas (API)
            Route::get('/returns/{return}/communications/stats', [ReturnCommunicationController::class, 'stats'])
                ->name('returns.communications.stats');
        });

        Route::middleware(['auth'])->group(function () {
            Route::resource('pdf-documents', PdfDocumentController::class);
            Route::get('pdf-documents/{pdfDocument}/download', [PdfDocumentController::class, 'download'])
                ->name('pdf-documents.download');
            Route::get('pdf-documents/{pdfDocument}/preview', [PdfDocumentController::class, 'preview'])
                ->name('pdf-documents.preview');
            Route::post('pdf-documents/{pdfDocument}/regenerate', [PdfDocumentController::class, 'regenerate'])
                ->name('pdf-documents.regenerate');
        });


        Route::prefix('returns')->name('customer.returns.')->group(function () {
            Route::get('/', [ReturnTrackingController::class, 'index'])->name('search');
            Route::post('/search', [ReturnTrackingController::class, 'search'])->name('search.submit');
            Route::get('/{return}', [ReturnTrackingController::class, 'show'])->name('show');
            Route::get('/{return}/download/{type}', [ReturnTrackingController::class, 'downloadDocument'])->name('download');
            Route::post('/{return}/update-email', [ReturnTrackingController::class, 'updateEmail'])->name('update-email');
            Route::get('/{return}/check-updates', [ReturnTrackingController::class, 'checkUpdates'])->name('check-updates');
        });


// Rutas de componentes para usuarios autenticados
        Route::middleware(['auth'])->group(function () {
            // Ver componentes de órdenes propias
            Route::get('orders/{order}/components', [ComponentController::class, 'orderComponents'])->name('orders.components');
            Route::get('order-components/{orderComponent}', [ComponentController::class, 'showOrderComponent'])->name('order-components.show');

            // Aplicar estrategias para componentes faltantes
            Route::post('order-components/{orderComponent}/apply-strategy', [ComponentController::class, 'applyMissingStrategy'])->name('order-components.apply-strategy');

            // Envíos de componentes
            Route::resource('component-shipments', ComponentShipmentController::class)->only(['index', 'show']);
            Route::get('component-shipments/{shipment}/tracking', [ComponentShipmentController::class, 'tracking'])->name('component-shipments.tracking');
        });

// Rutas de administración de componentes
        Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
            // Gestión de componentes
            Route::resource('components', ComponentAdminController::class);

            // Inventario de componentes
            Route::get('components-inventory', [ComponentController::class, 'inventory'])->name('components.inventory');
            Route::get('components-inventory/report', [ComponentController::class, 'inventoryReport'])->name('components.inventory-report');
            Route::get('components-inventory/export', [ComponentController::class, 'exportInventory'])->name('components.export-inventory');

            // Gestión de stock
            Route::post('components/{component}/update-stock', [ComponentController::class, 'updateStock'])->name('components.update-stock');
            Route::post('components/sync-physical-stock', [ComponentController::class, 'syncPhysicalStock'])->name('components.sync-physical-stock');
            Route::post('components/optimize-allocations', [ComponentController::class, 'optimizeAllocations'])->name('components.optimize-allocations');

            // Envíos parciales y gestión
            Route::post('orders/{order}/partial-shipment', [ComponentController::class, 'processPartialShipment'])->name('orders.partial-shipment');
            Route::resource('component-shipments', ComponentShipmentController::class)->except(['index', 'show']);
            Route::post('component-shipments/{shipment}/ship', [ComponentShipmentController::class, 'ship'])->name('component-shipments.ship');
            Route::post('component-shipments/{shipment}/deliver', [ComponentShipmentController::class, 'deliver'])->name('component-shipments.deliver');

            // Sustituciones de componentes
            Route::resource('component-substitutions', ComponentSubstitutionController::class);

            // Retornos de componentes
            Route::resource('component-returns', ComponentReturnController::class);
        });

// API Routes para componentes
        Route::middleware(['auth'])->prefix('api')->group(function () {
            // Búsqueda y consultas
            Route::get('components/search', [ComponentController::class, 'search'])->name('api.components.search');
            Route::get('components/{component}/alternatives', [ComponentController::class, 'getAlternatives'])->name('api.components.alternatives');

            // Información de stock y estado
            Route::get('components/{component}/stock-status', [ComponentController::class, 'getStockStatus'])->name('api.components.stock-status');
            Route::get('orders/{order}/component-summary', [ComponentController::class, 'getComponentSummary'])->name('api.orders.component-summary');

            // Tracking de envíos
            Route::get('component-shipments/{shipment}/track', [ComponentShipmentController::class, 'trackShipment'])->name('api.component-shipments.track');
        });


        Route::get('/track/email', [ReturnCommunicationController::class, 'track'])->name('email.track');


// Rutas de garantías para usuarios autenticados
        Route::middleware(['auth'])->group(function () {
            // Gestión de garantías del usuario
            Route::resource('warranties', WarrantyController::class)->only(['index', 'show']);

            // Acciones específicas de garantías
            Route::post('warranties/{warranty}/activate', [WarrantyController::class, 'activate'])->name('warranties.activate');
            Route::post('warranties/{warranty}/extend', [WarrantyController::class, 'extend'])->name('warranties.extend');
            Route::post('warranties/{warranty}/transfer', [WarrantyController::class, 'transfer'])->name('warranties.transfer');
            Route::post('warranties/{warranty}/register-manufacturer', [WarrantyController::class, 'registerWithManufacturer'])->name('warranties.register-manufacturer');
            Route::get('warranties/{warranty}/certificate', [WarrantyController::class, 'downloadCertificate'])->name('warranties.certificate');

            // Crear reclamos de garantía
            Route::get('warranties/{warranty}/claims/create', [WarrantyController::class, 'createClaim'])->name('warranties.claims.create');
            Route::post('warranties/{warranty}/claims', [WarrantyController::class, 'storeClaim'])->name('warranties.claims.store');

            // Gestión de reclamos de garantía
            Route::resource('warranty-claims', WarrantyClaimController::class)->only(['index', 'show', 'update']);
            Route::post('warranty-claims/{claim}/cancel', [WarrantyClaimController::class, 'cancel'])->name('warranty-claims.cancel');
            Route::post('warranty-claims/{claim}/evaluate', [WarrantyClaimController::class, 'evaluate'])->name('warranty-claims.evaluate');
            Route::post('warranty-claims/{claim}/reopen', [WarrantyClaimController::class, 'reopen'])->name('warranty-claims.reopen');
        });

// API Routes
        Route::middleware(['auth'])->prefix('api')->group(function () {
            // Búsqueda de garantías
            Route::post('warranties/lookup', [WarrantyController::class, 'lookup'])->name('api.warranties.lookup');
            Route::get('warranties/expiring', [WarrantyController::class, 'getExpiringWarranties'])->name('api.warranties.expiring');

            // Reclamos
            Route::get('warranty-claims/{claim}/manufacturer-status', [WarrantyClaimController::class, 'getManufacturerStatus'])->name('api.warranty-claims.manufacturer-status');
            Route::get('warranty-claims/{claim}/communication-log', [WarrantyClaimController::class, 'getCommunicationLog'])->name('api.warranty-claims.communication-log');
            Route::get('warranty-claims/stats', [WarrantyClaimController::class, 'getStats'])->name('api.warranty-claims.stats');
        });


// Rutas de administración (requieren rol admin)
        Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
            // Gestión de reglas de devolución
            Route::resource('return-rules', ProductReturnRuleController::class)->names([
                'index' => 'return-rules.index',
                'create' => 'return-rules.create',
                'store' => 'return-rules.store',
                'show' => 'return-rules.show',
                'edit' => 'return-rules.edit',
                'update' => 'return-rules.update',
                'destroy' => 'return-rules.destroy',
            ]);

            // Acciones adicionales para reglas
            Route::post('return-rules/{returnRule}/toggle', [ProductReturnRuleController::class, 'toggleStatus'])->name('return-rules.toggle');
            Route::get('return-rules/{returnRule}/clone', [ProductReturnRuleController::class, 'clone'])->name('return-rules.clone');
            Route::get('return-rules/export', [ProductReturnRuleController::class, 'export'])->name('return-rules.export');
            Route::post('return-rules/{returnRule}/test', [ProductReturnRuleController::class, 'test'])->name('return-rules.test');
        });


// Rutas de notificaciones (requieren autenticación)
        Route::middleware(['auth'])->group(function () {
            // Notificaciones
            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
            Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-all-read');
            Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
            Route::get('/notifications/unread', [NotificationController::class, 'getUnread'])->name('notifications.unread');

            // Tokens push
            Route::post('/notifications/register-push-token', [NotificationController::class, 'registerPushToken']);
            Route::post('/notifications/unregister-push-token', [NotificationController::class, 'unregisterPushToken']);

            // Configuraciones de notificaciones
            Route::get('/notifications/settings', [NotificationSettingsController::class, 'index'])->name('notifications.settings');
            Route::put('/notifications/settings', [NotificationSettingsController::class, 'update'])->name('notifications.settings.update');
            Route::get('/notifications/settings/reset', [NotificationSettingsController::class, 'reset'])->name('notifications.settings.reset');
            Route::post('/notifications/test', [NotificationSettingsController::class, 'test'])->name('notifications.test');
        });


// Rutas de validación de devoluciones (requieren autenticación)
        Route::middleware(['auth'])->group(function () {
            // Verificar elegibilidad para devolución
            Route::post('returns/check-eligibility', [ReturnValidationController::class, 'checkReturnEligibility'])->name('returns.check-eligibility');

            // Crear solicitud de devolución con validación
            Route::post('returns/create-request', [ReturnValidationController::class, 'createReturnRequest'])->name('returns.create-request');

            // Historial de validaciones del usuario
            Route::get('returns/my-validations', [ReturnValidationController::class, 'getUserValidations'])->name('returns.my-validations');

            // Ver detalles de validación específica
            Route::get('returns/validations/{validation}', [ReturnValidationController::class, 'show'])->name('returns.validation.show');
        });

// Rutas de administración adicionales
        Route::middleware(['auth', 'role:admin'])->group(function () {
            // Revalidar devolución
            Route::post('returns/validations/{validation}/revalidate', [ReturnValidationController::class, 'revalidate'])->name('returns.validation.revalidate');

            // Estadísticas de validaciones
            Route::get('returns/validation-stats', [ReturnValidationController::class, 'getValidationStats'])->name('returns.validation-stats');
        });


// Rutas de administración
        Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
            // Gestión de garantías (admin)
            Route::resource('warranties', WarrantyAdminController::class);
            Route::resource('warranty-types', WarrantyTypeController::class);
            Route::resource('manufacturers', ManufacturerController::class);

            // Gestión de reclamos (admin)
            Route::resource('warranty-claims', WarrantyClaimAdminController::class);
            Route::post('warranty-claims/{claim}/assign', [WarrantyClaimAdminController::class, 'assign'])->name('warranty-claims.assign');
            Route::post('warranty-claims/{claim}/status', [WarrantyClaimAdminController::class, 'changeStatus'])->name('warranty-claims.status');
            Route::post('warranty-claims/{claim}/submit-manufacturer', [WarrantyClaimAdminController::class, 'submitToManufacturer'])->name('warranty-claims.submit-manufacturer');

            // Reportes y estadísticas
            Route::get('warranties/reports/dashboard', [WarrantyAdminController::class, 'dashboard'])->name('warranties.dashboard');
            Route::get('warranties/reports/export', [WarrantyAdminController::class, 'export'])->name('warranties.export');
        });


// 5. POLICY: app/Policies/ReturnPolicy.php
        /*


        public function manageCommunications(User $user, Return $return): bool
        {
            return $user->hasRole(['admin', 'support', 'returns_manager']) ||
                   $user->id === $return->assigned_to;
        }

        // Rutas para API
                Route::middleware(['auth:sanctum'])->prefix('api/v1')->group(function () {
                    Route::prefix('returns/{return}/costs')->group(function () {
                        Route::get('/', [ReturnCostController::class, 'index']);
                        Route::post('/', [ReturnCostController::class, 'store']);
                        Route::post('/apply-automatic', [ReturnCostController::class, 'applyAutomatic']);
                        Route::delete('/{cost}', [ReturnCostController::class, 'destroy']);
                        Route::get('/summary', [ReturnCostController::class, 'summary']);
                    });
                });
                */

        Route::prefix('settings')->group(function () {
            Route::get('/profile', [SettingsController::class, 'profile'])->name('settings.profile');
            Route::get('/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
            Route::post('/profile/update', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
            Route::post('/notifications/update', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
        });

    });
