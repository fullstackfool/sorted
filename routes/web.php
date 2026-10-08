<?php

use App\Http\Controllers\ChoreController;
use App\Http\Controllers\CompletionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Template resource routes (for managing task templates/schedules)
Route::resource('templates', TemplateController::class)->except(['edit']);

// Task action routes (for completing/skipping/resetting task instances)
Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
Route::post('/tasks/{task}/skip', [TaskController::class, 'skip'])->name('tasks.skip');
Route::post('/tasks/{task}/reset', [TaskController::class, 'reset'])->name('tasks.reset');

// Chore pages (the list, each chore's page, the form for creating and editing, and deleting)
Route::resource('chores', ChoreController::class);

// Chore action routes (for completing/skipping chores and undoing their latest completion)
Route::post('/chores/{chore}/complete', [CompletionController::class, 'complete'])->name('chores.complete');
Route::post('/chores/{chore}/skip', [CompletionController::class, 'skip'])->name('chores.skip');
Route::post('/completions/{completion}/undo', [CompletionController::class, 'undo'])->name('completions.undo');

// User resource routes
Route::resource('users', UserController::class);
