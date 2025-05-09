<?php

use App\Http\Controllers\ActiveIngredientController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConcentrationController;
use App\Http\Controllers\DrugConcentrationDosageController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\RecommendedDosageController;
use App\Http\Controllers\ScientificNameController;
use App\Http\Controllers\SideEffectCategoryController;
use App\Http\Controllers\SideEffectController;
use App\Http\Controllers\TherapeuticUseController;
use App\Http\Controllers\VerifyAccountController; // تأكد من استيراد الكنترولر الصحيح
use App\Services\TranslationService;
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

// 🔒 Protected Routes (للعملاء المسجلين فقط)
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {


    // for form
    Route::prefix('forms')->group(function () {
        Route::get('/', [FormController::class, 'index']);
        Route::get('/{id}', [FormController::class, 'show']);
        Route::post('/', [FormController::class, 'store']);
        Route::post('/{id}', [FormController::class, 'update']);
        Route::delete('/{id}', [FormController::class, 'destroy']);

    });

    // for manufacturer
    Route::prefix('manufacturers')->group(function () {
        Route::get('/', [ManufacturerController::class, 'index']);
        Route::get('/{id}', [ManufacturerController::class, 'show']);
        Route::post('/', [ManufacturerController::class, 'store']);
        Route::post('/{id}', [ManufacturerController::class, 'update']);
        Route::delete('/{id}', [ManufacturerController::class, 'destroy']);

    });

    // for categories
   Route::prefix('therapeutic-use')->group(function () {
        Route::get('/', [TherapeuticUseController::class, 'index']);
        Route::get('/{id}', [TherapeuticUseController::class, 'show']);
        Route::post('/', [TherapeuticUseController::class, 'store']);
        Route::post('/{id}', [TherapeuticUseController::class, 'update']);
        Route::delete('/{id}', [TherapeuticUseController::class, 'destroy']);
    });

    //for concentrations
    Route::prefix('concentrations')->group(function () {
        Route::get('/', [ConcentrationController::class, 'index']);
        Route::get('/{id}', [ConcentrationController::class, 'show']);
        Route::post('/', [ConcentrationController::class, 'store']);
        Route::post('/{id}', [ConcentrationController::class, 'update']);
        Route::delete('/{id}', [ConcentrationController::class, 'destroy']);

    });

    Route::prefix('side-effect-categories')->group(function () {
        Route::get('/', [SideEffectCategoryController::class, 'index']);
        Route::get('/{id}', [SideEffectCategoryController::class, 'show']);
        Route::post('/', [SideEffectCategoryController::class, 'store']);
        Route::post('/{id}', [SideEffectCategoryController::class, 'update']);
        Route::delete('/{id}', [SideEffectCategoryController::class, 'destroy']);
    });

    Route::prefix('side-effect')->group(function () {
        Route::get('/', [SideEffectController::class, 'index']);
        Route::get('/{id}', [SideEffectController::class, 'show']);
        Route::post('/', [SideEffectController::class, 'store']);
        Route::post('/{id}', [SideEffectController::class, 'update']);
        Route::delete('/{id}', [SideEffectController::class, 'destroy']);
    });

    Route::prefix('active-ingredient')->group(function () {
        Route::get('/', [ActiveIngredientController::class, 'index']);
        Route::get('/{id}', [ActiveIngredientController::class, 'show']);
        Route::post('/', [ActiveIngredientController::class, 'store']);
        Route::post('/{id}', [ActiveIngredientController::class, 'update']);
        Route::post('/{activeIngredient}/remove-side-effect', [ActiveIngredientController::class, 'removeSideEffectFromActiveIngredient']);
        Route::delete('/{id}', [ActiveIngredientController::class, 'destroy']);
    });

  Route::prefix('drugs')->group(function () {
        Route::get('/', [DrugController::class, 'index']);
        Route::get('/{id}', [DrugController::class, 'show']);
        Route::post('/', [DrugController::class, 'store']);
        Route::post('/{id}', [DrugController::class, 'update']);
        Route::delete('/{id}', [DrugController::class, 'destroy']);

    });

    Route::prefix('recommended-dosage')->group(function () {
        Route::get('/', [RecommendedDosageController::class, 'index']);
        Route::get('/{id}', [RecommendedDosageController::class, 'show']);
        Route::post('/', [RecommendedDosageController::class, 'store']);
        Route::post ('/{id}', [RecommendedDosageController::class, 'update']);
        Route::delete('/{id}', [RecommendedDosageController::class, 'destroy']);
    });


/*    Route::prefix('drug-concentration-dosage')->group(function () {
        Route::get('/', [DrugConcentrationDosageController::class, 'index']);
        Route::get('/{id}', [DrugConcentrationDosageController::class, 'show']);
        Route::post('/', [DrugConcentrationDosageController::class, 'store']);
        Route::post('/{id}', [DrugConcentrationDosageController::class, 'update']);
        Route::delete('/{id}', [DrugConcentrationDosageController::class, 'destroy']);
    });*/


});
























Route::get('/test-translate', function (TranslationService $translator) {
    try {
        $translated = $translator->translate('Take two tablet daily', 'en', 'ar');
        return response()->json(['translated' => $translated]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
//
//
//
//Route::get('/test-translate/local',   function (TranslationService $translator)
//{
//    $text = 'Hello, how are you?';
//    $translated = $translator->translate($text, 'en', 'ar');
//
//    return response()->json([
//        'original' => $text,
//        'translated' => $translated,
//    ]);
//});

