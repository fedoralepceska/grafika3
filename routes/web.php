<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ClientCardStatementController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LargeFormatMaterialController;
use App\Http\Controllers\PriemnicaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SmallFormatMaterialController;
use App\Http\Controllers\SmallMaterialController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleCategoryController;
use App\Http\Controllers\ArticleAnalyticsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CatalogItemController;
use App\Http\Controllers\MultipartUploadController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\LargeFormatMaterial;
use App\Models\SmallMaterial;
use App\Http\Controllers\PricePerClientController;
use App\Http\Controllers\PricePerQuantityController;
use App\Http\Controllers\ClientPriceController;

use App\Http\Controllers\OfferController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\IncomingFakturaController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\TradeInvoiceController;
use App\Http\Controllers\TradeArticlesController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StockRealizationController;


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
    Route::get('/orders', [InvoiceController::class, 'index'])->name('orders.index');
    Route::get('/notInvoiced', [InvoiceController::class, 'invoiceReady'])->name('invoices.invoiceReady');
    Route::get('/api/notInvoiced/filtered', [InvoiceController::class, 'getFilteredUninvoicedOrders'])->name('api.invoices.filteredUninvoiced');
    Route::get('/invoiceGeneration',  [InvoiceController::class, 'showGenerateInvoice'])->name('invoices.showGenerateInvoice');
    Route::get('/allInvoices',  [InvoiceController::class, 'allFaktura'])->name('invoices.allFaktura');
    Route::get('/api/allInvoices/filtered', [InvoiceController::class, 'getFilteredAllInvoices'])->name('api.invoices.filteredAllInvoices');
    Route::post('/generate-invoice',  [InvoiceController::class, 'generateInvoice'])->name('invoices.generateInvoice');
    Route::post('/preview-invoice',  [InvoiceController::class, 'previewInvoice'])->name('invoices.previewInvoice');
    Route::get('orders/latest', [InvoiceController::class, 'latest'])->name('invoices.latest');
    Route::get('orders/latest-open', [InvoiceController::class, 'latestOpenOrders'])->name('invoices.latestOpen');
    Route::get('orders/completed', [InvoiceController::class, 'completedOrders'])->name('invoices.completed');
    Route::get('orders/available-users', [InvoiceController::class, 'getAvailableUsers'])->name('invoices.availableUsers');
    Route::get('/orders/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::put('/orders', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('/orders', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::delete('/orders/{id}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::get('/orders/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/orders/{id}/details', [InvoiceController::class, 'getOrderDetails'])->name('invoices.getOrderDetails');
    Route::put('/orders/update-note-flag', [InvoiceController::class, 'updateNoteProperty'])->name('invoices.updateNoteProperty');
    // Pre-generation order edits
    Route::put('/orders/{id}/title', [InvoiceController::class, 'updateOrderTitle'])->name('orders.updateTitle');
    Route::put('/orders/update-locked-note', [InvoiceController::class, 'updateLockedNote'])->name('invoices.updateLockedNote');
    Route::put('/orders/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::get('/orders/today/count', [InvoiceController::class, 'countToday'])->name('invoices.countToday');
    Route::get('/orders/tomorrow/count', [InvoiceController::class, 'countShippingTomorrow'])->name('invoices.countShippingTomorrow');
    Route::get('/orders/end-date/count', [InvoiceController::class, 'countShippingToday'])->name('invoices.countShippingToday');
    Route::get('/orders/seven-days/count', [InvoiceController::class, 'countInvoicesSevenOrMoreDaysAgo'])->name('invoices.countInvoicesSevenOrMoreDaysAgo');
    Route::get('/order/download', [InvoiceController::class, 'downloadInvoiceFiles'])->name('invoice.download');
    Route::get('/unique-clients', [ClientController::class, 'getUniqueClients']);
    Route::get('/orders/{id}/pdf', [InvoiceController::class, 'generateInvoicePdf'])->name('invoice.generateInvoicePdf');
    Route::get('/invoice/available-articles', [InvoiceController::class, 'getAvailableArticles'])->name('invoices.getAvailableArticles');
    Route::get('/invoice/{id}', [InvoiceController::class, 'getGeneratedInvoice'])->name('invoices.getGeneratedInvoice');
    Route::put('/invoice/{id}/update-comment', [InvoiceController::class, 'updateInvoiceComment'])->name('invoices.updateInvoiceComment');
    Route::get('/incomingInvoice', [\App\Http\Controllers\IncomingFakturaController::class, 'index'])->name('incomingInvoice.index');
    Route::post('/incomingInvoice', [\App\Http\Controllers\IncomingFakturaController::class, 'store'])->name('incomingInvoice.store');
    Route::get('/invoice-generation', [InvoiceController::class, 'generateAllInvoicesPdf'])->name('invoices.generateAllInvoicesPdf');
    Route::post('/outgoing/invoice', [InvoiceController::class, 'outgoingInvoicePdf'])->name('invoices.outgoingInvoicePdf');
    Route::put('/incomingInvoice/{id}', [IncomingFakturaController::class, 'update'])->name('incomingInvoice.update');

    // Invoice editing routes
    Route::put('/invoice/{fakturaId}/job/{jobId}', [InvoiceController::class, 'updateInvoiceJob'])->name('invoices.updateJob');
    Route::post('/invoice/{fakturaId}/trade-items', [InvoiceController::class, 'addTradeItem'])->name('invoices.addTradeItem');
    Route::put('/invoice/{fakturaId}/trade-items/{tradeItemId}', [InvoiceController::class, 'updateTradeItem'])->name('invoices.updateTradeItem');
    Route::delete('/invoice/{fakturaId}/trade-items/{tradeItemId}', [InvoiceController::class, 'deleteTradeItem'])->name('invoices.deleteTradeItem');
    Route::put('/invoice/{fakturaId}/invoice/{invoiceId}/title', [InvoiceController::class, 'updateInvoiceTitle'])->name('invoices.updateTitle');
    Route::put('/invoice/{fakturaId}/date', [InvoiceController::class, 'updateInvoiceDate'])->name('invoices.updateDate');
    // Utility: next faktura id for pre-generation display
    Route::get('/invoices/next-id', [InvoiceController::class, 'getNextFakturaId'])->name('invoices.nextId');

    // Individual Orders (Физичко лице)
    Route::get('/individual', [\App\Http\Controllers\IndividualOrderController::class, 'index'])->name('individual.index');
    Route::put('/individual/{id}/status', [\App\Http\Controllers\IndividualOrderController::class, 'updateStatus'])->name('individual.update-status');
    Route::put('/individual/{id}/notes', [\App\Http\Controllers\IndividualOrderController::class, 'updateNotes'])->name('individual.update-notes');

    // Trade Invoice routes
    Route::resource('trade-invoices', TradeInvoiceController::class);
    Route::get('/trade-invoices/{warehouseId}/available-articles', [TradeInvoiceController::class, 'getAvailableArticles'])->name('trade-invoices.available-articles');
    Route::put('/trade-invoices/{id}/status', [TradeInvoiceController::class, 'updateStatus'])->name('trade-invoices.update-status');
    Route::get('/trade-invoices/{id}/pdf', [TradeInvoiceController::class, 'generatePdf'])->name('trade-invoices.pdf');

    // Trade Articles routes
    Route::get('/trade-articles', [TradeArticlesController::class, 'index'])->name('trade-articles.index');
    Route::get('/trade-articles/summary', [TradeArticlesController::class, 'summary'])->name('trade-articles.summary');
    Route::get('/trade-articles/low-stock', [TradeArticlesController::class, 'lowStock'])->name('trade-articles.low-stock');
    Route::put('/trade-articles/{id}/selling-price', [TradeArticlesController::class, 'updateSellingPrice'])->name('trade-articles.update-selling-price');

    // finance routes
    Route::get('/statements', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/unique-banks', [CertificateController::class, 'getUniqueBanks']);
    Route::get('/statements/{id}', [CertificateController::class, 'getCertificate'])->name('certificates.getCertificate');
    Route::post('/item', [\App\Http\Controllers\ItemController::class, 'store'])->name('item.store');
    Route::get('/items/{id}', [\App\Http\Controllers\ItemController::class, 'getAllByCertificateId'])->name('item.getAllByCertificateId');
    Route::post('/certificate', [CertificateController::class, 'store'])->name('certificate.store');
    Route::put('/certificate/{certificate}', [CertificateController::class, 'update'])->name('certificate.update');
    Route::get('/banks-list', [CertificateController::class, 'getBanksList'])->name('banks.list');
    Route::post('/client_card_statement', [ClientCardStatementController::class, 'store'])->name('ccs.store');
    Route::get('/client_card_statement/{id}', [ClientCardStatementController::class, 'getCCSByClientId'])->name('ccs.getCCSByClientId');

    // Stock Realization routes
    Route::get('/stock-realizations', [StockRealizationController::class, 'index'])->name('stock-realizations.index');
    Route::get('/stock-realizations/pending', [StockRealizationController::class, 'pending'])->name('stock-realizations.pending');
    Route::get('/stock-realizations/{id}', [StockRealizationController::class, 'show'])->name('stock-realizations.show');
    Route::get('/stock-realizations/{id}/pdf', [StockRealizationController::class, 'generatePDF'])->name('stock-realizations.pdf');
    Route::get('/stock-realizations/{id}/debug', [StockRealizationController::class, 'debugData'])->name('stock-realizations.debug');
    Route::put('/stock-realizations/{id}/jobs/{jobId}', [StockRealizationController::class, 'updateJob'])->name('stock-realizations.updateJob');
    Route::put('/stock-realizations/{id}/jobs/{jobId}/articles/{articleId}', [StockRealizationController::class, 'updateArticle'])->name('stock-realizations.updateArticle');
    Route::post('/stock-realizations/{id}/realize', [StockRealizationController::class, 'realize'])->name('stock-realizations.realize');
    Route::post('/stock-realizations/{id}/revert', [StockRealizationController::class, 'revert'])->name('stock-realizations.revert');
    Route::get('/stock-realizations/articles/available', [StockRealizationController::class, 'getAvailableArticles'])->name('stock-realizations.availableArticles');
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
    Route::get('/api/clients', [ClientController::class, 'getClients'])->name('clients.getClients');
    Route::get('/api/clients/all', [ClientController::class, 'getAllClients']);
    Route::get('/client-details/{id}', [ClientController::class, 'getClientDetails'])->name('clients.getDetails');
});

//Routes for Client Statements
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/cardStatements', [ClientCardStatementController::class, 'index'])->name('clientCards.index');
    Route::get('/cardStatement/{id}', [ClientCardStatementController::class, 'show'])->name('cardStatement.show');
    Route::get('/clients-with-statements', [ItemController::class, 'getClientsWithCardStatements'])->name('clients.withStatements');
});

