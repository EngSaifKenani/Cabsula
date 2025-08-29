<?php

use App\Http\Controllers\ActiveIngredientController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\DisposalController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\InventoryCountController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\RecommendedDosageController;
use App\Http\Controllers\SideEffectCategoryController;
use App\Http\Controllers\SideEffectController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierReturnController;
use App\Http\Controllers\TherapeuticUseController;
use App\Http\Controllers\VerifyAccountController; // تأكد من استيراد الكنترولر الصحيح
use App\Models\Notification;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 🔓 Public Routes
Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/send-code/email', [VerifyAccountController::class, 'sendCodeToEmail']);
});

    Route::prefix('v1')->middleware(['auth:sanctum','check.logout'])->group(function () {


        Route::prefix('forms')->group(function () {
            Route::get('/get-all', [FormController::class, 'index'])->middleware('is_admin');
            Route::get('/get-one/{id}', [FormController::class, 'show'])->middleware('is_admin');
            Route::post('/create', [FormController::class, 'store'])->middleware('is_admin');
            Route::post('/update/{id}', [FormController::class, 'update'])->middleware('is_admin');
             Route::delete('delete/{id}', [FormController::class, 'destroy'])->middleware('is_admin');

        });

        Route::prefix('manufacturers')->group(function () {
            Route::get('/get-all', [ManufacturerController::class, 'index'])->middleware('is_admin');
            Route::get('/get-one/{id}', [ManufacturerController::class, 'show'])->middleware('is_admin');
            Route::post('/create', [ManufacturerController::class, 'store'])->middleware('is_admin');
            Route::post('/update/{id}', [ManufacturerController::class, 'update'])->middleware('is_admin');
             Route::delete('delete/{id}', [ManufacturerController::class, 'destroy'])->middleware('is_admin');

        });

       Route::prefix('therapeutic-use')->group(function () {
            Route::get('/get-all', [TherapeuticUseController::class, 'index'])->middleware('is_admin');
            Route::get('/get-one/{id}', [TherapeuticUseController::class, 'show'])->middleware('is_admin');
            Route::post('/create', [TherapeuticUseController::class, 'store'])->middleware('is_admin');
            Route::post('/update/{id}', [TherapeuticUseController::class, 'update'])->middleware('is_admin');
            Route::delete('delete/{id}', [TherapeuticUseController::class, 'destroy'])->middleware('is_admin');
        });

        Route::prefix('side-effect-categories')->group(function () {
             Route::get('/get-all', [SideEffectCategoryController::class, 'index'])->middleware('is_admin');
            Route::get('/get-one/{id}', [SideEffectCategoryController::class, 'show'])->middleware('is_admin');
             Route::post('/create', [SideEffectCategoryController::class, 'store'])->middleware('is_admin');
            Route::post('/update/{id}', [SideEffectCategoryController::class, 'update'])->middleware('is_admin');
             Route::delete('delete/{id}', [SideEffectCategoryController::class, 'destroy'])->middleware('is_admin');
        });

        Route::prefix('side-effect')->group(function () {
             Route::get('/get-all', [SideEffectController::class, 'index'])->middleware('is_admin');
            Route::get('/get-one/{id}', [SideEffectController::class, 'show'])->middleware('is_admin');
             Route::post('/create', [SideEffectController::class, 'store'])->middleware('is_admin');
            Route::post('/update/{id}', [SideEffectController::class, 'update'])->middleware('is_admin');
             Route::delete('delete/{id}', [SideEffectController::class, 'destroy'])->middleware('is_admin');
            Route::get('/form-options', [SideEffectController::class, 'fetchFormOptions'])->middleware('is_admin');

        });

        Route::prefix('active-ingredient')->group(function () {
             Route::get('/get-all', [ActiveIngredientController::class, 'index'])->middleware('is_admin');
           Route::get('/get-one/{id}', [ActiveIngredientController::class, 'show'])->middleware('is_admin');
             Route::post('/create', [ActiveIngredientController::class, 'store'])->middleware('is_admin');
            Route::post('/update/{id}', [ActiveIngredientController::class, 'update'])->middleware('is_admin');
            Route::post('/{activeIngredient}/remove-side-effect', [ActiveIngredientController::class, 'removeSideEffectFromActiveIngredient'])->middleware('is_admin');
             Route::delete('delete/{id}', [ActiveIngredientController::class, 'destroy'])->middleware('is_admin');
            Route::get('/form-options', [ActiveIngredientController::class, 'fetchFormOptions'])->middleware('is_admin');

        });

      Route::prefix('drugs')->group(function () {
             Route::get('/get-all', [DrugController::class, 'index']);
            Route::get('/get-one/{id}', [DrugController::class, 'show']);
             Route::post('/create', [DrugController::class, 'store'])->middleware('is_admin');
            Route::post('/update/{id}', [DrugController::class, 'update'])->middleware('is_admin');
             Route::delete('delete/{id}', [DrugController::class, 'destroy'])->middleware('is_admin');
          Route::get('/form-options', [DrugController::class, 'fetchFormOptions'])->middleware('is_admin');
          // by id of drug
          Route::get('/get-alternative/{id}', [DrugController::class, 'getAlternativeDrugById']);
          Route::get('/get-alternative', [DrugController::class, 'getAlternativeDrugByActiveIngredients']);

      });

        Route::prefix('suppliers')->group(function () {
            Route::get('/get-one/{supplier}', [SupplierController::class, 'show'])->middleware('is_admin');
            Route::get('/get-all', [SupplierController::class, 'index'])->middleware('is_admin');
            Route::post('/create', [SupplierController::class, 'store'])->middleware('is_admin');
            Route::post ('/update/{supplier}', [SupplierController::class, 'update'])->middleware('is_admin');
            Route::delete('delete/{supplier}', [SupplierController::class, 'destroy'])->middleware('is_admin');
        });
                Route::prefix('purchase-invoices')->group(function () {
            Route::get('/get-one/{invoice}', [PurchaseInvoiceController::class, 'show'])->middleware('is_admin');
            Route::get('/get-all', [PurchaseInvoiceController::class, 'index'])->middleware('is_admin');
            Route::post('/create', [PurchaseInvoiceController::class, 'store'])->middleware('is_admin');
            Route::post ('/update/{invoice}', [PurchaseInvoiceController::class, 'update'])->middleware('is_admin');
            Route::delete('/delete/{invoice}', [PurchaseInvoiceController::class, 'destroy'])->middleware('is_admin');

                });
    Route::prefix('notifications')->group(function () {
        Route::get('/get-one/{notification}', [NotificationController::class, 'show']);
        Route::get('/get-all', [NotificationController::class, 'index']);
        Route::post('/create', [NotificationController::class, 'store']);
        Route::post ('/update/{notification}', [NotificationController::class, 'update']);

        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('/{notification}/read', [NotificationController::class, 'markAsRead']);

    });
    Route::prefix('recommended-dosage')->group(function () {
         Route::get('/get-all', [RecommendedDosageController::class, 'index'])->middleware('is_admin');
        Route::get('/get-one/{id}', [RecommendedDosageController::class, 'show'])->middleware('is_admin');
         Route::post('/create', [RecommendedDosageController::class, 'store'])->middleware('is_admin');
        Route::post ('/update/{id}', [RecommendedDosageController::class, 'update'])->middleware('is_admin');
         Route::delete('delete/{id}', [RecommendedDosageController::class, 'destroy'])->middleware('is_admin');
    });

    Route::prefix('admins')->middleware('is_admin')->group(function () {
        Route::get('/get-all-pharmacist', [AdminController::class, 'listPharmacists']);
        Route::get('/get-one-pharmacist/{id}', [AdminController::class, 'getPharmacistById']);
        Route::post('create-pharmacist', [AdminController::class, 'createPharmacist']);
        Route::post ('/update-pharmacist/{id}', [AdminController::class, 'updatePharmacist']);
        Route::delete('delete-pharmacist/{id}', [AdminController::class, 'deletePharmacist']);
        Route::get('/money/statistics', [AdminController::class, 'statistics']);

    });

    Route::prefix('batches')->group(function () {
        Route::get('/get-all/{drug}', [BatchController::class, 'index'])->middleware('is_admin');
        Route::get('/get-all', [BatchController::class, 'index'])->middleware('is_admin');
        Route::get('/get-one/{drug}', [BatchController::class, 'show'])->middleware('is_admin');
        Route::post('create', [BatchController::class, 'store']);
        Route::delete('delete/{drug}', [BatchController::class, 'destroy']);
        Route::post('/dispose-full/{batch}', [BatchController::class, 'disposeFullBatch'])->middleware('is_admin');
        Route::post('/return-full/{batch}', [BatchController::class, 'returnFullBatch'])->middleware('is_admin');
    });

        Route::prefix('payments')->middleware('is_admin')->group(function () {
            Route::get('/get-all', [PaymentController::class, 'index']);
            Route::post('/create', [PaymentController::class, 'store']);
            Route::get('/summary', [PaymentController::class, 'getPaymentsSummary']);
            Route::get('/get-one/{payment}', [PaymentController::class, 'show']);
            Route::post('/update/{payment}', [PaymentController::class, 'update']);
            Route::delete('/delete/{payment}', [PaymentController::class, 'destroy']);
        });

        Route::prefix('disposals')->middleware('is_admin')->group(function () {
            Route::get('/get-all', [DisposalController::class, 'index']);
            Route::post('/create', [DisposalController::class, 'store']);
            Route::get('/summary', [DisposalController::class, 'getDisposalSummary']);
            Route::get('/get-one/{disposal}', [DisposalController::class, 'show']);
            Route::post('/update/{disposal}', [DisposalController::class, 'update']);
            Route::delete('/delete/{disposal}', [DisposalController::class, 'destroy']);
        });

        Route::prefix('supplier-returns')->middleware('is_admin')->group(function () {
            Route::get('/get-all', [SupplierReturnController::class, 'index']);
            Route::post('/create', [SupplierReturnController::class, 'store']);
            Route::get('/summary', [SupplierReturnController::class, 'getReturnsSummary']);
            Route::get('/get-one/{supplierReturn}', [SupplierReturnController::class, 'show']);
            Route::post('/update/{supplierReturn}', [SupplierReturnController::class, 'update']);
            Route::delete('/delete/{supplierReturn}', [SupplierReturnController::class, 'destroy']);
        });


    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy']);
    Route::delete('/invoices', [InvoiceController::class, 'destroy']);
    Route::put('/invoices/{id}', [InvoiceController::class, 'update']);



    Route::get('/dashboard/statistics', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware('is_admin');
    Route::apiResource('inventory-counts', InventoryCountController::class)->middleware('is_admin');

});

