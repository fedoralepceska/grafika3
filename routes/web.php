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
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CatalogItemController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\LargeFormatMaterial;
use App\Models\SmallMaterial;
use App\Http\Controllers\PricePerClientController;
use App\Http\Controllers\PricePerQuantityController;
use App\Http\Controllers\ClientPriceController;
use App\Http\Controllers\QuantityPriceController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\IncomingFakturaController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\QuestionController;


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
    Route::get('/invoice-generation', [InvoiceController::class, 'generateAllInvoicesPdf'])->name('invoices.generateAllInvoicesPdf');
    Route::post('/outgoing/invoice', [InvoiceController::class, 'outgoingInvoicePdf'])->name('invoices.outgoingInvoicePdf');
    Route::put('/incomingInvoice/{id}', [IncomingFakturaController::class, 'update'])->name('incomingInvoice.update');



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

//Route::get('/jobs/{id}/image-dimensions', 'JobController@calculateImageDimensions');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/sync-jobs-shipping', [JobController::class, 'syncJobsWithShipping'])->name('jobs.syncJobsWithShipping');
    Route::post('/sync-all-jobs', [JobController::class, 'syncAllJobs'])->name('jobs.syncAll');
    Route::post('/get-jobs-by-ids', [JobController::class, 'getJobsByIds'])->name('jobs.getJobsByIds');
    Route::get('/jobs/{id}/image-dimensions', [JobController::class, 'calculateImageDimensions'])->name('jobs.calculateImageDimensions');
    Route::put('/jobs/{id}', [JobController::class, 'update'])->name('jobs.update');
    Route::post('/jobs/{id}/update-file', [JobController::class, 'updateFile'])->name('jobs.updateFile');
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
    Route::put('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    Route::get('/articles/search', [\App\Http\Controllers\ArticleController::class, 'search'])->name('api.articles.search');
    Route::get('/articles/{id}', [\App\Http\Controllers\ArticleController::class, 'get'])->name('api.articles.get');
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
});

//Routes for Refinements
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/refinements', [\App\Http\Controllers\RefinementsController::class, 'index'])->name('refinements.index');
    Route::get('/refinements/all', [\App\Http\Controllers\RefinementsController::class, 'getRefinements'])->name('refinements.getRefinements');
    Route::post('/refinements/create', [\App\Http\Controllers\RefinementsController::class, 'store'])->name('refinements.store');
    Route::put('/refinements/{refinement}', [\App\Http\Controllers\RefinementsController::class, 'update'])->name('refinements.update');
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
    Route::delete('/catalog/{catalogItem}', [CatalogItemController::class, 'destroy'])->name('catalog.destroy');
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
    // Client Prices
    Route::get('/client-prices', [PricePerClientController::class, 'index'])->name('client-prices.index');
    Route::get('/client-prices/create', [PricePerClientController::class, 'create'])->name('client-prices.create');
    Route::post('/client-prices', [PricePerClientController::class, 'store'])->name('client-prices.store');
    Route::get('/client-prices/{clientPrice}/edit', [PricePerClientController::class, 'edit'])->name('client-prices.edit');
    Route::put('/client-prices/{clientPrice}', [PricePerClientController::class, 'update'])->name('client-prices.update');
    Route::delete('/client-prices/{clientPrice}', [PricePerClientController::class, 'destroy'])->name('client-prices.destroy');
    Route::get('/catalog-items/{catalogItem}/client-prices', [PricePerClientController::class, 'getClientPrices'])->name('client-prices.get');

    // Quantity Prices
    Route::get('/quantity-prices', [PricePerQuantityController::class, 'index'])->name('quantity-prices.index');
    Route::get('/quantity-prices/create', [PricePerQuantityController::class, 'create'])->name('quantity-prices.create');
    Route::post('/quantity-prices', [PricePerQuantityController::class, 'store'])->name('quantity-prices.store');
    Route::get('/quantity-prices/{quantityPrice}/edit', [PricePerQuantityController::class, 'edit'])->name('quantity-prices.edit');
    Route::put('/quantity-prices/{quantityPrice}', [PricePerQuantityController::class, 'update'])->name('quantity-prices.update');
    Route::delete('/quantity-prices/{quantityPrice}', [PricePerQuantityController::class, 'destroy'])->name('quantity-prices.destroy');
    Route::get('/catalog-items/{catalogItem}/clients/{client}/quantity-prices', [PricePerQuantityController::class, 'getQuantityPrices'])->name('quantity-prices.get');
});

// Client-specific prices routes
Route::middleware(['auth'])->group(function () {
    Route::get('/client-prices', [ClientPriceController::class, 'index'])->name('client-prices.index');
    Route::get('/client-prices/create', [ClientPriceController::class, 'create'])->name('client-prices.create');
    Route::post('/client-prices', [ClientPriceController::class, 'store'])->name('client-prices.store');
    Route::get('/client-prices/{clientPrice}/edit', [ClientPriceController::class, 'edit'])->name('client-prices.edit');
    Route::put('/client-prices/{clientPrice}', [ClientPriceController::class, 'update'])->name('client-prices.update');
    Route::delete('/client-prices/{clientPrice}', [ClientPriceController::class, 'destroy'])->name('client-prices.destroy');
});
// Quantity-based prices routes
Route::middleware(['auth'])->group(function () {
    // Routes for QuantityPriceController (multiple ranges)
    Route::get('/quantity-prices', [QuantityPriceController::class, 'index'])->name('quantity-prices.index');
    Route::get('/quantity-prices/create', [QuantityPriceController::class, 'create'])->name('quantity-prices.create');
    Route::post('/quantity-prices', [QuantityPriceController::class, 'store'])->name('quantity-prices.store');
    
    // Routes for PricePerQuantityController (single ranges)
    Route::post('/quantity-prices/single', [PricePerQuantityController::class, 'store'])->name('quantity-prices.store.single');
    Route::get('/price-per-quantity/{quantityPrice}/edit', [PricePerQuantityController::class, 'edit'])->name('price-per-quantity.edit');
    Route::post('/price-per-quantity/{quantityPrice}', [PricePerQuantityController::class, 'update'])->name('price-per-quantity.update');
    Route::delete('/price-per-quantity/{quantityPrice}', [PricePerQuantityController::class, 'destroy'])->name('price-per-quantity.destroy');
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
    });
});

Route::post('/jobs/questions-for-catalog-items', [\App\Http\Controllers\JobController::class, 'getQuestionsForCatalogItems']);

// Material dropdown API endpoints
Route::get('/api/materials/large-dropdown', [LargeFormatMaterialController::class, 'largeDropdown']);
Route::get('/api/materials/small-dropdown', [SmallMaterialController::class, 'smallDropdown']);

require __DIR__.'/auth.php';