//Rotues For Jobs
Route::resource('jobs', \App\Http\Controllers\JobController::class)
    ->only(['index', 'store'])
    ->middleware(['auth', 'verified']);
Route::post('/jobs/from-manual-catalog', [JobController::class, 'storeFromManualCatalog']);

//Route::get('/jobs/{id}/image-dimensions', 'JobController@calculateImageDimensions');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/sync-jobs-shipping', [JobController::class, 'syncJobsWithShipping'])->name('jobs.syncJobsWithShipping');
    Route::post('/sync-all-jobs', [JobController::class, 'syncAllJobs'])->name('jobs.syncAll');
    Route::post('/get-jobs-by-ids', [JobController::class, 'getJobsByIds'])->name('jobs.getJobsByIds');
    Route::get('/jobs/{id}/image-dimensions', [JobController::class, 'calculateImageDimensions'])->name('jobs.calculateImageDimensions');
    Route::put('/jobs/{id}', [JobController::class, 'update'])->name('jobs.update');
    Route::put('/jobs/{id}/update-machines', [JobController::class, 'updateMachines'])->name('jobs.updateMachines');
    Route::put('/jobs/{id}/update-machine', [JobController::class, 'updateMachine'])->name('jobs.updateMachine');
    Route::post('/jobs/{id}/update-file', [JobController::class, 'updateFile'])->name('jobs.updateFile');
    Route::post('/jobs/{id}/upload-multiple-files', [JobController::class, 'uploadMultipleFiles'])->name('jobs.uploadMultipleFiles');
    Route::get('/jobs/{id}/upload-progress', [JobController::class, 'getUploadProgress'])->name('jobs.uploadProgress');
    Route::get('/jobs/{id}/download-original-file', [JobController::class, 'downloadOriginalFile'])->name('jobs.downloadOriginalFile');
    Route::post('/jobs/{id}/download-original-file', [JobController::class, 'downloadOriginalFile'])->name('jobs.downloadOriginalFilePost');
    Route::delete('/jobs/{id}/remove-original-file', [JobController::class, 'removeOriginalFile'])->name('jobs.removeOriginalFile');
    Route::delete('/jobs/{id}/remove-multiple-original-files', [JobController::class, 'removeMultipleOriginalFiles'])->name('jobs.removeMultipleOriginalFiles');
    Route::post('/orders/download-all-files', [InvoiceController::class, 'downloadAllFiles'])->name('orders.downloadAllFiles');
    Route::post('/orders/download-selected-files', [InvoiceController::class, 'downloadSelectedFiles'])->name('orders.downloadSelectedFiles');

