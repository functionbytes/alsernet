<?php

use App\Http\Controllers\Managers\DashboardController;
use App\Http\Controllers\Managers\Events\EventsController;
use App\Http\Controllers\Managers\Inventaries\InventariesController;
use App\Http\Controllers\Managers\Inventaries\LocationssController as InventariessLocationsController;
use App\Http\Controllers\Managers\Livechat\LivechatController;
use App\Http\Controllers\Managers\Newsletters\NewslettersListsUserController;
use App\Http\Controllers\Managers\Newsletters\NewslettersReportController;
use App\Http\Controllers\Managers\Products\BarcodeController as ProductsBarcodesController;
use App\Http\Controllers\Managers\Products\ProductsController;
use App\Http\Controllers\Managers\Products\ReportController;
use App\Http\Controllers\Managers\Settings\EmailsSettingsController;
use App\Http\Controllers\Managers\Settings\HoursSettingsController;
use App\Http\Controllers\Managers\Settings\LiveSettingsController;
use App\Http\Controllers\Managers\Settings\MantenanceSettingsController;
use App\Http\Controllers\Managers\Settings\SettingsController;
use App\Http\Controllers\Managers\Settings\TicketsSettingsController;
use App\Http\Controllers\Managers\Shops\Locations\BarcodeController as LocationsBarcodesController;
use App\Http\Controllers\Managers\Shops\Locations\LocationsController as ShopsLocationsController;
use App\Http\Controllers\Managers\Shops\Shops\ShopsController;
use App\Http\Controllers\Managers\Users\UsersController;
use Illuminate\Support\Facades\Route;



use App\Http\Controllers\Managers\Faqs\CategoriesController as FaqsCategoriesController;
use App\Http\Controllers\Managers\Faqs\FaqsController;

use App\Http\Controllers\Managers\Newsletters\NewslettersController;
use App\Http\Controllers\Managers\Newsletters\NewslettersConditionsController;
use App\Http\Controllers\Managers\Newsletters\NewslettersListsController;



use App\Http\Controllers\Managers\Tickets\CannedsController as CannedsTicketsController;
use App\Http\Controllers\Managers\Tickets\CategoriesController as CategoriesTicketsController;
use App\Http\Controllers\Managers\Tickets\GroupsController as GroupsTicketsController;
use App\Http\Controllers\Managers\Tickets\PrioritiesController as PrioritiesTicketsController;
use App\Http\Controllers\Managers\Tickets\StatusController as StatusTicketsController;
use App\Http\Controllers\Managers\Tickets\TicketsController;



