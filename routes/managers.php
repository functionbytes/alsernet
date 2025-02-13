<?php

use App\Http\Controllers\Managers\Automations\AutomationsController;
use App\Http\Controllers\Managers\Campaigns\CampaignsController;
use App\Http\Controllers\Managers\DashboardController;
use App\Http\Controllers\Managers\Events\EventsController;
use App\Http\Controllers\Managers\Faqs\CategoriesController as FaqsCategoriesController;
use App\Http\Controllers\Managers\Faqs\FaqsController;
use App\Http\Controllers\Managers\Inventaries\InventariesController;
use App\Http\Controllers\Managers\Inventaries\LocationssController as InventariessLocationsController;
use App\Http\Controllers\Managers\Layouts\LayoutController;
use App\Http\Controllers\Managers\Livechat\LivechatController;
use App\Http\Controllers\Managers\Maillists\MaillistController;
use App\Http\Controllers\Managers\Maillists\SegmentController;
use App\Http\Controllers\Managers\Maillists\SubscriberController as MaillistSubscriberController;
use App\Http\Controllers\Managers\NotificationController;
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
use App\Http\Controllers\Managers\Subscribers\SubscribersConditionsController;
use App\Http\Controllers\Managers\Subscribers\SubscribersListsController;
use App\Http\Controllers\Managers\Subscribers\SubscribersReportController;
use App\Http\Controllers\Managers\Templates\TemplatesController;
use App\Http\Controllers\Managers\Tickets\CannedsController as CannedsTicketsController;
use App\Http\Controllers\Managers\Tickets\CategoriesController as CategoriesTicketsController;
use App\Http\Controllers\Managers\Tickets\GroupsController as GroupsTicketsController;
use App\Http\Controllers\Managers\Tickets\PrioritiesController as PrioritiesTicketsController;
use App\Http\Controllers\Managers\Tickets\StatusController as StatusTicketsController;
use App\Http\Controllers\Managers\Tickets\TicketsController;
use App\Http\Controllers\Managers\Users\UsersController;
use Illuminate\Support\Facades\Route;


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

        Route::get('/historys/{uid}', [TemplatesController::class, 'index'])->name('manager.inventaries.historys');
        Route::get('/history/edit/{uid}', [TemplatesController::class, 'edit'])->name('manager.historys.edit');
        Route::get('/history/destroy/{uid}', [TemplatesController::class, 'destroy'])->name('manager.historys.destroy');
        Route::get('/history/update', [TemplatesController::class, 'update'])->name('manager.historys.update');

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


    Route::group(['prefix' => 'subscribers'], function () {


        Route::get('/', [TemplatesController::class, 'index'])->name('manager.subscribers');
        Route::get('/create', [TemplatesController::class, 'create'])->name('manager.subscribers.create');
        Route::get('/lists', [TemplatesController::class, 'index'])->name('manager.subscribers.lists');
        Route::post('/update', [TemplatesController::class, 'update'])->name('manager.subscribers.update');

        Route::get('/lists/report', [SubscribersListsController::class, 'report'])->name('manager.subscribers.lists.reports');
        Route::get('/lists/create', [SubscribersListsController::class, 'create'])->name('manager.subscribers.lists.create');
        Route::post('/lists/update', [SubscribersListsController::class, 'update'])->name('manager.subscribers.lists.update');
        Route::post('/lists/store', [SubscribersListsController::class, 'store'])->name('manager.subscribers.lists.store');
        Route::get('/edit/{uid}', [TemplatesController::class, 'edit'])->name('manager.subscribers.edit');
        Route::get('/view/{uid}', [TemplatesController::class, 'view'])->name('manager.subscribers.view');
        Route::get('/destroy/{uid}', [TemplatesController::class, 'destroy'])->name('manager.subscribers.destroy');
        Route::get('/list/{uid}', [TemplatesController::class, 'list'])->name('manager.subscribers.list');
        Route::get('/logs/{slack}', [TemplatesController::class, 'logs'])->name('manager.subscribers.logs');


        Route::get('/lists/reports', [SubscribersReportController::class, 'report'])->name('manager.subscribers.lists.reports');
        Route::get('/lists/details/{uid}', [SubscribersListsController::class, 'details'])->name('manager.subscribers.lists.details');
        Route::get('/lists/edit/{uid}', [SubscribersListsController::class, 'edit'])->name('manager.subscribers.lists.edit');
        Route::get('/lists/view/{uid}', [SubscribersListsController::class, 'view'])->name('manager.subscribers.lists.view');
        Route::get('/lists/destroy/{uid}', [SubscribersListsController::class, 'destroy'])->name('manager.subscribers.lists.destroy');
        Route::get('/lists/includes/{uid}', [SubscribersListsController::class, 'includes'])->name('manager.subscribers.lists.includes');
        Route::post('/lists/includes/update', [SubscribersListsController::class, 'updateIncludes'])->name('manager.subscribers.lists.includes.update');

        Route::get('/lists/report/generate', [SubscribersReportController::class, 'generate'])->name('manager.subscribers.lists.reports.generate');

        Route::get('/conditions', [SubscribersConditionsController::class, 'index'])->name('manager.subscribers.conditions');
        Route::get('/conditions/create', [SubscribersConditionsController::class, 'create'])->name('manager.subscribers.conditions.create');
        Route::post('/conditions/store', [SubscribersConditionsController::class, 'store'])->name('manager.subscribers.conditions.store');
        Route::post('/conditions/update', [SubscribersConditionsController::class, 'update'])->name('manager.subscribers.conditions.update');
        Route::get('/conditions/edit/{uid}', [SubscribersConditionsController::class, 'edit'])->name('manager.subscribers.conditions.edit');
        Route::get('/conditions/view/{uid}', [SubscribersConditionsController::class, 'view'])->name('manager.subscribers.conditions.view');
        Route::get('/conditions/destroy/{uid}', [SubscribersConditionsController::class, 'destroy'])->name('manager.subscribers.conditions.destroy');


        Route::get('/lists/destroy/newsletter/{uid}', [SubscribersListsUserController::class, 'destroy'])->name('manager.subscribers.lists.user.destroy');

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



    Route::group(['prefix' => 'templates'], function () {

        Route::get('/', [TemplatesController::class, 'index'])->name('manager.templates');
        Route::get('/create', [TemplatesController::class, 'create'])->name('manager.templates.create');
        Route::get('/chat', [TemplatesController::class, 'chat'])->name('manager.templates.chat');
        Route::post('/store', [TemplatesController::class, 'store'])->name('manager.templates.store');
        Route::post('/upload', [TemplatesController::class, 'uploadTemplate'])->name('manager.templates.uploadTemplate');
        Route::post('/update', [TemplatesController::class, 'update'])->name('manager.templates.update');
        Route::get('/delete', [TemplatesController::class, 'delete'])->name('manager.templates.delete');
        Route::get('/edit/{uid}', [TemplatesController::class, 'edit'])->name('manager.templates.edit');
        Route::get('/view/{uid}', [TemplatesController::class, 'view'])->name('manager.templates.view');
        Route::get('/destroy/{uid}', [TemplatesController::class, 'destroy'])->name('manager.templates.destroy');
        Route::get('/{uid}/preview',[TemplatesController::class, 'preview'])->name('manager.templates.preview');
        Route::get('/{uid}/edit', [TemplatesController::class, 'edit'])->name('manager.templates.edit');
        Route::post('/{uid}/copy',[TemplatesController::class, 'copy'])->name('manager.templates.copy');
        Route::get('/{uid}/copy',[TemplatesController::class, 'copy'])->name('manager.templates.copy');
        Route::post('/{uid}/export', [TemplatesController::class, 'export'])->name('manager.templates.export');
        Route::patch('/{uid}/update', [TemplatesController::class, 'update'])->name('manager.templates.update');

        Route::get('/rss/parse', [TemplatesController::class, 'parseRss'])->name('manager.templates.parseRss');

        Route::get('/listing/{page?}',[TemplatesController::class, 'listing'])->name('manager.templates.listing');
        Route::get('/choosing/{campaign_uid}/{page?}',[TemplatesController::class, 'choosing'])->name('manager.templates.choosing');

        Route::match(['get', 'post'], '/builder/create', [TemplatesController::class, 'builderCreate'])->name('manager.templates.builder.create');
        Route::match(['get', 'post'], '/{uid}/change-name', [TemplatesController::class, 'changeName'])->name('manager.templates.changemame');
        Route::match(['get', 'post'], '/{uid}/categories', [TemplatesController::class, 'categories'])->name('manager.templates.categories');
        Route::match(['get', 'post'], '/{uid}/update-thumb-url', [TemplatesController::class, 'updateThumbUrl'])->name('manager.templates.update.thumburl');
        Route::match(['get', 'post'], '/{uid}/update-thumb', [TemplatesController::class, 'updateThumb'])->name('manager.templates.update.thumb');
        Route::match(['get', 'post'], '/{uid}/builder/edit', [TemplatesController::class, 'builderEdit'])->name('manager.templates.builder.edit');

        Route::get('/builder/templates/{category_uid?}', [TemplatesController::class, 'builderTemplates'])->name('manager.templates.builder.templates');
        Route::post('/{uid}/builder/edit/asset', [TemplatesController::class, 'uploadTemplateAssets'])->name('manager.templates.upload.template.assets');
        Route::get('/{uid}/builder/edit/content', [TemplatesController::class, 'builderEditContent'])->name('manager.templates.builder.edit.content');
        Route::get('/{uid}/builder/change-template/{change_uid}', [TemplatesController::class, 'builderChangeTemplate'])->name('manager.templates.builder.change.template');

    });


    Route::group(['prefix' => 'campaigns'], function () {

        Route::get('/', [CampaignsController::class, 'index'])->name('manager.campaigns');
        Route::get('/create', [CampaignsController::class, 'create'])->name('manager.campaigns.create');
        Route::post('/store', [CampaignsController::class, 'store'])->name('manager.campaigns.store');
        Route::get('/view/{uid}', [CampaignsController::class, 'view'])->name('manager.campaigns.view');
        Route::get('/destroy/{uid}', [CampaignsController::class, 'destroy'])->name('manager.campaigns.destroy');

        Route::post('/{uid}/preheader/remove', [CampaignsController::class, 'preheaderRemove'])->name('manager.campaigns.preheaderRemove');
        Route::match(['get', 'post'], '/{uid}/preheader/add', [CampaignsController::class, 'preheaderAdd'])->name('manager.campaigns.preheaderAdd');
        Route::get('/{uid}/preheader', [CampaignsController::class, 'preheader'])->name('manager.campaigns.preheader');

        Route::post('/webhooks/{webhook_uid}/test/{message_id}', [CampaignsController::class, 'webhooksTestMessage'])->name('manager.campaigns.webhooksTestMessage');
        Route::get('/{uid}/click-log/{message_id}/execute', [CampaignsController::class, 'clickLogExecute'])->name('manager.campaigns.clickLogExecute');
        Route::get('/{uid}/open-log/{message_id}/execute', [CampaignsController::class, 'openLogExecute'])->name('manager.campaigns.openLogExecute');
        Route::match(['get', 'post'], '/webhooks/{webhook_uid}/test', [CampaignsController::class, 'webhooksTest'])->name('manager.campaigns.webhooksTest');
        Route::get('/webhooks/{webhook_uid}/sample/request', [CampaignsController::class, 'webhooksSampleRequest'])->name('manager.campaigns.webhooksSampleRequest');
        Route::post('/webhooks/{webhook_uid}/delete', [CampaignsController::class, 'webhooksDelete'])->name('manager.campaigns.webhooksDelete');
        Route::match(['get', 'post'], '/webhooks/{webhook_uid}/edit', [CampaignsController::class, 'webhooksEdit'])->name('manager.campaigns.webhooksEdit');
        Route::get('/{uid}/webhooks/list', [CampaignsController::class, 'webhooksList'])->name('manager.campaigns.webhooksList');
        Route::get('/{uid}/webhooks/link-select', [CampaignsController::class, 'webhooksLinkSelect'])->name('manager.campaigns.webhooksLinkSelect');
        Route::match(['get', 'post'], '/{uid}/webhooks/add', [CampaignsController::class, 'webhooksAdd'])->name('manager.campaigns.webhooksAdd');
        Route::get('/{uid}/webhooks', [CampaignsController::class, 'webhooks'])->name('manager.campaigns.webhooks');

        Route::get('/{uid}/preview-as/list', [CampaignsController::class, 'previewAsList'])->name('manager.campaigns.previewAsList');
        Route::get('/{uid}/preview-as', [CampaignsController::class, 'previewAs'])->name('manager.campaigns.previewAs');

        Route::post('/{uid}/custom-plain/off', [CampaignsController::class, 'customPlainOff'])->name('manager.campaigns.customPlainOff');
        Route::post('/{uid}/custom-plain/on', [CampaignsController::class, 'customPlainOn'])->name('manager.campaigns.customPlainOn');
        Route::post('/{uid}/remove-attachment', [CampaignsController::class, 'removeAttachment'])->name('manager.campaigns.removeAttachment');
        Route::get('/{uid}/download-attachment', [CampaignsController::class, 'downloadAttachment'])->name('manager.campaigns.downloadAttachment');
        Route::post('/{uid}/upload-attachment', [CampaignsController::class, 'uploadAttachment'])->name('manager.campaigns.uploadAttachment');
        Route::get('/{uid}/template/builder-select', [CampaignsController::class, 'templateBuilderSelect'])->name('manager.campaigns.templateBuilderSelect');

        Route::match(['get', 'post'], '/{uid}/template/builder-plain', [CampaignsController::class, 'builderPlainEdit'])->name('manager.campaigns.builderPlainEdit');
        Route::match(['get', 'post'], '/{uid}/template/builder-classic', [CampaignsController::class, 'builderClassic'])->name('manager.campaigns.builderClassic');
        Route::match(['get', 'post'], '/{uid}/plain', [CampaignsController::class, 'plain'])->name('manager.campaigns.plain');
        Route::get('/{uid}/template/change/{template_uid}', [CampaignsController::class, 'templateChangeTemplate'])->name('manager.campaigns.templateChangeTemplate');

        Route::get('/{uid}/template/content', [CampaignsController::class, 'templateContent'])->name('manager.campaigns.templateContent');
        Route::match(['get', 'post'], '/{uid}/template/edit', [CampaignsController::class, 'templateEdit'])->name('manager.campaigns.templateEdit');
        Route::match(['get', 'post'], '/{uid}/template/upload', [CampaignsController::class, 'templateUpload'])->name('manager.campaigns.templateUpload');
        Route::get('/{uid}/template/layout/list', [CampaignsController::class, 'templateLayoutList'])->name('manager.campaigns.templateLayoutList');
        Route::match(['get', 'post'], '/{uid}/template/layout', [CampaignsController::class, 'templateLayout'])->name('manager.campaigns.templateLayout');
        Route::get('/{uid}/template/create', [CampaignsController::class, 'templateCreate'])->name('manager.campaigns.templateCreate');

        Route::get('/{uid}/spam-score', [CampaignsController::class, 'spamScore'])->name('manager.campaigns.spamScore');
        Route::get('/{from_uid}/copy-move-from/{action}', [CampaignsController::class, 'copyMoveForm'])->name('manager.campaigns.copyMoveForm');
        Route::match(['get', 'post'], '/{uid}/resend', [CampaignsController::class, 'resend'])->name('manager.campaigns.resend');
        Route::get('/{uid}/tracking-log/download', [CampaignsController::class, 'trackingLogDownload'])->name('manager.campaigns.trackingLogDownload');
        Route::get('/job/{uid}/progress', [CampaignsController::class, 'trackingLogExportProgress'])->name('manager.campaigns.trackingLogExportProgress');
        Route::get('/job/{uid}/download', [CampaignsController::class, 'download'])->name('manager.campaigns.download');

        Route::get('/{uid}/template/review-iframe', [CampaignsController::class, 'templateReviewIframe'])->name('manager.campaigns.templateReviewIframe');
        Route::get('/{uid}/template/review', [CampaignsController::class, 'templateReview'])->name('manager.campaigns.templateReview');
        Route::get('/select-type', [CampaignsController::class, 'selecttype'])->name('manager.campaigns.selecttype');
        Route::get('/{uid}/list-segment-form', [CampaignsController::class, 'listSegmentForm'])->name('manager.campaigns.list.segment.form');
        Route::get('/{uid}/preview/content/{subscriber_uid?}', [CampaignsController::class, 'previewContent'])->name('manager.campaigns.previewContent');
        Route::get('/{uid}/preview', [CampaignsController::class, 'preview'])->name('manager.campaigns.preview');
        Route::match(['get', 'post'], '/send-test-email', [CampaignsController::class, 'sendTestEmail'])->name('manager.campaigns.send.test');
        Route::get('/delete/confirm', [CampaignsController::class, 'deleteConfirm'])->name('manager.campaigns.deleteConfirm');
        Route::match(['get', 'post'], '/copy', [CampaignsController::class, 'copy'])->name('manager.campaigns.copy');

        Route::get('/{uid}/subscribers', [CampaignsController::class, 'subscribers'])->name('manager.campaigns.subscribers');
        Route::get('/{uid}/subscribers/listing', [CampaignsController::class, 'subscribersListing'])->name('manager.campaigns.subscribers.listing');
        Route::get('/{uid}/open-map', [CampaignsController::class, 'openMap'])->name('manager.campaigns.open.map');
        Route::get('/{uid}/tracking-log', [CampaignsController::class, 'trackingLog'])->name('manager.campaigns.tracking.log');
        Route::get('/{uid}/tracking-log/listing', [CampaignsController::class, 'trackingLogListing'])->name('manager.campaigns.tracking.log.listing');
        Route::get('/{uid}/bounce-log', [CampaignsController::class, 'bounceLog'])->name('manager.campaigns.bounceLog');
        Route::get('/{uid}/bounce-log/listing', [CampaignsController::class, 'bounceLogListing'])->name('manager.campaigns.bounce.log.listing');
        Route::get('/{uid}/feedback-log', [CampaignsController::class, 'feedbackLog'])->name('manager.campaigns.feedbackLog');
        Route::get('/{uid}/feedback-log/listing', [CampaignsController::class, 'feedbackLogListing'])->name('manager.campaigns.feedback.log.listing');
        Route::get('/{uid}/open-log', [CampaignsController::class, 'openLog'])->name('manager.campaigns.open.log');
        Route::get('/{uid}/open-log/listing', [CampaignsController::class, 'openLogListing'])->name('manager.campaigns.open.log.listing');
        Route::get('/{uid}/click-log', [CampaignsController::class, 'clickLog'])->name('manager.campaigns.click.log');
        Route::get('/{uid}/click-log/listing', [CampaignsController::class, 'clickLogListing'])->name('manager.campaigns.click.log.listing');
        Route::get('/{uid}/unsubscribe-log', [CampaignsController::class, 'unsubscribeLog'])->name('manager.campaigns.unsubscribe.log');
        Route::get('/{uid}/unsubscribe-log/listing', [CampaignsController::class, 'unsubscribeLogListing'])->name('manager.campaigns.unsubscribe.log.listing');

        Route::get('/quick-view', [CampaignsController::class, 'quickView'])->name('manager.campaigns.quickView');
        Route::get('/{uid}/chart24h', [CampaignsController::class, 'chart24h'])->name('manager.campaigns.chart24h');
        Route::get('/{uid}/chart', [CampaignsController::class, 'chart'])->name('manager.campaigns.chart');
        Route::get('/{uid}/chart/countries/open', [CampaignsController::class, 'chartCountry'])->name('manager.campaigns.chartCountry');
        Route::get('/{uid}/chart/countries/click', [CampaignsController::class, 'chartClickCountry'])->name('manager.campaigns.chartClickCountry');
        Route::get('/{uid}/overview', [CampaignsController::class, 'overview'])->name('manager.campaigns.overview');
        Route::get('/{uid}/links', [CampaignsController::class, 'links'])->name('manager.campaigns.links');

        Route::get('/listing/{page?}', [CampaignsController::class, 'listing'])->name('manager.campaigns.listing');

        Route::match(['get', 'post'], '/{uid}/setup', [CampaignsController::class, 'setup'])->name('manager.campaigns.setup');
        Route::match(['get', 'post'], '/{uid}/template', [CampaignsController::class, 'template'])->name('manager.campaigns.template');
        Route::match(['get', 'post'], '/{uid}/recipients', [CampaignsController::class, 'recipients'])->name('manager.campaigns.recipients');
        Route::match(['get', 'post'], '/{uid}/schedule', [CampaignsController::class, 'schedule'])->name('manager.campaigns.schedule');
        Route::match(['get', 'post'], '/{uid}/confirm', [CampaignsController::class, 'confirm'])->name('manager.campaigns.confirm');

        Route::get('/{uid}/template/select', [CampaignsController::class, 'templateSelect'])->name('manager.campaigns.templateSelect');
        Route::get('/{uid}/template/choose/{template_uid}', [CampaignsController::class, 'templateChoose'])->name('manager.campaigns.templateChoose');
        Route::get('/{uid}/template/preview', [CampaignsController::class, 'templatePreview'])->name('manager.campaigns.templatePreview');
        Route::get('/{uid}/template/iframe', [CampaignsController::class, 'templateIframe'])->name('manager.campaigns.templateIframe');
        Route::get('/{uid}/template/build/{style}', [CampaignsController::class, 'templateBuild'])->name('manager.campaigns.templateBuild');
        Route::get('/{uid}/template/rebuild', [CampaignsController::class, 'templateRebuild'])->name('manager.campaigns.templateRebuild');

        Route::post('/delete', [CampaignsController::class, 'delete'])->name('manager.campaigns.delete');
        Route::get('/select2', [CampaignsController::class, 'select2'])->name('manager.campaigns.select2');
        Route::post('/pause', [CampaignsController::class, 'pause'])->name('manager.campaigns.pause');
        Route::post('/restart', [CampaignsController::class, 'restart'])->name('manager.campaigns.restart');
        Route::get('/{uid}/edit', [CampaignsController::class, 'edit'])->name('manager.campaigns.edit');
        Route::patch('/{uid}/update', [CampaignsController::class, 'update'])->name('manager.campaigns.update');
        Route::get('/{uid}/run', [CampaignsController::class, 'run'])->name('manager.campaigns.run');
        Route::get('/{uid}/update-stats', [CampaignsController::class, 'updateStats'])->name('manager.campaigns.updateStats');


    });


    Route::group(['prefix' => 'segments'], function () {

        Route::get('/no-list', [SegmentController::class, 'noList'])->name('manager.segments.noList');
        Route::get('/condition-value-control', [SegmentController::class, 'conditionValueControl'])->name('manager.segments.conditionValueControl');
        Route::get('/select_box', [SegmentController::class, 'selectBox'])->name('manager.segments.selectBox');
        Route::get('/lists/{list_uid}/segments', [SegmentController::class, 'index'])->name('manager.segments.index');
        Route::get('/lists/{list_uid}/segments/{uid}/subscribers', [SegmentController::class, 'subscribers'])->name('manager.segments.subscribers.');
        Route::get('/lists/{list_uid}/segments/{uid}/listing_subscribers', [SegmentController::class, 'listing_subscribers'])->name('manager.segments.listing_subscribers');
        Route::get('/lists/{list_uid}/segments/create', [SegmentController::class, 'create'])->name('manager.segments.create');
        Route::get('/lists/{list_uid}/segments/listing', [SegmentController::class, 'listing'])->name('manager.segments.listing');
        Route::post('/lists/{list_uid}/segments/store', [SegmentController::class, 'store'])->name('manager.segments.store');
        Route::get('/lists/{list_uid}/segments/{uid}/edit', [SegmentController::class, 'edit'])->name('manager.segments.edit');
        Route::patch('/lists/{list_uid}/segments/{uid}/update', [SegmentController::class, 'update'])->name('manager.segments.update');
        Route::get('/lists/{list_uid}/segments/delete', [SegmentController::class, 'delete'])->name('manager.segments.delete');
        Route::get('/lists/{list_uid}/segments/sample_condition', [SegmentController::class, 'sample_condition'])->name('manager.segments.sample_condition');


    });




    Route::group(['prefix' => 'maillists'], function () {


        Route::get('/', [MaillistController::class, 'index'])->name('manager.maillists');
        Route::get('/create', [MaillistController::class, 'create'])->name('manager.maillists.create');
        Route::post('/store', [MaillistController::class, 'store'])->name('manager.maillists.store');
        Route::get('/edit/{uid}', [MaillistController::class, 'edit'])->name('manager.maillists.edit');
        Route::get('/view/{uid}', [MaillistController::class, 'view'])->name('manager.maillists.view');
        Route::get('/destroy/{uid}', [MaillistController::class, 'destroy'])->name('manager.maillists.destroy');


        Route::match(['get', 'post'], 'lists/select', [MailListController::class, 'selectList'])->name('manager.campaigns.maillists.selectList');
        Route::get('/{uid}/email-verification/chart', [MailListController::class, 'emailVerificationChart'])->name('manager.campaigns.maillists.emailVerificationChart');
        Route::get('/{uid}/clone-to-customers/choose', [MailListController::class, 'cloneForCustomersChoose'])->name('manager.campaigns.maillists.cloneForCustomersChoose');
        Route::post('/{uid}/clone-to-customers', [MailListController::class, 'cloneForCustomers'])->name('manager.campaigns.maillists.cloneForCustomers');

        Route::get('/{uid}/verification/{job_uid}/progress', [MailListController::class, 'verificationProgress'])->name('manager.campaigns.maillists.verificationProgress');
        Route::get('/{uid}/verification', [MailListController::class, 'verification'])->name('manager.campaigns.maillists.verification');
        Route::post('/{uid}/verification/start', [MailListController::class, 'startVerification'])->name('manager.campaigns.maillists.startVerification');
        Route::post('/{uid}/verification/{job_uid}/stop', [MailListController::class, 'stopVerification'])->name('manager.campaigns.maillists.stopVerification');
        Route::post('/{uid}/verification/reset', [MailListController::class, 'resetVerification'])->name('manager.campaigns.maillists.resetVerification');
        Route::match(['get', 'post'], '/copy', [MailListController::class, 'copy'])->name('manager.campaigns.maillists.copy');
        Route::get('/quick-view', [MailListController::class, 'quickView'])->name('manager.campaigns.maillists.quickView');
        Route::get('/{uid}/list-growth', [MailListController::class, 'listGrowthChart'])->name('manager.campaigns.maillists.listGrowthChart');
        Route::get('/{uid}/list-statistics-chart', [MailListController::class, 'statisticsChart'])->name('manager.campaigns.maillists.statisticsChart');
        Route::get('/sort', [MailListController::class, 'sort'])->name('manager.campaigns.maillists.sort');
        Route::get('/listing/{page?}', [MailListController::class, 'listing'])->name('manager.campaigns.maillists.listing');
        Route::post('/delete', [MailListController::class, 'delete'])->name('manager.campaigns.maillists.delete');
        Route::get('/delete/confirm', [MailListController::class, 'deleteConfirm'])->name('manager.campaigns.maillists.delete.confirm');
        Route::get('/{uid}/overview', [MailListController::class, 'overview'])->name('manager.campaigns.maillists.overview');

        Route::get('/{uid}/edit', [MailListController::class, 'edit'])->name('manager.campaigns.maillists.edit');
        Route::patch('/{uid}/update', [MailListController::class, 'update'])->name('manager.campaigns.maillists.update');

// Field
        Route::get('/{list_uid}/fields', [FieldController::class, 'index'])->name('manager.campaigns.maillists.fields');
        Route::get('/{list_uid}/fields/sort', [FieldController::class, 'sort'])->name('manager.campaigns.maillists.fieldsSort');
        Route::post('/{list_uid}/fields/store', [FieldController::class, 'store'])->name('manager.campaigns.maillists.fieldsStore');
        Route::get('/{list_uid}/fields/sample/{type}', [FieldController::class, 'sample'])->name('manager.campaigns.maillists.fieldsSample');
        Route::get('/{list_uid}/fields/{uid}/delete', [FieldController::class, 'delete'])->name('manager.campaigns.maillists.fieldsDelete');

// Subscriber
        Route::get('/{list_uid}/subscribers/import/wizard/progress/content', [MaillistSubscriberController::class, 'import2ProgressContent'])->name('manager.campaigns.maillists.subscribers.import.wizard.ProgressContent');
        Route::get('/{list_uid}/subscribers/import/wizard/progress', [MaillistSubscriberController::class, 'import2Progress'])->name('manager.campaigns.maillists.subscribers.import.wizard.progress');
        Route::post('/{list_uid}/subscribers/import/wizard/run', [MaillistSubscriberController::class, 'import2Run'])->name('manager.campaigns.maillists.subscribers.import.wizard.run');
        Route::post('/{list_uid}/subscribers/import/wizard/validate', [MaillistSubscriberController::class, 'import2Validate'])->name('manager.campaigns.maillists.subscribers.import.wizard.validate');
        Route::get('/{list_uid}/subscribers/import/wizard/mapping', [MaillistSubscriberController::class, 'import2Mapping'])->name('manager.campaigns.maillists.subscribers.import.wizard.mapping');
        Route::post('/{list_uid}/subscribers/import/wizard/upload', [MaillistSubscriberController::class, 'import2Upload'])->name('manager.campaigns.maillists.subscribers.import.wizard.upload');
        Route::get('/{list_uid}/subscribers/import/wizard/wizard', [MaillistSubscriberController::class, 'import2Wizard'])->name('manager.campaigns.maillists.subscribers.import.wizard.wizard');
        Route::get('/{list_uid}/subscribers/import/wizard', [MaillistSubscriberController::class, 'import2'])->name('manager.campaigns.maillists.subscribers.import.wizard');
        Route::get('subscribers/no-list', [MaillistSubscriberController::class, 'noList'])->name('manager.campaigns.maillists.subscribers.nolist');
        Route::match(['get', 'post'], 'lists/{list_uid}/subscribers/assign-values', [MaillistSubscriberController::class, 'assignValues'])->name('manager.campaigns.maillists.subscribers.assignvalues');
        Route::match(['get', 'post'], 'lists/{list_uid}/subscribers/bulk-delete', [MaillistSubscriberController::class, 'bulkDelete'])->name('manager.campaigns.maillists.subscribers.bulkdelete');

        // Subscriber
        Route::post('/{list_uid}/subscriber/{uid}/remove-tag', [MaillistSubscriberController::class, 'removeTag'])->name('manager.campaigns.maillists.subscriberRemoveTag');
        Route::match(['get', 'post'], 'lists/{list_uid}/subscriber/{uid}/update-tags', [MaillistSubscriberController::class, 'updateTags'])->name('manager.campaigns.maillists.subscriberUpdateTags');

        Route::post('/{list_uid}/subscribers/resend/confirmation-email/{uids?}', [MaillistSubscriberController::class, 'resendConfirmationEmail'])->name('manager.campaigns.maillists.subscribers.ResendConfirmationEmail');
        Route::post('subscriber/{uid}/verification/start', [MaillistSubscriberController::class, 'startVerification'])->name('manager.campaigns.maillists.subscribers.tartVerification');
        Route::post('subscriber/{uid}/verification/reset', [MaillistSubscriberController::class, 'resetVerification'])->name('manager.campaigns.maillists.subscriberResetVerification');
        Route::get('/{from_uid}/copy-move-from/{action}', [MaillistSubscriberController::class, 'copyMoveForm'])->name('manager.campaigns.maillists.subscribers.CopyMoveForm');
        Route::post('subscribers/move', [MaillistSubscriberController::class, 'move'])->name('manager.campaigns.maillists.subscribers.move');
        Route::post('subscribers/copy', [MaillistSubscriberController::class, 'copy'])->name('manager.campaigns.maillists.subscribers.copy');
        Route::get('/{list_uid}/subscribers', [MaillistSubscriberController::class, 'index'])->name('manager.campaigns.maillists.subscribers.index');
        Route::get('/{list_uid}/subscribers/create', [MaillistSubscriberController::class, 'create'])->name('manager.campaigns.maillists.subscribers.create');
        Route::get('/{list_uid}/subscribers/listing', [MaillistSubscriberController::class, 'listing'])->name('manager.campaigns.maillists.subscribers.listing');
        Route::post('/{list_uid}/subscribers/store', [MaillistSubscriberController::class, 'store'])->name('manager.campaigns.maillists.subscribers.store');
        Route::get('/{list_uid}/subscribers/{uid}/edit', [MaillistSubscriberController::class, 'edit'])->name('manager.campaigns.maillists.subscribers.edit');
        Route::patch('/{list_uid}/subscribers/{uid}/update', [MaillistSubscriberController::class, 'update'])->name('manager.campaigns.maillists.subscribers.update');
        Route::post('/{list_uid}/subscribers/delete', [MaillistSubscriberController::class, 'delete'])->name('manager.campaigns.maillists.subscribers.delete');
        Route::get('/{list_uid}/subscribers/delete', [MaillistSubscriberController::class, 'delete'])->name('manager.campaigns.maillists.subscribers.deleteGet');
        Route::post('/{list_uid}/subscribers/subscribe', [MaillistSubscriberController::class, 'subscribe'])->name('manager.campaigns.maillists.subscribers.subscribe');
        Route::post('/{list_uid}/subscribers/unsubscribe', [MaillistSubscriberController::class, 'unsubscribe'])->name('manager.campaigns.maillists.subscribers.unsubscribe');

// Import & Export
        Route::get('/{list_uid}/subscribers/import', [MaillistSubscriberController::class, 'import'])->name('manager.campaigns.maillists.subscribers.import');
        Route::post('/{list_uid}/subscribers/import/dispatch', [MaillistSubscriberController::class, 'dispatchImportJob'])->name('manager.campaigns.maillists.subscribers.import.dispatch');
        Route::get('/{list_uid}/import/{job_uid}/progress', [MaillistSubscriberController::class, 'importProgress'])->name('manager.campaigns.maillists.import.progress');
        Route::get('/import/{job_uid}/log/download', [MaillistSubscriberController::class, 'downloadImportLog'])->name('manager.campaigns.maillists.import.log.download');
        Route::post('/import/{job_uid}/cancel', [MaillistSubscriberController::class, 'cancelImport'])->name('manager.campaigns.maillists.import.cancel');

        Route::get('/{list_uid}/subscribers/export', [MaillistSubscriberController::class, 'export'])->name('manager.campaigns.maillists.subscribers.export');
        Route::post('/{list_uid}/subscribers/export/dispatch', [MaillistSubscriberController::class, 'dispatchExportJob'])->name('manager.campaigns.maillists.subscribers.export.dispatch');
        Route::get('/export/{job_uid}/progress', [MaillistSubscriberController::class, 'exportProgress'])->name('manager.campaigns.maillists.export.progress');
        Route::get('/export/{job_uid}/log/download', [MaillistSubscriberController::class, 'downloadExportedFile'])->name('manager.campaigns.maillists.export.log.download');
        Route::post('/export/{job_uid}/cancel', [MaillistSubscriberController::class, 'cancelExport'])->name('manager.campaigns.maillists.export.cancel');

// Segment
        Route::get('segments/no-list', [SegmentController::class, 'noList'])->name('manager.campaigns.maillists.segmentsNoList');
        Route::get('segments/condition-value-control', [SegmentController::class, 'conditionValueControl'])->name('manager.campaigns.maillists.segmentsConditionValueControl');
        Route::get('segments/select_box', [SegmentController::class, 'selectBox'])->name('manager.campaigns.maillists.segmentsSelectBox');
        Route::get('/{list_uid}/segments', [SegmentController::class, 'index'])->name('manager.campaigns.maillists.segments.index');
        Route::get('/{list_uid}/segments/{uid}/subscribers', [SegmentController::class, 'subscribers'])->name('manager.campaigns.maillists.segments.subscribers');
        Route::get('/{list_uid}/segments/{uid}/listing_subscribers', [SegmentController::class, 'listing_subscribers'])->name('manager.campaigns.maillists.segmentsListingSubscribers');
        Route::get('/{list_uid}/segments/create', [SegmentController::class, 'create'])->name('manager.campaigns.maillists.segmentsCreate');
        Route::get('/{list_uid}/segments/listing', [SegmentController::class, 'listing'])->name('manager.campaigns.maillists.segmentsListing');
        Route::post('/{list_uid}/segments/store', [SegmentController::class, 'store'])->name('manager.campaigns.maillists.segmentsStore');
        Route::get('/{list_uid}/segments/{uid}/edit', [SegmentController::class, 'edit'])->name('manager.campaigns.maillists.segmentsEdit');
        Route::patch('/{list_uid}/segments/{uid}/update', [SegmentController::class, 'update'])->name('manager.campaigns.maillists.segmentsUpdate');
        Route::get('/{list_uid}/segments/delete', [SegmentController::class, 'delete'])->name('manager.campaigns.maillists.segmentsDelete');
        Route::get('/{list_uid}/segments/sample_condition', [SegmentController::class, 'sample_condition'])->name('manager.campaigns.maillists.segmentsSampleCondition');

// Page
        Route::post('/{list_uid}/pages/{alias}/restore-default', [PageController::class, 'restoreDefault'])->name('manager.campaigns.maillists.pages.restore.default');
        Route::get('/{list_uid}/pages/{alias}/update', [PageController::class, 'update'])->name('manager.campaigns.maillists.pages.update');
        Route::post('/{list_uid}/pages/{alias}/update', [PageController::class, 'update'])->name('manager.campaigns.maillists.pages.update.post');
        Route::post('/{list_uid}/pages/{alias}/preview', [PageController::class, 'preview'])->name('manager.campaigns.maillists.pages.preview');



    });

    Route::group(['prefix' => 'automations'], function () {

            // Automation2
            Route::post('/{uid}/trigger-all', [AutomationsController::class, 'triggerAll'])->name('manager.automations.triggerAll');

            Route::match(['get', 'post'], 'automation/{uid}/copy', [AutomationsController::class, 'copy'])->name('manager.automations.copy');
            Route::get('/{uid}/condition/remove', [AutomationsController::class, 'conditionRemove'])->name('manager.automations.conditionRemove');

            Route::post('/{uid}/template/{email_uid}/preheader/remove', [AutomationsController::class, 'emailPreheaderRemove'])->name('manager.automations.emailPreheaderRemove');
            Route::match(['get', 'post'], 'automation/{uid}/template/{email_uid}/preheader/add', [AutomationsController::class, 'emailPreheaderAdd'])->name('manager.automations.emailPreheaderAdd');
            Route::get('/{uid}/template/{email_uid}/preheader', [AutomationsController::class, 'emailPreheader'])->name('manager.automations.emailPreheader');

            Route::match(['get', 'post'], 'automation/condition/wait/custom', [AutomationsController::class, 'conditionWaitCustom'])->name('manager.automations.conditionWaitCustom');
            Route::match(['get', 'post'], 'automation/{email_uid}/send-test-email', [AutomationsController::class, 'sendTestEmail'])->name('manager.automations.send.test');
            Route::get('/{uid}/cart/items', [AutomationsController::class, 'cartItems'])->name('manager.automations.cartItems');
            Route::get('/{uid}/cart/list', [AutomationsController::class, 'cartList'])->name('manager.automations.cartList');
            Route::get('/{uid}/cart/stats', [AutomationsController::class, 'cartStats'])->name('manager.automations.cartStats');
            Route::match(['get', 'post'], 'automation/{uid}/cart/change-store', [AutomationsController::class, 'cartChangeStore'])->name('manager.automations.cartChangeStore');
            Route::match(['get', 'post'], 'automation/{uid}/cart/wait', [AutomationsController::class, 'cartWait'])->name('manager.automations.cartWait');
            Route::match(['get', 'post'], 'automation/{uid}/cart/change-list', [AutomationsController::class, 'cartChangeList'])->name('manager.automations.cartChangeList');

            Route::get('/{uid}/condition/setting', [AutomationsController::class, 'conditionSetting'])->name('manager.automations.conditionSetting');
            Route::get('/{uid}/operation/show', [AutomationsController::class, 'operationShow'])->name('manager.automations.operationShow');
            Route::match(['get', 'post'], 'automation/{uid}/operation/edit', [AutomationsController::class, 'operationEdit'])->name('manager.automations.operation.edit');
            Route::match(['get', 'post'], 'automation/{uid}/operation/create', [AutomationsController::class, 'operationCreate'])->name('manager.automations.operation.create');
            Route::get('/{uid}/operation/select', [AutomationsController::class, 'operationSelect'])->name('manager.automations.operation.select');

            Route::post('/{uid}/wait-time', [AutomationsController::class, 'waitTime'])->name('manager.automations.waitTime');
            Route::get('/{uid}/wait-time', [AutomationsController::class, 'waitTime'])->name('manager.automations.waitTime');
            Route::get('/{uid}/last-saved', [AutomationsController::class, 'lastSaved'])->name('manager.automations.lastSaved');

            Route::post('/{uid}/subscribers/{subscriber_uid}/restart', [AutomationsController::class, 'subscribersRestart'])->name('manager.automations.subscribers.restart');
            Route::post('/{uid}/subscribers/{subscriber_uid}/remove', [AutomationsController::class, 'subscribersRemove'])->name('manager.automations.subscribers.remove');
            Route::get('/{uid}/subscribers/{subscriber_uid}/show', [AutomationsController::class, 'subscribersShow'])->name('manager.automations.subscribers.Show');
            Route::get('/{uid}/subscribers/list', [AutomationsController::class, 'subscribersList'])->name('manager.automations.subscribers.List');
            Route::get('/{uid}/subscribers', [AutomationsController::class, 'subscribers'])->name('manager.automations.subscribers.');

            Route::get('/{uid}/insight', [AutomationsController::class, 'insight'])->name('manager.automations.insight');
            Route::post('/{uid}/data/save', [AutomationsController::class, 'saveData'])->name('manager.automations.saveData');
            Route::post('/{uid}/update', [AutomationsController::class, 'update'])->name('manager.automations.update');
            Route::get('/{uid}/settings', [AutomationsController::class, 'settings'])->name('manager.automations.settings');

            Route::match(['get', 'post'], 'automation/emails/webhooks/{webhook_uid}/test', [AutomationsController::class,'webhooksTest'])->name('manager.automations.webhooksTest');
            Route::get('/emails/webhooks/{webhook_uid}/sample/request', [AutomationsController::class,'webhooksSampleRequest'])->name('manager.automations.webhooksSampleRequest');
            Route::post('/emails/webhooks/{webhook_uid}/delete', [AutomationsController::class,'webhooksDelete'])->name('manager.automations.webhooksDelete');
            Route::match(['get', 'post'], 'automation/emails/webhooks/{webhook_uid}/edit', [AutomationsController::class,'webhooksEdit'])->name('manager.automations.webhooksEdit');
            Route::get('/emails/{email_uid}/webhooks/list', [AutomationsController::class,'webhooksList'])->name('manager.automations.webhooksList');
            Route::get('/emails/{email_uid}/webhooks/link-select', [AutomationsController::class,'webhooksLinkSelect'])->name('manager.automations.webhooksLinkSelect');
            Route::match(['get', 'post'], 'automation/emails/{email_uid}/webhooks/add', [AutomationsController::class,'webhooksAdd'])->name('manager.automations.webhooksAdd');
            Route::get('/emails/{email_uid}/webhooks', [AutomationsController::class,'webhooks'])->name('manager.automations.webhooks');

            Route::post('/disable', [AutomationsController::class, 'disable'])->name('manager.automations.disable');
            Route::post('/enable', [AutomationsController::class, 'enable'])->name('manager.automations.enable');
            Route::delete('/delete', [AutomationsController::class, 'delete'])->name('manager.automations.delete');
            Route::get('/listing', [AutomationsController::class, 'listing'])->name('manager.automations.listing');
            Route::get('/', [AutomationsController::class, 'index'])->name('manager.automations');
            Route::get('/{uid}/debug', [AutomationsController::class, 'debug'])->name('manager.automations.debug');
            Route::get('trigger/{id}', [AutomationsController::class, 'show'])->name('manager.automations.show');
            Route::get('/{automation}/{subscriber}/trigger', [AutomationsController::class, 'triggerNow'])->name('manager.automations.triggerNow');
            Route::get('/{automation}/run', [AutomationsController::class, 'run'])->name('manager.automations.run');


    });



    Route::group(['prefix' => 'maillists'], function () {

        Route::get('/', [MaillistController::class, 'index'])->name('manager.maillists');
        Route::get('/create', [MaillistController::class, 'create'])->name('manager.maillists.create');
        Route::post('/store', [MaillistController::class, 'store'])->name('manager.maillists.store');
        Route::get('/edit/{uid}', [MaillistController::class, 'edit'])->name('manager.maillists.edit');
        Route::get('/view/{uid}', [MaillistController::class, 'view'])->name('manager.maillists.view');
        Route::get('/destroy/{uid}', [MaillistController::class, 'destroy'])->name('manager.maillists.destroy');

        Route::match(['get', 'post'], '/lists/select', [MaillistController::class, 'selectList'])->name('manager.maillists.selectList');
        Route::get('/lists/{uid}/email-verification/chart', [MaillistController::class, 'emailVerificationChart'])->name('manager.maillists.emailVerificationChart');
        Route::get('/lists/{uid}/clone-to-customers/choose', [MaillistController::class, 'cloneForCustomersChoose'])->name('manager.maillists.clone.for.customerschoose');
        Route::post('/lists/{uid}/clone-to-customers', [MaillistController::class, 'cloneForCustomers'])->name('manager.maillists.cloneForCustomers');

        Route::get('/lists/{uid}/verification/{job_uid}/progress', [MaillistController::class, 'verificationProgress'])->name('manager.maillists.verificationProgress');
        Route::get('/lists/{uid}/verification', [MaillistController::class, 'verification'])->name('manager.maillists.verification');
        Route::post('/lists/{uid}/verification/start', [MaillistController::class, 'startVerification'])->name('manager.maillists.startVerification');
        Route::post('/lists/{uid}/verification/{job_uid}/stop', [MaillistController::class, 'stopVerification'])->name('manager.maillists.stopVerification');
        Route::post('/lists/{uid}/verification/reset', [MaillistController::class, 'resetVerification'])->name('manager.maillists.resetVerification');

        Route::match(['get', 'post'], '/lists/copy', [MaillistController::class, 'copy'])->name('manager.maillists.copy');
        Route::get('/lists/quick-view', [MaillistController::class, 'quickView'])->name('manager.maillists.quickView');
        Route::get('/lists/{uid}/list-growth', [MaillistController::class, 'listGrowthChart'])->name('manager.maillists.listGrowthChart');
        Route::get('/lists/{uid}/list-statistics-chart', [MaillistController::class, 'statisticsChart'])->name('manager.maillists.statisticsChart');
        Route::get('/lists/sort', [MaillistController::class, 'sort'])->name('manager.maillists.sort');
        Route::get('/lists/listing/{page?}', [MaillistController::class, 'listing'])->name('manager.maillists.listing');
        Route::post('/lists/delete', [MaillistController::class, 'delete'])->name('manager.maillists.delete');
        Route::get('/lists/delete/confirm', [MaillistController::class, 'deleteConfirm'])->name('manager.maillists.delete.confirm');

        Route::get('/lists/{uid}/overview', [MaillistController::class, 'overview'])->name('manager.maillists.overview');
        Route::get('/lists/{uid}/edit', [MaillistController::class, 'edit'])->name('manager.maillists.edit');
        Route::post('/lists/{uid}/update', [MaillistController::class, 'update'])->name('manager.maillists.update');
        Route::match(['get', 'post'], '/lists/{uid}/embedded-form', [MaillistController::class, 'embeddedForm'])->name('manager.campaigns.maillists.embeddedForm');
        Route::get('/lists/{uid}/embedded-form-frame', [MaillistController::class, 'embeddedFormFrame'])->name('manager.campaigns.maillists.embeddedFormFrame');

        Route::post('/lists/{uid}/embedded-form-subscribe', [MaillistController::class, 'embeddedFormSubscribe'])->name('manager.campaigns.maillists.embeddedFormSubscribe');
        Route::post('/lists/{uid}/embedded-form-subscribe-captcha', [MaillistController::class, 'embeddedFormSubscribe'])->name('manager.automations.maillists');

        Route::get('/lists/{uid}/check-email', [AutomationsController::class, 'checkEmail'])->name('manager.campaigns.maillists.checkEmail');


    });



    Route::group(['prefix' => 'notifications'], function () {

        Route::get('/', [NotificationController::class, 'index'])->name('manager.notifications');
        Route::get('/popup', [NotificationController::class, 'popup'])->name('manager.notifications.popup');

        Route::post('/delete', [NotificationController::class, 'delete'])->name('manager.automations.delete');
        Route::post('/listing', [NotificationController::class, 'listing'])->name('manager.automations.listing');


    });



    Route::group(['prefix' => 'layouts'], function () {

        Route::get('/', [LayoutController::class, 'index'])->name('manager.layouts');
        Route::get('/create', [LayoutController::class, 'create'])->name('manager.layouts.create');
        Route::post('/store', [LayoutController::class, 'store'])->name('manager.layouts.store');
        Route::patch('/update/{uid}', [LayoutController::class, 'update'])->name('manager.layouts.update');
        Route::get('/edit/{uid}', [LayoutController::class, 'edit'])->name('manager.layouts.edit');
        Route::get('/view/{uid}', [LayoutController::class, 'view'])->name('manager.layouts.view');
        Route::get('/destroy/{uid}', [LayoutController::class, 'destroy'])->name('manager.layouts.destroy');

        Route::get('/listing/{page?}', [LayoutController::class, 'listing'])->name('manager.layouts.listing');
        Route::get('/sort', [LayoutController::class, 'sort'])->name('manager.layouts.sort');

    });



});