Route::get('/jobs/{id}/articles', [JobController::class, 'getJobArticles'])->name('jobs.getArticles');
Route::get('/jobs/{jobId}/view-original-file/{fileIndex}', [JobController::class, 'viewOriginalFile'])->name('jobs.viewOriginalFile');
Route::get('/jobs/{jobId}/view-thumbnail/{fileIndex}/{page?}', [JobController::class, 'viewThumbnail'])->name('jobs.viewThumbnail');
Route::get('/jobs/{jobId}/thumbnails', [JobController::class, 'getThumbnails'])->name('jobs.getThumbnails');
Route::get('/jobs/{jobId}/local-thumbnails', [JobController::class, 'getLocalThumbnails'])->name('jobs.getLocalThumbnails');
Route::get('/jobs/{jobId}/view-legacy-file', [JobController::class, 'viewLegacyFile'])->name('jobs.viewLegacyFile');
Route::get('/api/jobs/{jobId}/thumbnail-files', [JobController::class, 'getThumbnailFiles'])->name('jobs.getThumbnailFiles');
    
    // Cutting Files Routes
    Route::post('/jobs/{id}/upload-cutting-files', [JobController::class, 'uploadCuttingFiles'])->name('jobs.uploadCuttingFiles');
    Route::get('/jobs/{id}/cutting-upload-progress', [JobController::class, 'getCuttingUploadProgress'])->name('jobs.cuttingUploadProgress');
    Route::get('/jobs/{id}/cutting-file-thumbnails', [JobController::class, 'getCuttingFileThumbnails'])->name('jobs.getCuttingFileThumbnails');
    Route::get('/jobs/{id}/local-cutting-thumbnails', [JobController::class, 'getLocalCuttingThumbnails'])->name('jobs.getLocalCuttingThumbnails');
    Route::get('/jobs/{jobId}/view-cutting-file/{fileIndex}', [JobController::class, 'viewCuttingFile'])->name('jobs.viewCuttingFile');
    Route::get('/jobs/{jobId}/view-cutting-file-thumbnail/{fileIndex}', [JobController::class, 'viewCuttingFileThumbnail'])->name('jobs.viewCuttingFileThumbnail');
    Route::delete('/jobs/{id}/remove-cutting-file', [JobController::class, 'removeCuttingFile'])->name('jobs.removeCuttingFile');
    
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
    Route::post('/jobs/admin-end-job', [JobController::class, 'adminEndJob'])->name('jobs.adminEndJob');
    Route::get('/action/{actionId}/status', [JobController::class, 'getActionStatus'])->name('jobs.getActionStatus');
