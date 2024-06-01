<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ClientCardStatementController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LargeFormatMaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SmallFormatMaterialController;
use App\Http\Controllers\SmallMaterialController;
use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Auth;
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
    // Check if the user is logged in
    if (Auth::check()) {
        // Redirect to /dashboard if the user is logged in
        return redirect()->to('/dashboard');
    } else {
        // Stay on / with the Login page if the user is not logged in
        return Inertia::render('Auth/Login');
    }
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

//Rotues For Invoices and Orders
Route::resource('invoices', \App\Http\Controllers\InvoiceController::class)
    ->only(['index', 'store'])
    ->middleware(['auth', 'verified']);
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/orders', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/notInvoiced', [InvoiceController::class, 'invoiceReady'])->name('invoices.invoiceReady');
    Route::get('/invoiceGeneration',  [InvoiceController::class, 'showGenerateInvoice'])->name('invoices.showGenerateInvoice');
    Route::get('/allInvoices',  [InvoiceController::class, 'allFaktura'])->name('invoices.allFaktura');
    Route::post('/generate-invoice',  [InvoiceController::class, 'generateInvoice'])->name('invoices.generateInvoice');
    Route::get('orders/latest', [InvoiceController::class, 'latest'])->name('invoices.latest');
    Route::get('/orders/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::put('/orders', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('/orders', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/orders/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::put('/orders/update-note-flag', [InvoiceController::class, 'updateNoteProperty'])->name('invoices.updateNoteProperty');
    Route::put('/orders/update-locked-note', [InvoiceController::class, 'updateLockedNote'])->name('invoices.updateLockedNote');
    Route::put('/orders/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::get('/orders/today/count', [InvoiceController::class, 'countToday'])->name('invoices.countToday');
    Route::get('/orders/tomorrow/count', [InvoiceController::class, 'countShippingTomorrow'])->name('invoices.countShippingTomorrow');
    Route::get('/orders/end-date/count', [InvoiceController::class, 'countShippingToday'])->name('invoices.countShippingToday');
    Route::get('/orders/seven-days/count', [InvoiceController::class, 'countInvoicesSevenOrMoreDaysAgo'])->name('invoices.countInvoicesSevenOrMoreDaysAgo');
    Route::get('/order/download', [InvoiceController::class, 'downloadInvoiceFiles'])->name('invoice.download');
    Route::get('/unique-clients', [ClientController::class, 'getUniqueClients']);
    Route::get('/orders/{id}/pdf', [InvoiceController::class, 'generateInvoicePdf'])->name('invoice.generateInvoicePdf');
    Route::get('/invoice/{id}', [InvoiceController::class, 'getGeneratedInvoice'])->name('invoices.getGeneratedInvoice');
    Route::put('/invoice/{id}/update-comment', [InvoiceController::class, 'updateInvoiceComment'])->name('invoices.updateInvoiceComment');
    Route::get('/incomingInvoice', [\App\Http\Controllers\IncomingFakturaController::class, 'index'])->name('incomingInvoice.index');
    Route::post('/incomingInvoice', [\App\Http\Controllers\IncomingFakturaController::class, 'store'])->name('incomingInvoice.store');


    // finance routes
    Route::get('/statements', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/unique-banks', [CertificateController::class, 'getUniqueBanks']);
    Route::get('/statements/{id}', [CertificateController::class, 'getCertificate'])->name('certificates.getCertificate');
    Route::post('/item', [\App\Http\Controllers\ItemController::class, 'store'])->name('item.store');
    Route::get('/items/{id}', [\App\Http\Controllers\ItemController::class, 'getAllByCertificateId'])->name('item.getAllByCertificateId');
    Route::post('/certificate', [CertificateController::class, 'store'])->name('certificate.store');
    Route::post('/client_card_statement', [ClientCardStatementController::class, 'store'])->name('ccs.store');
    Route::get('/client_card_statement/{id}', [ClientCardStatementController::class, 'getCCSByClientId'])->name('ccs.getCCSByClientId');
});

//Rotues For Client
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::post('/clients/{id}/contact', [ContactController::class, 'store'])->name('contacts.store');
    Route::delete('/clients/{clientId}/contacts/{contactId}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

});

