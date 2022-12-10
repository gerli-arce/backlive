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


// Route::get('/users', [UserController::class, 'index']);
// Route::post('/users', [UserController::class, 'store']);
// Route::put('/users', [UserController::class, 'update']);
// Route::delete('/users', [UserController::class, 'destroy']);
// Route::post('/users/restore', [UserController::class, 'restore']);
// Route::get('/users/get/{username}', [UserController::class, 'getUser']);
// Route::post('/users/paginate', [UserController::class, 'paginate']);
// Route::post('/users/media', [UserController::class, 'searchByMedia']);

// // PROFILE
// Route::get('/profile/{relative_id}/{zize}', [ProfileController::class, 'profile']);
// Route::put('/profile/account', [ProfileController::class, 'account']);
// Route::patch('/profile/account', [ProfileController::class, 'account']);
// Route::put('/profile/password', [ProfileController::class, 'password']);
// Route::patch('/profile/password', [ProfileController::class, 'password']);
// Route::put('/profile/personal', [ProfileController::class, 'personal']);
// Route::patch('/profile/personal', [ProfileController::class, 'personal']);

// // COVER
// Route::get('/cover/{relative_id}/{zize}', [ProfileController::class, 'cover']);


// SESSION
Route::post('/session/login', [SessionController::class, 'login']);
// Route::post('/session/logout', [SessionController::class, 'logout']);
Route::post('/session/verify', [SessionController::class, 'verify']);


// // ROLE
// Route::get('/roles', [RoleController::class, 'index']);
// Route::post('/roles', [RoleController::class, 'store']);
// Route::put('/roles', [RoleController::class, 'update']);
// Route::patch('/roles', [RoleController::class, 'update']);
// Route::delete('/roles', [RoleController::class, 'destroy']);
// Route::post('/roles/restore', [RoleController::class, 'restore']);
// Route::post('/roles/paginate', [RoleController::class, 'paginate']);
// Route::put('/roles/permissions', [RoleController::class, 'permissions']);


// // VIEWS 
// Route::get('/views', [ViewsController::class, 'index']);
// Route::post('/views/paginate', [ViewsController::class, 'paginate']);
// Route::post('/views', [ViewsController::class, 'store']);
// Route::put('/views', [ViewsController::class, 'update']);
// Route::delete('/views', [ViewsController::class, 'delete']);
// Route::post('/views/restore', [ViewsController::class, 'restore']);


// // PERMISSIONS
// Route::get('/permissions', [PermissionController::class, 'index']);
// Route::post('/permissions', [PermissionController::class, 'store']);
// Route::put('/permissions', [PermissionController::class, 'update']);
// Route::delete('/permissions', [PermissionController::class, 'delete']);
// Route::post('/permissions/restore', [PermissionController::class, 'restore']);
// Route::post('/permissions/paginate', [PermissionController::class, 'paginate']);


// // SERVICES
// Route::get('/services', [ServiceController::class, 'index']);
// Route::post('/services', [ServiceController::class, 'store']);
// Route::patch('/services', [ServiceController::class, 'update']);
// Route::delete('/services', [ServiceController::class, 'delete']);
// Route::post('/services/restore', [ServiceController::class, 'restore']);
// Route::post('/services/paginate', [ServiceController::class, 'paginate']);

// // MODULES
// Route::get('/modules', [ModuleController::class, 'index']);
// Route::post('/modules', [ModuleController::class, 'store']);
// Route::patch('/modules', [ModuleController::class, 'update']);
// Route::delete('/modules', [ModuleController::class, 'delete']);
// Route::post('/modules/restore', [ModuleController::class, 'restore']);
// Route::post('/modules/paginate', [ModuleController::class, 'paginate']);
// Route::post('/modules/search', [ModuleController::class, 'search']);

// // EVIRONMENT
// Route::get('/environments', [EnvironmentController::class, 'index']);
// Route::post('/environments', [EnvironmentController::class, 'store']);
// Route::patch('/environments', [EnvironmentController::class, 'update']);
// Route::delete('/environments', [EnvironmentController::class, 'delete']);
// Route::post('/environments/restore', [EnvironmentController::class, 'restore']);
// Route::post('/environments/paginate', [EnvironmentController::class, 'paginate']);

// //ACTIVITIES
// Route::get('/activities/pending', [ActivityController::class, 'getpending']);

// Route::post('/activities/pending/paginate', [ActivityController::class, 'paginatePending']);
// Route::post('/activities/done/paginate', [ActivityController::class, 'paginateDone']);
// Route::post('/activities/toreview/paginate', [ActivityController::class, 'paginateToReView']);

// Route::post('/activities', [ActivityController::class, 'store']);
// Route::put('/activities', [ActivityController::class, 'update']);
// Route::patch('/activities', [ActivityController::class, 'update']);
// Route::delete('/activities', [ActivityController::class, 'delete']);

// Route::post('/activities/restore', [ActivityController::class, 'restore']);
// Route::post('/activities/paginate', [ActivityController::class, 'paginate']);
// Route::post('/activities/reject/{id}', [ActivityController::class, 'reject']);
// Route::post('/activities/resolve/{id}', [ActivityController::class, 'resolve']);
// Route::post('/activities/details', [ActivityController::class, 'details']);
// Route::put('/activities/markasdone', [ActivityController::class, 'markasdone']);
// Route::patch('/activities/markasdone', [ActivityController::class, 'markasdone']);
// Route::put('/activities/markastoreview', [ActivityController::class, 'markastoreview']);
// Route::patch('/activities/markastoreview', [ActivityController::class, 'markastoreview']);
// Route::post('/activities/cancel/{id}', [ActivityController::class, 'cancel']);

// Route::post('/activities/user/{username}', [ActivityController::class, 'getByUser']);

// // TO REVIEW
// Route::post('/activities/toreview/{action}', [ActivityController::class, 'observe']);

// // REVIEWED
// Route::post('/activities/reviewed/paginate', [ActivityController::class, 'paginateReviewed']);
// Route::post('/activities/reviewed/movetopending', [ActivityController::class, 'movetopending']);
// Route::post('/activities/reviewed/movetodone', [ActivityController::class, 'movetodone']);

// // DELETED
// Route::post('/activities/deleted/paginate', [ActivityController::class, 'paginateDeleted']);
// Route::post('/activities/deleted/restore', [ActivityController::class, 'restore']);

// //EVICENCES
// Route::get('/evidences/activity/{id}', [EvidenceController::class, 'index']);
// Route::post('/evidences', [EvidenceController::class, 'store']);
// Route::patch('/evidences', [EvidenceController::class, 'update']);
// Route::delete('/evidences/{id}', [EvidenceController::class, 'delete']);

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