Route::post('/invoice/{invoiceId}/update-status', [JobController::class, 'updateInvoiceStatusManually']);
Route::get('/invoice/{invoiceId}/debug-status', [JobController::class, 'debugInvoiceStatus']);
Route::get('/test-start-job-response', [JobController::class, 'testStartJobResponse']);
    Route::post('/jobs-with-prices', [JobController::class, 'getJobsWithPrices'])->name('jobs.getJobsWithPrices');
    Route::post('/sync-jobs-with-machine', [JobController::class, 'syncAllJobsWithMachines'])->name('jobs.syncAllJobsWithMachines');
    Route::delete('/jobs/{id}', [JobController::class, 'destroy'])->name('jobs.destroy');

    // Subcategory routes
    Route::get('/api/subcategories', [SubcategoryController::class, 'index'])->name('subcategories.index');
    Route::post('/api/subcategories', [SubcategoryController::class, 'store'])->name('subcategories.store');
    Route::put('/api/subcategories/{subcategory}', [SubcategoryController::class, 'update'])->name('subcategories.update');
    Route::delete('/api/subcategories/{subcategory}', [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');

    // Questions routes
    Route::get('/questions', [\App\Http\Controllers\QuestionController::class, 'index']);
    Route::post('/questions', [\App\Http\Controllers\QuestionController::class, 'store']);
    Route::put('/questions/{question}', [\App\Http\Controllers\QuestionController::class, 'update']);
    Route::delete('/questions/{question}', [\App\Http\Controllers\QuestionController::class, 'destroy']);
    Route::post('/questions/{question}/enable', [\App\Http\Controllers\QuestionController::class, 'enable']);
    Route::post('/questions/{question}/disable', [\App\Http\Controllers\QuestionController::class, 'disable']);
    Route::post('/questions/reorder', [\App\Http\Controllers\QuestionController::class, 'reorder']);
    Route::get('/questions/active', [\App\Http\Controllers\QuestionController::class, 'active']);
    Route::get('/questions/catalog-item/{catalogItem}', [\App\Http\Controllers\QuestionController::class, 'getByCatalogItem']);
    Route::post('/questions/catalog-item/{catalogItem}', [\App\Http\Controllers\QuestionController::class, 'updateCatalogItemQuestions']);
    Route::get('/admin/questions', function () {
        return Inertia::render('Questions/QuestionsManager');
    })->name('admin.questions');
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
    Route::get('/materials/small', [SmallMaterialController::class, 'getSmallMaterials'])->name('materials-small.getSmallMaterials');
    Route::get('/materials/small/all', [SmallMaterialController::class, 'getAllMaterials'])->name('materials-small.getAllMaterials');
    Route::get('/materials/pdf', [SmallMaterialController::class, 'generateSmallMaterialsPdf'])->name('materials.pdf');
    Route::get('/materials/all-pdf', [SmallMaterialController::class, 'generateAllSmallMaterialsPdf'])->name('materials.all-pdf');
    Route::get('/small/materials/create', [SmallMaterialController::class, 'create'])->name('materials.create');
    Route::post('/materials-small', [SmallMaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials-small/{material}/edit', [SmallMaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/materials-small/{material}', [SmallMaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials-small/{material}', [SmallMaterialController::class, 'destroy'])->name('materials.destroy');
});


//Rotues For Large Format Materials
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/materials-large', [LargeFormatMaterialController::class, 'index'])->name('materials.index');
    Route::get('/materials/large', [LargeFormatMaterialController::class, 'getLargeMaterials'])->name('largeMaterials.getLargeMaterials');
    Route::get('/materials/large/all', [LargeFormatMaterialController::class, 'getAllMaterials'])->name('largeMaterials.getAllMaterials');
    Route::get('/materials/large/pdf', [LargeFormatMaterialController::class, 'generateLargeMaterialsPdf'])->name('materials.large.pdf');
    Route::get('/materials/large/all-pdf', [LargeFormatMaterialController::class, 'generateAllLargeMaterialsPdf'])->name('materials.large.all-pdf');
    Route::get('/largeFormat/materials/create', [LargeFormatMaterialController::class, 'create'])->name('materials.create');
    Route::post('/materials-large', [LargeFormatMaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{material}/edit', [LargeFormatMaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/materials-large-format/{material}', [LargeFormatMaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials-large-format/{material}', [LargeFormatMaterialController::class, 'destroy']);
    Route::get('/contacts', [ContactController::class, 'index'])->name('contact.index');
});

//Routes for Materials
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/materials', [\App\Http\Controllers\MaterialController::class, 'index'])->name('material.index');
});

//Routes for Warehouse
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/warehouse', [\App\Http\Controllers\WarehouseController::class, 'index'])->name('warehouse.index');
    Route::post('/api/warehouses', [\App\Http\Controllers\WarehouseController::class, 'createWarehouse']);
    Route::get('/api/warehouses', [\App\Http\Controllers\WarehouseController::class, 'getWarehouses']);
    Route::delete('/api/warehouse/{id}', [\App\Http\Controllers\WarehouseController::class, 'deleteWarehouse']);
});

