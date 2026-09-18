<?php

use Illuminate\Support\Facades\Route;
use Modules\Forms\Http\Controllers\Managers\FormsManagerController;
use Modules\Forms\Http\Controllers\Managers\FormsReportController;

/*
| Forms manager routes.
|
| Montadas por FormsServiceProvider con prefix 'panel/helpdesk/settings/tickets'
| y middleware ['web', 'auth', 'can:helpdesk.tickets.view'] a nivel de grupo
| (lectura). Las rutas de FormsManagerController exigen además
| 'can:helpdesk.tickets.settings' (middleware del propio controller) por ser
| mutación de configuración.
|
| Los nombres de ruta (forms.manage.*, forms.report.*) se conservan tal cual
| aunque la URI ya no lleve el segmento 'manage' -- son un módulo aparte
| (Forms) y renombrarlos habría obligado a tocar cada vista que usa route().
*/
Route::get('forms/report', [FormsReportController::class, 'index'])->name('forms.report.index');

Route::get('forms', [FormsManagerController::class, 'index'])->name('forms.manage.index');
Route::post('forms', [FormsManagerController::class, 'store'])->name('forms.manage.store');
Route::put('forms/{form}', [FormsManagerController::class, 'update'])->name('forms.manage.update');
Route::post('forms/{form}/toggle', [FormsManagerController::class, 'toggle'])->name('forms.manage.toggle');
Route::delete('forms/{form}', [FormsManagerController::class, 'destroy'])->name('forms.manage.destroy');
Route::post('forms/bulk', [FormsManagerController::class, 'bulk'])->name('forms.manage.bulk');
Route::get('forms/export', [FormsManagerController::class, 'exportJson'])->name('forms.manage.export');
Route::post('forms/import', [FormsManagerController::class, 'importJson'])->name('forms.manage.import');
