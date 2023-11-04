<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LargeFormatMaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SmallFormatMaterialController;
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
    Route::post('/get-user',[\App\Http\Controllers\Auth\RegisteredUserController::class, 'show']);
    Route::post('/get-client',[ClientController::class, 'show']);
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/SmallFormatMaterials', [SmallFormatMaterialController::class, 'index'])->name('materials.index');
    Route::get('/smallFormat/materials/create', [SmallFormatMaterialController::class, 'create'])->name('materials.create');
    Route::post('/materials', [SmallFormatMaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{material}/edit', [SmallFormatMaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/materials/{material}', [SmallFormatMaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials/{material}', [SmallFormatMaterialController::class, 'destroy'])->name('materials.destroy');
    Route::get('/contacts', [ContactController::class, 'index'])->name('contact.index');
});

//Rotues For Large Format Materials
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/LargeFormatMaterials', [LargeFormatMaterialController::class, 'index'])->name('materials.index');
    Route::get('/largeFormat/materials/create', [LargeFormatMaterialController::class, 'create'])->name('materials.create');
    Route::post('/materials', [LargeFormatMaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{material}/edit', [LargeFormatMaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/materials/{material}', [LargeFormatMaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials/{material}', [LargeFormatMaterialController::class, 'destroy'])->name('materials.destroy');
    Route::get('/contacts', [ContactController::class, 'index'])->name('contact.index');
});


require __DIR__.'/auth.php';