//Routes for Machines
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/get-machines', [\App\Http\Controllers\MachineController::class, 'getMachines'])->name('machine.index');
    Route::post('/machines', [\App\Http\Controllers\MachineController::class, 'createMachine']);
    Route::delete('/machines/{id}', [\App\Http\Controllers\MachineController::class, 'deleteMachine']);
});

//Routes For Articles
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/count', [ArticleController::class, 'getCount'])->name('articles.getCount');
    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles/create', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/view', [ArticleController::class, 'show'])->name('articles.show');
    Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    Route::get('/articles/search', [\App\Http\Controllers\ArticleController::class, 'search'])->name('api.articles.search');
    Route::get('/articles/{id}', [\App\Http\Controllers\ArticleController::class, 'get'])->name('api.articles.get');
});

// Routes For Article Analytics
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/articles/{article}/analytics/stock', [ArticleAnalyticsController::class, 'getStockInfo'])->name('articles.analytics.stock');
    Route::get('/articles/{article}/analytics/orders', [ArticleAnalyticsController::class, 'getOrderUsage'])->name('articles.analytics.orders');
    Route::get('/articles/{article}/analytics/monthly', [ArticleAnalyticsController::class, 'getMonthlyUsage'])->name('articles.analytics.monthly');
    Route::get('/articles/{article}/analytics/dashboard', [ArticleAnalyticsController::class, 'getDashboard'])->name('articles.analytics.dashboard');
});

