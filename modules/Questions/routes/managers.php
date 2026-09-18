<?php

use Illuminate\Support\Facades\Route;
use Modules\Questions\Http\Controllers\Managers\QuestionsManagerController;

/*
| Bandeja de consultas. El grupo exige 'can:questions.view'; las acciones que
| cambian algo piden además 'questions.moderate' desde el controlador.
| URIs en inglés, como el resto del panel.
*/
Route::get('/', [QuestionsManagerController::class, 'index'])->name('questions.index');
Route::get('/{question}', [QuestionsManagerController::class, 'show'])->name('questions.show')->whereNumber('question');
Route::post('/bulk', [QuestionsManagerController::class, 'bulk'])->name('questions.bulk');
Route::post('/{question}/answer', [QuestionsManagerController::class, 'answer'])->name('questions.answer');
Route::post('/{question}/approve', [QuestionsManagerController::class, 'approve'])->name('questions.approve');
Route::post('/{question}/reject', [QuestionsManagerController::class, 'reject'])->name('questions.reject');
