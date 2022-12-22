<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViewsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\GraphController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PDFController;
use App\Models\Role;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// USERS

Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);

// SESSION
Route::post('/session/login', [SessionController::class, 'login']);
// Route::post('/session/logout', [SessionController::class, 'logout']);
Route::post('/session/verify', [SessionController::class, 'verify']);

// GRAPH CONTROLLER
Route::get('/graph/counts', [GraphController::class, 'counts']);
Route::get('/graph/invoices', [GraphController::class, 'invoices']);
Route::get('/graph/solution', [GraphController::class, 'solution']);

// INVOICES
Route::post('/invoices/paginate', [InvoiceController::class, 'paginate']);
Route::post('/invoices/create', [InvoiceController::class, 'create']);
Route::patch('/invoices', [InvoiceController::class, 'update']);
Route::put('/invoices', [InvoiceController::class, 'update']);
Route::post('/invoices/pdf', [PDFController::class, 'generatePDF']);
Route::post('/invoices/pdf/save', [InvoiceController::class, 'savePDF']);
Route::post('/invoices/share', [InvoiceController::class, 'share']);

Route::any('*', null);