//Routes for Client Statements
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/cardStatements', [ClientCardStatementController::class, 'index'])->name('clientCards.index');
    Route::get('/cardStatement/{id}', [ClientCardStatementController::class, 'show'])->name('cardStatement.show');
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
    Route::get('/jobs/{id}', [JobController::class, 'show'])->name('jobs.show');
    Route::get('/job-action-status-counts', [JobController::class, 'jobActionStatusCounts']);
    Route::get('/job-machine-print-counts', [JobController::class, 'jobMachinePrintCounts']);
    Route::get('/job-machine-cut-counts', [JobController::class, 'jobMachineCutCounts']);
    Route::get('/production', [JobController::class, 'production'])->name('jobs.production');
    Route::get('/machines', [JobController::class, 'machine'])->name('jobs.machine');
    Route::get('/actions/{id}', [\App\Http\Controllers\ActionController::class, 'index'])->name('actions.index');
    Route::get('/machines/{id}', [\App\Http\Controllers\MachineController::class, 'index'])->name('machines.index');
    Route::put('/actions/{id}', [\App\Http\Controllers\ActionController::class, 'update'])->name('actions.update');
    Route::get('/actions/{id}/jobs', [JobController::class, 'getJobsByActionId'])->name('jobs.getJobsByActionId');
    Route::get('/machines/{id}/jobs', [JobController::class, 'getActionsByMachineName'])->name('jobs.getActionsByMachineName');
    Route::post('/jobs/start-job', [JobController::class, 'fireStartJobEvent'])->name('jobs.fireStartJobEvent');
    Route::post('/jobs/end-job', [JobController::class, 'fireEndJobEvent'])->name('jobs.fireEndJobEvent');
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
    Route::get('/get-materials-small', [SmallMaterialController::class, 'getSmallMaterials'])->name('materials-small.getSmallMaterials');
    Route::get('/small/materials/create', [SmallMaterialController::class, 'create'])->name('materials.create');
    Route::post('/materials-small', [SmallMaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials-small/{material}/edit', [SmallMaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/materials-small/{material}', [SmallMaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials-small/{material}', [SmallMaterialController::class, 'destroy'])->name('materials.destroy');
});


//Rotues For Large Format Materials
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/materials-large', [LargeFormatMaterialController::class, 'index'])->name('materials.index');
    Route::get('/get-large-materials', [LargeFormatMaterialController::class, 'getLargeMaterials'])->name('largeMaterials.getLargeMaterials');
    Route::get('/largeFormat/materials/create', [LargeFormatMaterialController::class, 'create'])->name('materials.create');
    Route::post('/materials-large', [LargeFormatMaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{material}/edit', [LargeFormatMaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/materials-large-format/{material}', [LargeFormatMaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials-large-format/{material}', [LargeFormatMaterialController::class, 'destroy']);
    Route::get('/contacts', [ContactController::class, 'index'])->name('contact.index');
});

//Routes For Articles
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles/create', [ArticleController::class, 'store'])->name('articles.store');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
});

//Routes for Priemnici
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/receipt', [\App\Http\Controllers\PriemnicaController::class, 'index'])->name('priemnica.index');
    Route::get('/receipt/create', [\App\Http\Controllers\PriemnicaController::class, 'create'])->name('priemnica.create');
    Route::post('/receipt/create', [\App\Http\Controllers\PriemnicaController::class, 'store'])->name('priemnica.store');
});

//Routes for Refinements
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/refinements', [\App\Http\Controllers\RefinementsController::class, 'index'])->name('refinements.index');
});

//Routes for Banks
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/api/banks', [\App\Http\Controllers\BanksController::class, 'createBank']);
    Route::get('/api/banks', [\App\Http\Controllers\BanksController::class, 'getBanks']);
    Route::delete('/api/banks/{id}', [\App\Http\Controllers\BanksController::class, 'deleteBank']);
});

//Routes for Dorabotki
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dorabotki', [\App\Http\Controllers\DorabotkaController::class, 'index'])->name('dorabotka.index');
    Route::get('/dorabotki/create', [\App\Http\Controllers\DorabotkaController::class, 'create'])->name('dorabotka.create');
    Route::post('/dorabotki/create', [\App\Http\Controllers\DorabotkaController::class, 'store'])->name('dorabotka.store');
});

require __DIR__.'/auth.php';
