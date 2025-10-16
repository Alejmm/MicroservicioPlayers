<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlayerController;

Route::get('/health', [PlayerController::class, 'health']);

Route::get('/players',         [PlayerController::class, 'index']);
Route::get('/players/{id}',    [PlayerController::class, 'show']);
Route::post('/players',        [PlayerController::class, 'store']);
Route::put('/players/{id}',    [PlayerController::class, 'update']);
Route::delete('/players/{id}', [PlayerController::class, 'destroy']);
