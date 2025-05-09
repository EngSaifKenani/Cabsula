<?php

namespace App\Http\Controllers;

use App\Http\Resources\SideEffectCategoryResource;
use App\Http\Resources\SideEffectResource;
use App\Models\ActiveIngredient;
use App\Models\SideEffect;
use App\Models\SideEffectCategory;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SideEffectController extends Controller
{

    public function fetchFormOptions()
    {
        return response()->json([
            'categories' =>  SideEffectCategoryResource::collection( SideEffectCategory::select('id', 'name')->get()),
        ]);
    }

    public function index(Request $request)
    {

        $validRelations = $this->extractValidRelations(SideEffect::class, $request);
        $sideEffects = SideEffect::with($validRelations)->get();

        return $this->success(SideEffectResource::collection($sideEffects), 'تم جلب التصنيفات بنجاح');
    }

    public function show(Request $request, $id)
    {
        $validRelations = $this->extractValidRelations(SideEffect::class, $request);
        $sideEffect = SideEffect::with($validRelations)->findOrFail($id);
        return $this->success(new SideEffectResource($sideEffect), 'تم جلب التأثير الجانبي بنجاح');
    }

    public function store(Request $request, TranslationService $translationService)
    {
        $request->validate([
            'category_id' => 'required|exists:side_effect_categories,id',
            'name' => 'required|string|max:255',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
            'activeIngredients_ids' => 'nullable|array',
            'activeIngredients_ids.*' => 'exists:active_ingredients,id',
            'activeIngredients_ids'
        ]);

        $sideEffect = SideEffect::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
        ]);

        $sourceLocale = 'en';

            foreach ($request->translations as $translation) {
                $locale = $translation['locale'];

                $translatedValue = $locale === $sourceLocale
                    ? $request->name
                    : $translationService->translate($request->name, $sourceLocale, $locale);

                $sideEffect->translations()->updateOrCreate(
                    ['locale' => $translation['locale'], 'field' => 'name'],
                    ['value' => $translatedValue]
                );
            }


        if ($request->has('activeIngredients_ids')) {
            $sideEffect->activeIngredients()->sync($request->input('activeIngredients_ids'));
        }

        return $this->success(new SideEffectResource($sideEffect), 'تم إنشاء التأثير الجانبي وربطه بالأسماء العلمية بنجاح', 201);
    }


    public function update(Request $request, $id, TranslationService $translationService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:side_effect_categories,id',
            'translations' => 'required|array',
            'translations.*.locale' => 'required_with:translations|string|in:en,ar,fr',
            'activeIngredients_ids' => 'nullable|array',
            'activeIngredients_ids.*' => 'exists:active_ingredients,id',
        ]);

        $sideEffect = SideEffect::findOrFail($id);

        $sideEffect->update([
            'name' => $request->name,
            'category_id' => $request->category_id ?? $sideEffect->category_id,
        ]);
        $sourceLocale='en';
        foreach ($request->translations as $translation) {

            $locale = $translation['locale'];

            $translatedValue = $locale === $sourceLocale
                ? $request->name
                : $translationService->translate($request->name, $sourceLocale, $locale);

            $sideEffect->translations()->updateOrCreate(
                ['locale' => $translation['locale'], 'field' => 'name'],
                ['value' => $translatedValue]
            );
        }

        if ($request->has('activeIngredients_ids')) {
            $activeIngredients = ActiveIngredient::find($request->activeIngredients_ids);
            $sideEffect->ActiveIngredients()->sync($activeIngredients);
        }

        return $this->success(new SideEffectResource($sideEffect), 'تم تحديث التأثير الجانبي وربطه بالأسماء العلمية بنجاح');
    }


    public function removeActiveIngredientFromSideEffect(Request $request, $sideEffectId)
    {
        $sideEffect = SideEffect::findOrFail($sideEffectId);

        if ($request->has('scientific_name_id')) {
            $ActiveIngredientId = $request->scientific_name_id;

            // التحقق من وجود العلاقة بين التأثير الجانبي واسم العلمي
            if ($sideEffect->ActiveIngredients()->where('scientific_name_id', $ActiveIngredientId)->exists()) {
                // إزالة العلاقة
                $sideEffect->ActiveIngredients()->detach($ActiveIngredientId);

                return response()->json([
                    'message' => 'تم إزالة الاسم العلمي من التأثير الجانبي بنجاح.',
                    'data' => [
                        'side_effect_id' => $sideEffectId,
                        'scientific_name_id' => $ActiveIngredientId,
                    ],
                ], 200);
            }

            return response()->json([
                'message' => 'الاسم العلمي غير موجود لهذا التأثير الجانبي.',
            ], 404);
        }

        return $this->error([], 'scientific_name_id مفقود في الطلب.');
    }


    public function destroy($id)
    {
        $sideEffect = SideEffect::findOrFail($id);
        $sideEffect->translations()->delete();
        $sideEffect->activeIngredients()->detach();  // إزالة العلاقة بين التأثير الجانبي والأسماء العلمية
        $sideEffect->delete();

        return $this->success([], 'تم حذف التأثير الجانبي بنجاح');
    }
}
