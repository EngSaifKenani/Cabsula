<?php

use App\Http\Controllers\ActiveIngredientController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\RecommendedDosageController;
use App\Http\Controllers\SideEffectCategoryController;
use App\Http\Controllers\SideEffectController;
use App\Http\Controllers\TherapeuticUseController;
use App\Http\Controllers\VerifyAccountController; // تأكد من استيراد الكنترولر الصحيح
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

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {


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

    Route::prefix('recommended-dosage')->group(function () {
         Route::get('/get-all', [RecommendedDosageController::class, 'index']);
        Route::get('/get-one/{id}', [RecommendedDosageController::class, 'show']);
         Route::post('/create', [RecommendedDosageController::class, 'store']);
        Route::post ('/update/{id}', [RecommendedDosageController::class, 'update']);
         Route::delete('delete/{id}', [RecommendedDosageController::class, 'destroy']);
    });

});