//Routes For Article Categories
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('article-categories', ArticleCategoryController::class);
    Route::get('/api/article-categories', [ArticleCategoryController::class, 'getCategories'])->name('api.article-categories.index');
});

//Routes for Priemnici
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/receipt', [\App\Http\Controllers\PriemnicaController::class, 'index'])->name('priemnica.index');
    Route::get('/api/priemnica', [PriemnicaController::class, 'fetchPriemnica']);
    Route::get('/receipt/create', [\App\Http\Controllers\PriemnicaController::class, 'create'])->name('priemnica.create');
    Route::post('/receipt/create', [\App\Http\Controllers\PriemnicaController::class, 'store'])->name('priemnica.store');
    Route::get('/receipt/{id}/edit', [\App\Http\Controllers\PriemnicaController::class, 'edit'])->name('priemnica.edit');
    Route::put('/receipt/{id}', [\App\Http\Controllers\PriemnicaController::class, 'update'])->name('priemnica.update');
    Route::get('/api/receipt/{id}', [\App\Http\Controllers\PriemnicaController::class, 'show'])->name('priemnica.show');
    Route::get('/api/material-import-summary', [PriemnicaController::class, 'getMaterialImportSummary'])->name('priemnica.materialImportSummary');
});

//Routes for Refinements
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/refinements', [\App\Http\Controllers\RefinementsController::class, 'index'])->name('refinements.index');
    Route::get('/refinements/all', [\App\Http\Controllers\RefinementsController::class, 'getRefinements'])->name('refinements.getRefinements');
    Route::get('/refinements/{refinement}/usage', [\App\Http\Controllers\RefinementsController::class, 'usage'])->name('refinements.usage');
    Route::post('/refinements/create', [\App\Http\Controllers\RefinementsController::class, 'store'])->name('refinements.store');
    Route::put('/refinements/{refinement}', [\App\Http\Controllers\RefinementsController::class, 'update'])->name('refinements.update');
    Route::delete('/refinements/{id}', [\App\Http\Controllers\RefinementsController::class, 'destroy'])->name('refinements.destroy');
});

//Routes for Banks
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/api/banks', [\App\Http\Controllers\BanksController::class, 'createBank']);
    Route::get('/api/banks', [\App\Http\Controllers\BanksController::class, 'getBanks']);
    Route::delete('/api/banks/{id}', [\App\Http\Controllers\BanksController::class, 'deleteBank']);
    Route::put('/api/banks/{id}', [\App\Http\Controllers\BanksController::class, 'updateBank']);
});

// Analytics
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/analytics', [AnalyticsController::class, 'index']);
    Route::post('insert-analytics', [JobController::class, 'insertAnalytics']);

    Route::get('/user-invoice-counts', [AnalyticsController::class, 'getUserInvoiceCounts']);
    Route::get('/analytics/orders', function () {
        return Inertia::render('Analytics/UserInvoiceAnalytics');
    });

    Route::get('/article-invoice-counts', [AnalyticsController::class, 'getArticlesInvoiceCounts']);
    Route::get('/analytics/articles', function () {
        return Inertia::render('Analytics/ArticleAnalytics');
    });

    Route::get('/analytics/workers', function () {
        return Inertia::render('Analytics/WorkerAnalytics');
    });
    Route::get('/user-invoice-time-spent', [AnalyticsController::class, 'getWorkerAnalytics']);

    Route::get('/client-invoice-counts', [AnalyticsController::class, 'getClientsInvoiceCounts']);
    Route::get('/client-invoice-costs-counts', [AnalyticsController::class, 'getClientCosts']);
    Route::get('/analytics/clients', function () {
        return Inertia::render('Analytics/ClientAnalytics');
    });
});

