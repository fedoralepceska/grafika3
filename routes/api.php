<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Users and Roles API Routes
Route::middleware(['auth'])->group(function () {
    // User management routes
    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users/{id}/role', [UserController::class, 'updateRole']);
    
    // User roles routes
    Route::get('/user-roles', [UserRoleController::class, 'index']);
    Route::post('/user-roles', [UserRoleController::class, 'store']);
});
