<?php

use Illuminate\Support\Facades\Route;
use Modules\Forms\Http\Controllers\Managers\FormsManagerController;
use Modules\Forms\Http\Controllers\Managers\FormsReportController;

/*
| Forms manager routes.
|
| Montadas por FormsServiceProvider con prefix 'panel/forms' y middleware
| ['web', 'auth', 'can:helpdesk.tickets.view'] a nivel de grupo (lectura).
| Las rutas de FormsManagerController exigen además 'can:helpdesk.tickets.settings'
| (middleware del propio controller) por ser mutación de configuración.
*/
Route::get('report', [FormsReportController::class, 'index'])->name('forms.report.index');

Route::get('manage', [FormsManagerController::class, 'index'])->name('forms.manage.index');
Route::post('manage', [FormsManagerController::class, 'store'])->name('forms.manage.store');
Route::put('manage/{form}', [FormsManagerController::class, 'update'])->name('forms.manage.update');
Route::post('manage/{form}/toggle', [FormsManagerController::class, 'toggle'])->name('forms.manage.toggle');
Route::delete('manage/{form}', [FormsManagerController::class, 'destroy'])->name('forms.manage.destroy');
Route::post('manage/bulk', [FormsManagerController::class, 'bulk'])->name('forms.manage.bulk');
Route::get('manage/export', [FormsManagerController::class, 'exportJson'])->name('forms.manage.export');
Route::post('manage/import', [FormsManagerController::class, 'importJson'])->name('forms.manage.import');