Route::get('/catalog-items', [JobController::class, 'getCatalogItems']);
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/catalog', [CatalogItemController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/create', [CatalogItemController::class, 'create'])->name('catalog.create');
    Route::post('/catalog', [CatalogItemController::class, 'store'])->name('catalog.store');
    Route::get('/catalog/{catalogItem}/edit', [CatalogItemController::class, 'edit'])->name('catalog.edit');
    Route::put('/catalog/{catalogItem}', [CatalogItemController::class, 'update'])->name('catalog.update');
    Route::delete('/catalog/{id}', [CatalogItemController::class, 'destroy'])->name('catalog.destroy');
    Route::delete('/catalog/{id}/force', [CatalogItemController::class, 'forceDelete'])->name('catalog.force-delete');
    Route::post('/catalog/{id}/copy', [CatalogItemController::class, 'copy'])->name('catalog.copy');
    Route::get('/catalog_items/offer', [CatalogItemController::class, 'fetchAllForOffer'])->name('catalog.fetchAllForOffer');
    Route::get('/catalog/{catalogItem}/download-template', [CatalogItemController::class, 'downloadTemplate'])->name('catalog.download-template');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/offer', [\App\Http\Controllers\OfferController::class, 'index'])->name('offer.index');
    Route::get('/offers', [\App\Http\Controllers\OfferController::class, 'getOffers'])->name('offer.getOffers');
    Route::get('/offer/create', [\App\Http\Controllers\OfferController::class, 'create'])->name('offer.create');
    Route::post('/offers', [\App\Http\Controllers\OfferController::class, 'store'])->name('offers.store');
    Route::delete('/offer/{offer}', [\App\Http\Controllers\OfferController::class, 'destroy'])->name('offer.destroy');
    Route::get('/offer-client/create', [\App\Http\Controllers\OfferController::class, 'displayOfferClientPage'])->name('offer.displayOfferClientPage');
    Route::post('/offer-client', [\App\Http\Controllers\OfferController::class, 'storeOfferClient'])->name('offer.storeOfferClient');
    Route::get('/offer-client', [\App\Http\Controllers\OfferController::class, 'getOffersClients'])->name('offer.getOffersClients');
    Route::post('/offer-client/accept', [\App\Http\Controllers\OfferController::class, 'acceptOffer'])->name('offer.acceptOffer');
    Route::get('/offer-client/details/{id}', [\App\Http\Controllers\OfferController::class, 'getDetails'])->name('offer.getDetails');

    // Offer routes
    Route::resource('offers', \App\Http\Controllers\OfferController::class);
    Route::get('/offers/{offer}/items', [\App\Http\Controllers\OfferController::class, 'items'])->name('offers.items');
    Route::get('/offers/{offer}/pdf', [OfferController::class, 'generateOfferPdf'])->name('offers.pdf');
    Route::get('/calculate-price', [PriceController::class, 'calculatePrice'])->name('price.calculate');
});

// Routes for catalog edit form data
Route::get('/get-machines-print', function () {
    return response()->json(DB::table('machines_print')->select('id', 'name')->get());
});

Route::get('/get-machines-cut', function () {
    return response()->json(DB::table('machines_cut')->select('id', 'name')->get());
});

Route::get('/get-materials', function () {
    $largeMaterials = LargeFormatMaterial::with(['article' => function($query) {
        $query->select('id', 'name', 'code');
    }])->get();

    $smallMaterials = SmallMaterial::with(['article' => function($query) {
        $query->select('id', 'name', 'code');
    }])->get();

    return response()->json([
        'largeMaterials' => $largeMaterials,
        'smallMaterials' => $smallMaterials
    ]);
});

Route::get('/get-actions', function () {
    return response()->json(
        DB::table('dorabotka')
            ->select('id', 'name', 'isMaterialized')
            ->whereNotNull('name')
            ->get()
    );
});

// Price Management Routes
Route::middleware(['auth'])->group(function () {
    // Client-specific prices routes
    Route::get('/client-prices', [ClientPriceController::class, 'index'])->name('client-prices.index');
    Route::get('/client-prices/create', [ClientPriceController::class, 'create'])->name('client-prices.create');
    Route::post('/client-prices', [ClientPriceController::class, 'store'])->name('client-prices.store');
    Route::get('/client-prices/{clientPrice}/edit', [ClientPriceController::class, 'edit'])->name('client-prices.edit');
    Route::put('/client-prices/{clientPrice}', [ClientPriceController::class, 'update'])->name('client-prices.update');
    Route::delete('/client-prices/{clientPrice}', [ClientPriceController::class, 'destroy'])->name('client-prices.destroy');
    Route::get('/catalog-items/{catalogItem}/client-prices', [ClientPriceController::class, 'getByCatalogItem'])->name('client-prices.get-by-catalog-item');

    // Grouped Quantity Prices
    Route::get('/quantity-prices', [PricePerQuantityController::class, 'index'])->name('quantity-prices.index');
    Route::get('/quantity-prices/create', [PricePerQuantityController::class, 'create'])->name('quantity-prices.create');
    Route::post('/quantity-prices', [PricePerQuantityController::class, 'store'])->name('quantity-prices.store');
    Route::get('/quantity-prices/{catalogItem}/{client}/view', [PricePerQuantityController::class, 'view'])->name('quantity-prices.view');
    Route::get('/quantity-prices/{catalogItem}/{client}/edit', [PricePerQuantityController::class, 'editGroup'])->name('quantity-prices.edit-group');
    Route::put('/quantity-prices/{catalogItem}/{client}', [PricePerQuantityController::class, 'updateGroup'])->name('quantity-prices.update-group');
    Route::delete('/quantity-prices/{catalogItem}/{client}', [PricePerQuantityController::class, 'destroyGroup'])->name('quantity-prices.destroy-group');
    Route::get('/catalog-items/{catalogItem}/clients/{client}/quantity-prices', [PricePerQuantityController::class, 'getQuantityPrices'])->name('quantity-prices.get');
});





