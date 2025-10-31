<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlayerController;

Route::get('/health', [PlayerController::class, 'health']);

/**
 * Rutas existentes 
 */
Route::get('/players',         [PlayerController::class, 'index']);
Route::get('/players/{id}',    [PlayerController::class, 'show']);
Route::post('/players',        [PlayerController::class, 'store']);
Route::put('/players/{id}',    [PlayerController::class, 'update']);
Route::delete('/players/{id}', [PlayerController::class, 'destroy']);

/**
 * Nuevas rutas compatibles con el frontend 
 * - /api/jugadores           => listado sin paginar (array)
 * - /api/jugadores/paged     => listado paginado (items, totalItems, page, pageSize)
 * - /api/jugadores/{id}      => detalle
 * - POST/PUT/DELETE          => CRUD
 *
 * Nota: requieren que el PlayerController tenga el método listAll()
 * además de index(), show(), store(), update(), destroy().
 */
Route::get('/jugadores',            [PlayerController::class, 'listAll']);
Route::get('/jugadores/paged',      [PlayerController::class, 'index']);
Route::get('/jugadores/{id}',       [PlayerController::class, 'show']);
Route::post('/jugadores',           [PlayerController::class, 'store']);
Route::put('/jugadores/{id}',       [PlayerController::class, 'update']);
Route::delete('/jugadores/{id}',    [PlayerController::class, 'destroy']);
