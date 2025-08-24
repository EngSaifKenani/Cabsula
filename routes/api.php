<?php

use App\Http\Controllers\ActiveIngredientController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\InventoryCountController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\RecommendedDosageController;
use App\Http\Controllers\SideEffectCategoryController;
use App\Http\Controllers\SideEffectController;
use App\Http\Controllers\SupplierController;
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
    Route::get('hi',function(){
        return view('emails.verify-code');
    });
});

    Route::prefix('v1')->middleware(['auth:sanctum','check.logout'])->group(function () {


        Route::prefix('forms')->group(function () {
            Route::get('/get-all', [FormController::class, 'index']);
            Route::get('/get-one/{id}', [FormController::class, 'show']);
            Route::post('/create', [FormController::class, 'store']);
            Route::post('/update/{id}', [FormController::class, 'update']);
             Route::delete('delete/{id}', [FormController::class, 'destroy']);

        });

        Route::prefix('manufacturers')->group(function () {
            Route::get('/get-all', [ManufacturerController::class, 'index']);
            Route::get('/get-one/{id}', [ManufacturerController::class, 'show']);
            Route::post('/create', [ManufacturerController::class, 'store']);
            Route::post('/update/{id}', [ManufacturerController::class, 'update']);
             Route::delete('delete/{id}', [ManufacturerController::class, 'destroy']);

        });

       Route::prefix('therapeutic-use')->group(function () {
            Route::get('/get-all', [TherapeuticUseController::class, 'index']);
            Route::get('/get-one/{id}', [TherapeuticUseController::class, 'show']);
            Route::post('/create', [TherapeuticUseController::class, 'store']);
            Route::post('/update/{id}', [TherapeuticUseController::class, 'update']);
            Route::delete('delete/{id}', [TherapeuticUseController::class, 'destroy']);
        });

        Route::prefix('side-effect-categories')->group(function () {
             Route::get('/get-all', [SideEffectCategoryController::class, 'index']);
            Route::get('/get-one/{id}', [SideEffectCategoryController::class, 'show']);
             Route::post('/create', [SideEffectCategoryController::class, 'store']);
            Route::post('/update/{id}', [SideEffectCategoryController::class, 'update']);
             Route::delete('delete/{id}', [SideEffectCategoryController::class, 'destroy']);
        });

        Route::prefix('side-effect')->group(function () {
             Route::get('/get-all', [SideEffectController::class, 'index']);
            Route::get('/get-one/{id}', [SideEffectController::class, 'show']);
             Route::post('/create', [SideEffectController::class, 'store']);
            Route::post('/update/{id}', [SideEffectController::class, 'update']);
             Route::delete('delete/{id}', [SideEffectController::class, 'destroy']);
            Route::get('/form-options', [SideEffectController::class, 'fetchFormOptions']);

        });

        Route::prefix('active-ingredient')->group(function () {
             Route::get('/get-all', [ActiveIngredientController::class, 'index']);
           Route::get('/get-one/{id}', [ActiveIngredientController::class, 'show']);
             Route::post('/create', [ActiveIngredientController::class, 'store']);
            Route::post('/update/{id}', [ActiveIngredientController::class, 'update']);
            Route::post('/{activeIngredient}/remove-side-effect', [ActiveIngredientController::class, 'removeSideEffectFromActiveIngredient']);
             Route::delete('delete/{id}', [ActiveIngredientController::class, 'destroy']);
            Route::get('/form-options', [ActiveIngredientController::class, 'fetchFormOptions']);

        });

      Route::prefix('drugs')->group(function () {
             Route::get('/get-all', [DrugController::class, 'index']);
            Route::get('/get-one/{id}', [DrugController::class, 'show']);
             Route::post('/create', [DrugController::class, 'store']);
            Route::post('/update/{id}', [DrugController::class, 'update']);
             Route::delete('delete/{id}', [DrugController::class, 'destroy']);
          Route::get('/form-options', [DrugController::class, 'fetchFormOptions']);
          // by id of drug
          Route::get('/get-alternative/{id}', [DrugController::class, 'getAlternativeDrugById']);
          Route::get('/get-alternative', [DrugController::class, 'getAlternativeDrugByActiveIngredients']);

      });

        Route::prefix('suppliers')->group(function () {
            Route::get('/get-one/{supplier}', [SupplierController::class, 'show']);
            Route::get('/get-all', [SupplierController::class, 'index']);
            Route::post('/create', [SupplierController::class, 'store']);
            Route::post ('/update/{supplier}', [SupplierController::class, 'update']);
            Route::delete('delete/{supplier}', [SupplierController::class, 'destroy']);
        });
                Route::prefix('purchase-invoices')->group(function () {
            Route::get('/get-one/{invoice}', [PurchaseInvoiceController::class, 'show']);
            Route::get('/get-all', [PurchaseInvoiceController::class, 'index']);
            Route::post('/create', [PurchaseInvoiceController::class, 'store']);
            Route::post ('/update/{invoice}', [PurchaseInvoiceController::class, 'update']);
            Route::delete('/delete/{invoice}', [PurchaseInvoiceController::class, 'destroy']);
            Route::post ('/update-status/{invoice}', [PurchaseInvoiceController::class, 'updateStatus']);

                });
    Route::prefix('notifications')->group(function () {
        Route::get('/get-one/{id}', [NotificationController::class, 'show']);
        Route::get('/get-all', [NotificationController::class, 'index']);
        Route::post('/create', [NotificationController::class, 'store']);
        Route::post ('/update/{id}', [NotificationController::class, 'update']);
        Route::delete('delete/{id}', [NotificationController::class, 'destroy']);

        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);

    });
    Route::prefix('recommended-dosage')->group(function () {
         Route::get('/get-all', [RecommendedDosageController::class, 'index']);
        Route::get('/get-one/{id}', [RecommendedDosageController::class, 'show']);
         Route::post('/create', [RecommendedDosageController::class, 'store']);
        Route::post ('/update/{id}', [RecommendedDosageController::class, 'update']);
         Route::delete('delete/{id}', [RecommendedDosageController::class, 'destroy']);
    });

    Route::prefix('admins')->middleware(['is_admin'])->group(function () {
        Route::get('/get-all-pharmacist', [AdminController::class, 'listPharmacists']);
        Route::get('/get-one-pharmacist/{id}', [AdminController::class, 'getPharmacistById']);
        Route::post('create-pharmacist', [AdminController::class, 'createPharmacist']);
        Route::post ('/update-pharmacist/{id}', [AdminController::class, 'updatePharmacist']);
        Route::delete('delete-pharmacist/{id}', [AdminController::class, 'deletePharmacist']);
    });

    Route::prefix('batches')->group(function () {
        Route::get('/get-all/{drug}', [BatchController::class, 'index']);
        Route::get('/get-all', [BatchController::class, 'index']);

        Route::get('/get-one/{drug}', [BatchController::class, 'show']);
        Route::post('create', [BatchController::class, 'store']);
        Route::post ('/update-status/{id}', [BatchController::class, 'updateStatus']);
        Route::delete('delete/{drug}', [BatchController::class, 'destroy']);
        Route::get('/disposed-losses', [BatchController::class, 'getDisposedLosses']);
        Route::get('/returned-value', [BatchController::class, 'getReturnedValue']);
    });


    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy']);
    Route::delete('/invoices', [InvoiceController::class, 'destroy']);
    Route::put('/invoices/{id}', [InvoiceController::class, 'update']);
    Route::get('/money/statistics', [InvoiceController::class, 'statistics']);


        Route::get('/dashboard/statistics', [\App\Http\Controllers\DashboardController::class, 'index']);




 //   Route::middleware('auth:sanctum')->post('/reports/update', [ManufacturerController::class, 'updateReports']);

    //GET /api/reports/weekly?manufacturer_id=3&year=2025
   // Route::get('/reports/weekly', [ManufacturerController::class, 'weeklyReports']);

    //GET /api/reports/monthly?manufacturer_id=3&year=2025
  //  Route::get('/reports/monthly', [ManufacturerController::class, 'monthlyReports']);

    Route::apiResource('inventory-counts', InventoryCountController::class);

});

