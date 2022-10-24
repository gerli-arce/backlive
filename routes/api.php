<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\UserController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ACTIVITY

Route::get('/activities', [ActivityController::class, 'get']);

// USER

Route::get('/users', [UserController::class, 'get']);
Route::post('/users', [UserController::class, 'register']);



// // VIEWS 
// Route::get('/views', [ViewsController::class, 'index']);
// Route::post('/views', [ViewsController::class, 'store']);
// Route::put('/views', [ViewsController::class, 'update']);
// Route::delete('/views', [ViewsController::class, 'delete']);
// Route::post('/views/restore', [ViewsController::class, 'restore']);
// Route::post('/views/paginate', [ViewsController::class, 'paginate']);