Route::group(['prefix' => 'manager', 'middleware' => ['auth', 'roles:managers']], function () {

    Route::get('/', [DashboardController::class, 'dashboard'])->name('manager.dashboard');

    Route::group(['prefix' => 'shops'], function () {

        Route::get('/', [ShopsController::class, 'index'])->name('manager.shops');
        Route::get('/create', [ShopsController::class, 'create'])->name('manager.shops.create');
        Route::post('/update', [ShopsController::class, 'update'])->name('manager.shops.update');
        Route::get('/edit/{uid}', [ShopsController::class, 'edit'])->name('manager.shops.edit');
        Route::get('/view/{uid}', [ShopsController::class, 'view'])->name('manager.shops.view');
        Route::get('/destroy/{uid}', [ShopsController::class, 'destroy'])->name('manager.shops.destroy');
        Route::get('/locations/{uid}', [ShopsLocationsController::class, 'index'])->name('manager.shops.locations');

        Route::post('/locations/store', [ShopsLocationsController::class, 'store'])->name('manager.shops.locations.store');
        Route::post('/locations/update', [ShopsLocationsController::class, 'update'])->name('manager.shops.locations.update');
        Route::get('/locations/create/{uid}', [ShopsLocationsController::class, 'create'])->name('manager.shops.locations.create');
        Route::get('/locations/edit/{uid}', [ShopsLocationsController::class, 'edit'])->name('manager.shops.locations.edit');
        Route::get('/locations/view/{uid}', [ShopsLocationsController::class, 'view'])->name('manager.shops.locations.view');
        Route::get('/locations/exists/{uid}', [ShopsLocationsController::class, 'exists'])->name('manager.shops.locations.exists');
        Route::get('/locations/destroy/{uid}', [ShopsLocationsController::class, 'destroy'])->name('manager.shops.locations.destroy');
        Route::get('/locations/all/barcode', [LocationsBarcodesController::class, 'index'])->name('manager.shops.locations.barcodes.all');
        Route::get('/locations/single/barcode/{uid}', [LocationsBarcodesController::class, 'destroy'])->name('manager.shops.locations.barcodes.single');
        Route::get('/locations/historys/{uid}', [ShopsLocationsController::class, 'history'])->name('manager.shops.locations.history');

        Route::post('/locations/exists/validate', [ShopsLocationsController::class, 'validate'])->name('manager.shops.locations.exists.validate');

    });

    Route::group(['prefix' => 'products'], function () {

        Route::get('/validate', [ProductsController::class, 'validate'])->name('manager.products');
        Route::get('/validate/products', [ProductsController::class, 'validateProductShop'])->name('manager.products.shop');
        Route::get('/validate/productss', [ProductsController::class, 'validateProductShops'])->name('manager.products.shop');
        Route::get('/validate/apps', [ProductsController::class, 'validateManagement'])->name('manager.products.apps');

        Route::get('/', [ProductsController::class, 'index'])->name('manager.products');
        Route::get('/all/barcode', [ProductsBarcodesController::class, 'index'])->name('manager.products.barcodes.all');
        Route::get('/reporte/generate/inventary', [ReportController::class, 'generateInventary'])->name('manager.products.generate.inventary');
        Route::get('/reporte/generate/kardex', [ReportController::class, 'generateKardex'])->name('manager.products.generate.kardex');
        Route::get('/create', [ProductsController::class, 'create'])->name('manager.products.create');
        Route::post('/store', [ProductsController::class, 'store'])->name('manager.products.store');
        Route::post('/update', [ProductsController::class, 'update'])->name('manager.products.update');
        Route::get('/edit/{uid}', [ProductsController::class, 'edit'])->name('manager.products.edit');
        Route::get('/view/{uid}', [ProductsController::class, 'view'])->name('manager.locations.view');
        Route::get('/destroy/{uid}', [ProductsController::class, 'destroy'])->name('manager.products.destroy');


        Route::get('/locations/{uid}', [ProductsController::class, 'locations'])->name('manager.products.locations');
        Route::get('/locations/details/{uid}', [ProductsController::class, 'details'])->name('manager.products.locations.details');

        Route::get('/single/barcode/{uid}', [ProductsBarcodesController::class, 'destroy'])->name('manager.products.barcodes.single');
    });


    Route::group(['prefix' => 'inventaries'], function () {

        Route::get('/', [InventariesController::class, 'index'])->name('manager.inventaries');
        Route::get('/create', [InventariesController::class, 'create'])->name('manager.inventaries.create');
        Route::post('/update', [InventariesController::class, 'update'])->name('manager.inventaries.update');
        Route::get('/edit/{uid}', [InventariesController::class, 'edit'])->name('manager.inventaries.edit');
        Route::get('/view/{uid}', [InventariesController::class, 'view'])->name('manager.inventaries.view');
        Route::get('/destroy/{uid}', [InventariesController::class, 'destroy'])->name('manager.inventaries.destroy');
        Route::get('/report/{uid}', [InventariesController::class, 'report'])->name('manager.inventaries.report');

        Route::get('/historys/{uid}', [NewslettersController::class, 'index'])->name('manager.inventaries.historys');
        Route::get('/history/edit/{uid}', [NewslettersController::class, 'edit'])->name('manager.historys.edit');
        Route::get('/history/destroy/{uid}', [NewslettersController::class, 'destroy'])->name('manager.historys.destroy');
        Route::get('/history/update', [NewslettersController::class, 'update'])->name('manager.historys.update');

        Route::get('/historys/locations/{uid}', [InventariesLocationsController::class, 'index'])->name('manager.inventaries.locations');
        Route::get('/history/locations/details/{uid}', [InventariesLocationsController::class, 'details'])->name('manager.inventaries.locations.details');
        Route::get('/history/locations/edit/{uid}', [InventariesLocationsController::class, 'edit'])->name('manager.inventaries.locations.edit');
        Route::get('/history/locations/destroy/{uid}', [InventariesLocationsController::class, 'destroy'])->name('manager.inventaries.locations.destroy');
        Route::post('/history/locations/update', [InventariesLocationsController::class, 'update'])->name('manager.inventaries.locations.update');

        Route::get('/history/locations/destroy/items/{uid}', [InventariesLocationsController::class, 'destroyItem'])->name('manager.historys.items.destroy');
        Route::get('/historys/locationss/{uid}', [InventariessLocationsController::class, 'index'])->name('manager.inventaries.locationss');

    });


    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', [SettingsController::class, 'index'])->name('manager.settings');
        Route::post('/update', [SettingsController::class, 'update'])->name('manager.settings.update');

    });


    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UsersController::class, 'index'])->name('manager.users');
        Route::get('/create', [UsersController::class, 'create'])->name('manager.users.create');
        Route::post('/store', [UsersController::class, 'store'])->name('manager.users.store');
        Route::post('/update', [UsersController::class, 'update'])->name('manager.users.update');
        Route::get('/edit/{uid}', [UsersController::class, 'edit'])->name('manager.users.edit');
        Route::get('/view/{uid}', [UsersController::class, 'view'])->name('manager.users.view');
        Route::get('/destroy/{uid}', [UsersController::class, 'destroy'])->name('manager.users.destroy');

    });


    Route::group(['prefix' => 'events'], function () {
        Route::get('/', [EventsController::class, 'index'])->name('manager.events');
        Route::get('/create', [EventsController::class, 'create'])->name('manager.events.create');
        Route::post('/store', [UsersController::class, 'store'])->name('manager.events.store');
        Route::post('/update', [EventsController::class, 'update'])->name('manager.events.update');
        Route::get('/create', [EventsController::class, 'create'])->name('manager.events.create');
        Route::get('/edit/{uid}', [EventsController::class, 'edit'])->name('manager.events.edit');
        Route::get('/view/{uid}', [EventsController::class, 'view'])->name('manager.events.view');
        Route::get('/destroy/{uid}', [EventsController::class, 'destroy'])->name('manager.events.destroy');

    });


    Route::group(['prefix' => 'newsletters'], function () {

        Route::get('/', [NewslettersController::class, 'index'])->name('manager.newsletters');
        Route::get('/create', [NewslettersController::class, 'create'])->name('manager.newsletters.create');
        Route::get('/lists', [NewslettersListsController::class, 'index'])->name('manager.newsletters.lists');
        Route::post('/update', [NewslettersController::class, 'update'])->name('manager.newsletters.update');

        Route::get('/lists/report', [NewslettersListsController::class, 'report'])->name('manager.newsletters.lists.reports');
        Route::get('/lists/create', [NewslettersListsController::class, 'create'])->name('manager.newsletters.lists.create');
        Route::post('/lists/update', [NewslettersListsController::class, 'update'])->name('manager.newsletters.lists.update');
        Route::post('/lists/store', [NewslettersListsController::class, 'store'])->name('manager.newsletters.lists.store');
        Route::get('/edit/{uid}', [NewslettersController::class, 'edit'])->name('manager.newsletters.edit');
        Route::get('/view/{uid}', [NewslettersController::class, 'view'])->name('manager.newsletters.view');
        Route::get('/destroy/{uid}', [NewslettersController::class, 'destroy'])->name('manager.newsletters.destroy');
        Route::get('/list/{uid}', [NewslettersController::class, 'list'])->name('manager.newsletters.list');


        Route::get('/lists/reports', [NewslettersReportController::class, 'report'])->name('manager.newsletters.lists.reports');
        Route::get('/lists/details/{uid}', [NewslettersListsController::class, 'details'])->name('manager.newsletters.lists.details');
        Route::get('/lists/edit/{uid}', [NewslettersListsController::class, 'edit'])->name('manager.newsletters.lists.edit');
        Route::get('/lists/view/{uid}', [NewslettersListsController::class, 'view'])->name('manager.newsletters.lists.view');
        Route::get('/lists/destroy/{uid}', [NewslettersListsController::class, 'destroy'])->name('manager.newsletters.lists.destroy');
        Route::get('/lists/includes/{uid}', [NewslettersListsController::class, 'includes'])->name('manager.newsletters.lists.includes');
        Route::post('/lists/includes/update', [NewslettersListsController::class, 'updateIncludes'])->name('manager.newsletters.lists.includes.update');

        Route::get('/lists/report/generate', [NewslettersReportController::class, 'generate'])->name('manager.newsletters.lists.reports.generate');

        Route::get('/conditions', [NewslettersConditionsController::class, 'index'])->name('manager.newsletters.conditions');
        Route::get('/conditions/create', [NewslettersConditionsController::class, 'create'])->name('manager.newsletters.conditions.create');
        Route::post('/conditions/store', [NewslettersConditionsController::class, 'store'])->name('manager.newsletters.conditions.store');
        Route::post('/conditions/update', [NewslettersConditionsController::class, 'update'])->name('manager.newsletters.conditions.update');
        Route::get('/conditions/edit/{uid}', [NewslettersConditionsController::class, 'edit'])->name('manager.newsletters.conditions.edit');
        Route::get('/conditions/view/{uid}', [NewslettersConditionsController::class, 'view'])->name('manager.newsletters.conditions.view');
        Route::get('/conditions/destroy/{uid}', [NewslettersConditionsController::class, 'destroy'])->name('manager.newsletters.conditions.destroy');


        Route::get('/lists/destroy/newsletter/{uid}', [NewslettersListsUserController::class, 'destroy'])->name('manager.newsletters.lists.user.destroy');

    });





    Route::group(['prefix' => 'settings'], function () {

        Route::get('/', [SettingsController::class, 'index'])->name('manager.settings');
        Route::post('/update', [SettingsController::class, 'update'])->name('manager.settings.update');

        Route::post('/favicon', [SettingsController::class, 'storeFavicon'])->name('manager.settings.favicon');
        Route::get('/delete/favicon/{id}', [SettingsController::class, 'deleteFavicon'])->name('manager.settings.favicon.delete');
        Route::get('/get/favicon/{id}', [SettingsController::class, 'getFavicon'])->name('manager.settings.favicon.get');

        Route::post('/logo', [SettingsController::class, 'storeLogo'])->name('manager.settings.logo');
        Route::get('/delete/logo/{id}', [SettingsController::class, 'deleteLogo'])->name('manager.settings.logo.delete');
        Route::get('/get/logo/{id}', [SettingsController::class, 'getLogo'])->name('manager.settings.logo.get');

        Route::get('/maintenance', [MantenanceSettingsController::class, 'index'])->name('manager.settings.maintenance');
        Route::post('/maintenance/update', [MantenanceSettingsController::class, 'update'])->name('manager.settings.maintenance.update');

        Route::get('/tickets', [TicketsSettingsController::class, 'index'])->name('manager.settings.tickets');
        Route::post('/tickets/update', [TicketsSettingsController::class, 'update'])->name('manager.settings.tickets.update');

        Route::get('/lives', [LiveSettingsController::class, 'index'])->name('manager.settings.lives');
        Route::post('/lives/update', [LiveSettingsController::class, 'update'])->name('manager.settings.lives.update');

        Route::get('/emails', [EmailsSettingsController::class, 'index'])->name('manager.settings.emails');
        Route::post('/emails/update', [EmailsSettingsController::class, 'update'])->name('manager.settings.emails.update');

        Route::get('/hours', [HoursSettingsController::class, 'index'])->name('manager.settings.hours');
        Route::post('/hours/update', [HoursSettingsController::class, 'update'])->name('manager.settings.hours.update');

    });





    Route::group(['prefix' => 'faqs'], function () {

        Route::get('/', [FaqsController::class, 'index'])->name('manager.faqs');
        Route::get('/create', [FaqsController::class, 'create'])->name('manager.faqs.create');
        Route::post('/store', [FaqsController::class, 'store'])->name('manager.faqs.store');
        Route::post('/update', [FaqsController::class, 'update'])->name('manager.faqs.update');
        Route::get('/edit/{uid}', [FaqsController::class, 'edit'])->name('manager.faqs.edit');
        Route::get('/destroy/{uid}', [FaqsController::class, 'destroy'])->name('manager.faqs.destroy');

        Route::get('/categories', [FaqsCategoriesController::class, 'index'])->name('manager.faqs.categories');
        Route::get('/categories/create', [FaqsCategoriesController::class, 'create'])->name('manager.faqs.categories.create');
        Route::post('/categories/store', [FaqsCategoriesController::class, 'store'])->name('manager.faqs.categories.store');
        Route::post('/categories/update', [FaqsCategoriesController::class, 'update'])->name('manager.faqs.categories.update');
        Route::get('/categories/edit/{uid}', [FaqsCategoriesController::class, 'edit'])->name('manager.faqs.categories.edit');
        Route::get('/categories/destroy/{uid}', [FaqsCategoriesController::class, 'destroy'])->name('manager.faqs.categories.destroy');

    });



    Route::group(['prefix' => 'livechat'], function () {

        Route::get('/',[LivechatController::class,'index'])->name('manager.livechats');
        Route::get('/operators',[LivechatController::class,'operators'])->name('admin.operators');
        Route::get('/livechat-flow/{id}',[LivechatController::class,'liveChatFlow']);
        Route::get('/livechat-flow/test-it-out/{id}',[LivechatController::class,'testItOut']);
        Route::get('/chat-responses',[LivechatController::class,'chatResponses'])->name('admin.chatResponses');
        Route::get('/solvedchats',[LivechatController::class,'solvedChats'])->name('admin.solvedChats');
        Route::get('/myopened',[LivechatController::class,'myOpenedChats'])->name('admin.myOpenedChats');
        Route::get('/livechat-settings',[LivechatController::class,'livechatSettings'])->name('manager.livechats.Settings');
        Route::post('/operators/broadcastoperator',[LivechatController::class,'broadcastoperator'])->name('admin.broadcastoperator');
        Route::get('/operators/singleoperator/{id}',[LivechatController::class,'singleoperator'])->name('admin.singleoperator');
        Route::get('/operators/conversationdelete/{id}',[LivechatController::class,'conversationdelete'])->name('admin.conversationdelete');
        Route::get('/operators/markasunread/{id}',[LivechatController::class,'markasunread'])->name('admin.markasunread');
        Route::get('/operators/markasread/{id}',[LivechatController::class,'markasread'])->name('admin.markasread');
        Route::post('/operators/groupbroadcastoperator',[LivechatController::class,'groupbroadcastoperator'])->name('admin.groupbroadcastoperator');
        Route::get('/operators/groupconversion/{id}',[LivechatController::class,'groupconversion'])->name('admin.groupconversion');
        Route::post('/operators/groupconversionstore/{id}',[LivechatController::class,'groupconversionstore'])->name('admin.groupconversionstore');
        Route::get('/operators/groupconversiondelete/{id}',[LivechatController::class,'groupconversiondelete'])->name('admin.groupconversiondelete');
        Route::post('/livechat-flow/save',[LivechatController::class,'ChatFlowSave'])->name('manager.livechats.FlowSave');
        Route::get('/livechat-flow/delete/{id}',[LivechatController::class,'deleteChatFlow'])->name('manager.livechats.FlowDelete');
        Route::post('/livechat-flow/active-save',[LivechatController::class,'activeChatFlow'])->name('manager.livechats.ActiveFlowSave');
        Route::post('/livechat/engage-conversation',[LivechatController::class,'engageConversation'])->name('admin.engageConversation');
        Route::get('/livechat/conversationleave',[LivechatController::class,'conversationLeave'])->name('admin.conversationLeave');
        Route::post('/livechat/conversation-reassign',[LivechatController::class,'conversationReassign'])->name('admin.conversationReassign');
        Route::get('/livechat/conversation-delete',[LivechatController::class,'livechatConversationDelete'])->name('manager.livechats.ConversationDelete');
        Route::get('/livechat/mark-as-solved',[LivechatController::class,'markAsSolved'])->name('admin.markAsSolved');
        Route::post('/livechat/live-chat-notifications-setting',[LivechatController::class,'liveChatNotificationsSetting'])->name('manager.livechats.NotificationsSetting');
        Route::post('/livechat/live-chat-notifications-sound',[LivechatController::class,'liveChatNotificationsSound'])->name('manager.livechats.NotificationsSound');
        Route::post('/livechat/live-chat-flow-settings',[LivechatController::class,'liveChatFlowSettings'])->name('manager.livechats.FlowSettings');
        Route::post('/livechat/live-chat-file-settings',[LivechatController::class,'liveChatFileSettings'])->name('manager.livechats.FileSettings');
        Route::post('/livechat/live-chat-cust-fileupload',[LivechatController::class,'liveChatCustFileUpload'])->name('manager.livechats.CustFileUpload');
        Route::get('/livechat-notifications-sounds',[LivechatController::class,'livechatNotificationsSonds'])->name('manager.livechats.NotificationsSonds');
        Route::get('/livechat-notifications-sounds-delete',[LivechatController::class,'livechatNotificationsSondsDelete'])->name('manager.livechats.NotificationsSondsDelete');
        Route::get('/livechat-notifications-masssounds-delete',[LivechatController::class,'livechatNotificationsMassSondsDelete'])->name('manager.livechats.NotificationsMassSondsDelete');
        Route::post('/livechat/live-chat-icon-size',[LivechatController::class,'liveChatIconSize'])->name('manager.livechats.IconSize');
        Route::post('/livechat/live-chat-position',[LivechatController::class,'liveChatPosition'])->name('manager.livechats.Position');
        Route::post('/livechat/live-chat-offline-setting',[LivechatController::class,'liveChatOfflineSetting'])->name('manager.livechats.OfflineSetting');
        Route::get('/livechat/get-cannedmessages',[LivechatController::class,'getCannedmessages'])->name('admin.getCannedmessages');
        Route::post('/livechat/operators-notifications-setting',[LivechatController::class,'operatorsNotificationsSetting'])->name('admin.operatorsNotificationsSetting');
        Route::post('/livechat/livechat-credentials',[LivechatController::class,'livechatCredentials'])->name('manager.livechats.Credentials');
        Route::post('/livechat/livechatssldstore',[LivechatController::class,'livechatssldstore'])->name('manager.livechats.ssldstore');
        Route::post('/livechat/livechat-autosave',[LivechatController::class,'livechatAutoSave'])->name('manager.livechats.AutoSave');
        Route::post('/livechat/livechatAutoDelete',[LivechatController::class,'livechatAutoDelete'])->name('manager.livechats.AutoDelete');
        Route::get('/livechat-tickets',[LivechatController::class,'livechatTickets'])->name('manager.livechats.Tickets');
        Route::get('/livechat-ticket-massdelete',[LivechatController::class,'livechatTicketMassDelete'])->name('manager.livechats.TicketMassDelete');
        Route::get('/livechat-ticket-delete',[LivechatController::class,'livechatTicketDelete'])->name('manager.livechats.TicketDelete');
        Route::post('/livechat-feedback-dropdown',[LivechatController::class,'livechatFeedbackDropdown'])->name('manager.livechats.FeedbackDropdown');
        Route::post('/livechat-cust-welecome-title',[LivechatController::class,'LivechatCustWelcomeMsg'])->name('manager.livechats.CustWelcomeMsg');
        Route::get('/livechat-allratings',[LivechatController::class,'livechatAllRatings'])->name('manager.livechats.AllRatings');
        Route::get('/livechat-employer-ratings/{id}',[LivechatController::class,'livechatEmpliyerRatings'])->name('manager.livechats.EmpliyerRatings');
        Route::get('/livechat-delete-feedback/{id}',[LivechatController::class,'livechatDeleteFeedback'])->name('manager.livechats.DeleteFeedback');
        Route::post('/security-settings',[LivechatController::class,'securitySettings'])->name('admin.securitySettings');


    });


    Route::group(['prefix' => 'tickets'], function () {

        Route::get('/', [TicketsController::class, 'index'])->name('manager.tickets');
        Route::get('/create', [TicketsController::class, 'create'])->name('manager.tickets.create');
        Route::post('/store', [TicketsController::class, 'store'])->name('manager.tickets.store');
        Route::post('/update', [TicketsController::class, 'update'])->name('manager.tickets.update');
        Route::get('/edit/{uid}', [TicketsController::class, 'edit'])->name('manager.tickets.edit');
        Route::get('/control/{uid}', [TicketsController::class, 'control'])->name('manager.tickets.control');
        Route::get('/view/{uid}', [TicketsController::class, 'view'])->name('manager.tickets.view');
        Route::get('/destroy/{uid}', [TicketsController::class, 'destroy'])->name('manager.tickets.destroy');
        Route::post('/reopen/{uid}', [CommentsController::class, 'reopenticket'])->name('manager.tickets.reopen');

        Route::post('/image/upload/{uid}', [TicketsController::class, 'storeMedia'])->name('manager.tickets.image.store');
        Route::post('/image/upload', [TicketsController::class, 'guestmedia'])->name('manager.tickets.image.upload');

        Route::post('/priority/change', [TicketsController::class, 'changepriority'])->name('manager.tickets.change.priority');

        Route::post('/note/create', [TicketsController::class, 'note'])->name('manager.tickets.note.create');
        Route::get('/note/{uid}', [TicketsController::class, 'notedestroy'])->name('manager.tickets.note.destroy');

        Route::get('/comment/{uid}', [CommentsController::class, 'view'])->name('manager.tickets.comments');
        Route::post('/comment/post/{uid}', [CommentsController::class, 'postComment'])->name('manager.tickets.comments.post');
        Route::post('/comment/edit/{uid}', [CommentsController::class, 'updateedit'])->name('manager.tickets.comments.edit');
        Route::get('/comment/delete/{uid}', [CommentsController::class, 'deletecomment'])->name('manager.tickets.comments.delete');
        Route::get('/comment/image/upload/{uid}', [CommentsController::class, 'imagedestroy'])->name('manager.tickets.image.destroy');

        Route::post('/assigned', [TicketsController::class, 'create'])->name('manager.tickets.assigned');
        Route::get('/assigned/{uid}', [TicketsController::class, 'edit'])->name('manager.tickets.assigned.view');
        Route::get('/assigned/edit/{uid}', [TicketsController::class, 'view'])->name('manager.tickets.assigned.edit');

        Route::get('/inprogress', [NotificationsController::class, 'allactiveinprogresstickets'])->name('manager.notifications.markallnotify');

        Route::get('/selfassigneds', [TrashedsController::class, 'selfassignticketview'])->name('manager.tickets.selfassigned');
        Route::get('/assigneds', [TrashedsController::class, 'myassignedTickets'])->name('manager.tickets.assigneds');
        Route::get('/closeds', [TrashedsController::class, 'myclosedtickets'])->name('manager.tickets.closeds');
        Route::get('/suspends', [TrashedsController::class, 'mysuspendtickets'])->name('manager.tickets.history.suspendss');

        Route::get('/trasheds', [TrashedsController::class, 'tickettrashed'])->name('manager.tickets.trasheds');
        Route::get('/trasheds/view/{uid}', [TrashedsController::class, 'tickettrashedview'])->name('manager.tickets.trasheds.view');
        Route::post('/trasheds/restore/{uid}', [TrashedsController::class, 'tickettrashedrestore'])->name('manager.tickets.trasheds.restore');
        Route::post('/trasheds/destroy/{uid}', [TrashedsController::class, 'tickettrasheddestroy'])->name('manager.tickets.trasheds.destroy');
        Route::post('/trasheds/restore/all', [TrashedsController::class, 'alltrashedticketrestore'])->name('manager.tickets.trasheds.restore.all');
        Route::post('/trasheds/destroy/all', [TrashedsController::class, 'alltrashedticketdelete'])->name('manager.tickets.trasheds.destroy.all');

        Route::get('/history/{uid}', [TrashedsController::class, 'tickethistory'])->name('manager.tickets.history');
        Route::get('/history/users/{uid}', [TrashedsController::class, 'customerprevioustickets'])->name('manager.tickets.history.users');

        Route::get('/categories', [CategoriesTicketsController::class, 'index'])->name('manager.tickets.categories');
        Route::get('/categories/create', [CategoriesTicketsController::class, 'create'])->name('manager.tickets.categories.create');
        Route::post('/categories/store', [CategoriesTicketsController::class, 'store'])->name('manager.tickets.categories.store');
        Route::post('/categories/update', [CategoriesTicketsController::class, 'update'])->name('manager.tickets.categories.update');
        Route::post('/categories/assign/update', [CategoriesTicketsController::class, 'update'])->name('manager.tickets.categories.assign.update');
        Route::get('/categories/edit/{uid}', [CategoriesTicketsController::class, 'edit'])->name('manager.tickets.categories.edit');
        Route::get('/categories/assign/{uid}', [CategoriesTicketsController::class, 'assign'])->name('manager.tickets.categories.assign');
        Route::get('/categories/destroy/{uid}', [CategoriesTicketsController::class, 'destroy'])->name('manager.tickets.categories.destroy');

        Route::get('/canneds', [CannedsTicketsController::class, 'index'])->name('manager.tickets.canneds');
        Route::get('/canneds/create', [CannedsTicketsController::class, 'create'])->name('manager.tickets.canneds.create');
        Route::post('/canneds/store', [CannedsTicketsController::class, 'store'])->name('manager.tickets.canneds.store');
        Route::post('/canneds/update', [CannedsTicketsController::class, 'update'])->name('manager.tickets.canneds.update');
        Route::get('/canneds/edit/{uid}', [CannedsTicketsController::class, 'edit'])->name('manager.tickets.canneds.edit');
        Route::get('/canneds/destroy/{uid}', [CannedsTicketsController::class, 'destroy'])->name('manager.tickets.canneds.destroy');

        Route::get('/status', [StatusTicketsController::class, 'index'])->name('manager.tickets.status');
        Route::get('/status/create', [StatusTicketsController::class, 'create'])->name('manager.tickets.status.create');
        Route::post('/status/store', [StatusTicketsController::class, 'store'])->name('manager.tickets.status.store');
        Route::post('/status/update', [StatusTicketsController::class, 'update'])->name('manager.tickets.status.update');
        Route::get('/status/edit/{uid}', [StatusTicketsController::class, 'edit'])->name('manager.tickets.status.edit');
        Route::get('/status/destroy/{uid}', [StatusTicketsController::class, 'destroy'])->name('manager.tickets.status.destroy');

        Route::get('/priorities', [PrioritiesTicketsController::class, 'index'])->name('manager.tickets.priorities');
        Route::get('/priorities/create', [PrioritiesTicketsController::class, 'create'])->name('manager.tickets.priorities.create');
        Route::post('/priorities/store', [PrioritiesTicketsController::class, 'store'])->name('manager.tickets.priorities.store');
        Route::post('/priorities/update', [PrioritiesTicketsController::class, 'update'])->name('manager.tickets.priorities.update');
        Route::get('/priorities/edit/{uid}', [PrioritiesTicketsController::class, 'edit'])->name('manager.tickets.priorities.edit');
        Route::get('/priorities/destroy/{uid}', [PrioritiesTicketsController::class, 'destroy'])->name('manager.tickets.priorities.destroy');

        Route::get('/groups', [GroupsTicketsController::class, 'index'])->name('manager.tickets.groups');
        Route::get('/groups/create', [GroupsTicketsController::class, 'create'])->name('manager.tickets.groups.create');
        Route::post('/groups/store', [GroupsTicketsController::class, 'store'])->name('manager.tickets.groups.store');
        Route::post('/groups/update', [GroupsTicketsController::class, 'update'])->name('manager.tickets.groups.update');
        Route::get('/groups/edit/{uid}', [GroupsTicketsController::class, 'edit'])->name('manager.tickets.groups.edit');
        Route::get('/groups/destroy/{uid}', [GroupsTicketsController::class, 'destroy'])->name('manager.tickets.groups.destroy');

    });


});