// Offer Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
    Route::get('/offers/create', [OfferController::class, 'create'])->name('offers.create');
    Route::post('/offers', [OfferController::class, 'store'])->name('offers.store');
    Route::get('/offers/{offer}', [OfferController::class, 'show'])->name('offers.show');
    Route::get('/offers/{offer}/edit', [OfferController::class, 'edit'])->name('offers.edit');
    Route::put('/offers/{offer}', [OfferController::class, 'update'])->name('offers.update');
    Route::patch('/offers/{offer}/status', [OfferController::class, 'updateStatus'])->name('offers.update-status');
    Route::get('/offers/{offer}/items', [OfferController::class, 'items'])->name('offers.items');
    Route::get('/offers/{offer}/pdf', [OfferController::class, 'generateOfferPdf'])->name('offers.pdf');
    Route::get('/calculate-price', [PriceController::class, 'calculatePrice'])->name('price.calculate');
    Route::delete('/offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/items/{id}', [ItemController::class, 'getAllByCertificateId']);
        Route::put('/items/{item}', [ItemController::class, 'update']);
        Route::delete('/items/{item}', [ItemController::class, 'destroy']);

    });
});

Route::get('/api/next-faktura-counter', [IncomingFakturaController::class, 'getNextFakturaCounter']);

//Routes For User Roles
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/user-roles', function () {
        return Inertia::render('UserRoles/Index');
    })->name('user-roles.index');

    Route::prefix('api')->group(function () {
        Route::get('/user-roles', [UserRoleController::class, 'index']);
        Route::post('/user-roles', [UserRoleController::class, 'store']);
        Route::put('/user-roles/{userRole}', [UserRoleController::class, 'update']);
        Route::delete('/user-roles/{userRole}', [UserRoleController::class, 'destroy']);
    });
});

// User Roles API Routes
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/user-roles', [UserRoleController::class, 'index']);
    Route::post('/user-roles', [UserRoleController::class, 'store']);
    Route::put('/user-roles/{userRole}', [UserRoleController::class, 'update']);
    Route::delete('/user-roles/{userRole}', [UserRoleController::class, 'destroy']);
});

//Routes For User Management
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/user-management', function () {
        return Inertia::render('UserManagement/Index');
    })->name('user-management.index');

    Route::prefix('api')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
        Route::put('/users/{user}/password', [UserController::class, 'changePassword']);
        Route::get('/users/{user}/orders', [UserController::class, 'checkUserOrders']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});

Route::post('/jobs/questions-for-catalog-items', [\App\Http\Controllers\JobController::class, 'getQuestionsForCatalogItems']);
Route::post('/jobs/recalculate-cost', [\App\Http\Controllers\JobController::class, 'recalculateJobCost'])->name('jobs.recalculateCost');

// Multipart Upload Routes for Large Files
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/uploads/multipart/start', [MultipartUploadController::class, 'start'])->name('uploads.multipart.start');
    Route::post('/uploads/multipart/sign-part', [MultipartUploadController::class, 'signPart'])->name('uploads.multipart.signPart');
    Route::post('/uploads/multipart/complete', [MultipartUploadController::class, 'complete'])->name('uploads.multipart.complete');
    Route::post('/uploads/multipart/abort', [MultipartUploadController::class, 'abort'])->name('uploads.multipart.abort');
});

// Material dropdown API endpoints
Route::get('/api/materials/large-dropdown', [LargeFormatMaterialController::class, 'largeDropdown']);
Route::get('/api/materials/small-dropdown', [SmallMaterialController::class, 'smallDropdown']);

require __DIR__.'/auth.php';
