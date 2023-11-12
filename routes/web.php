<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LargeFormatMaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SmallFormatMaterialController;
use App\Http\Controllers\SmallMaterialController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

//Rotues For USER PROFILE
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//Rotues For Invoices
Route::resource('invoices', \App\Http\Controllers\InvoiceController::class)
    ->only(['index', 'store'])
    ->middleware(['auth', 'verified']);
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('clients.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('clients.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('clients.store');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoice/download', [InvoiceController::class, 'downloadInvoiceFiles'])->name('invoice.download');
});

//Rotues For Client
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
});

//Rotues For Jobs
Route::resource('jobs', \App\Http\Controllers\JobController::class)
    ->only(['index', 'store'])
    ->middleware(['auth', 'verified']);

//Route::get('/jobs/{id}/image-dimensions', 'JobController@calculateImageDimensions');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/sync-jobs-shipping', [JobController::class, 'syncJobsWithShipping'])->name('jobs.syncJobsWithShipping');
    Route::post('/sync-all-jobs', [JobController::class, 'syncAllJobs'])->name('jobs.syncAll');
    Route::post('/get-jobs-by-ids', [JobController::class, 'getJobsByIds'])->name('jobs.getJobsByIds');
    Route::get('/jobs/{id}/image-dimensions', [JobController::class, 'calculateImageDimensions'])->name('jobs.calculateImageDimensions');
    Route::put('/jobs/{id}', [JobController::class, 'update'])->name('jobs.update');
});

//Routes For Small Format Materials
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/materials-small-format', [SmallFormatMaterialController::class, 'index'])->name('materials-small-format.index');
    Route::get('/smallFormat/materials/create', [SmallFormatMaterialController::class, 'create'])->name('materials.create');
    Route::post('/materials-small-format', [SmallFormatMaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{material}/edit', [SmallFormatMaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/materials-small-format/{material}', [SmallFormatMaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials-small-format/{material}', [SmallFormatMaterialController::class, 'destroy'])->name('materials.destroy');
    Route::get('/contacts', [ContactController::class, 'index'])->name('contact.index');
    Route::get('/get-sf-materials', [SmallFormatMaterialController::class, 'getSFMaterials'])->name('getSFMaterials');
});

//Routes For Small Materials
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/materials-small', [SmallMaterialController::class, 'index'])->name('materials-small.index');
    Route::get('/small/materials/create', [SmallMaterialController::class, 'create'])->name('materials.create');
    Route::post('/materials-small', [SmallMaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials-small/{material}/edit', [SmallMaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/materials-small/{material}', [SmallMaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials-small/{material}', [SmallMaterialController::class, 'destroy'])->name('materials.destroy');
});


//Rotues For Large Format Materials
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/materials-large', [LargeFormatMaterialController::class, 'index'])->name('materials.index');
    Route::get('/largeFormat/materials/create', [LargeFormatMaterialController::class, 'create'])->name('materials.create');
    Route::post('/materials-large', [LargeFormatMaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{material}/edit', [LargeFormatMaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/materials-large-format/{material}', [LargeFormatMaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials-large-format/{material}', [LargeFormatMaterialController::class, 'destroy']);
    Route::get('/contacts', [ContactController::class, 'index'])->name('contact.index');
});


require __DIR__.'/auth.php';
