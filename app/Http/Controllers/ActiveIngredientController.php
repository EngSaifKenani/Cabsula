<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActiveIngredientResource;
use App\Http\Resources\SideEffectResource;
use App\Http\Resources\TherapeuticUseResource;
use App\Models\ActiveIngredient;
use App\Models\SideEffect;
use App\Models\TherapeuticUse;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ActiveIngredientController extends Controller
{
    public function fetchFormOptions()
    {
        return response()->json([
            'side_effects' =>  SideEffectResource::collection( SideEffect::select('id', 'name')->get()),
            'therapeutic_uses' =>TherapeuticUseResource::collection( TherapeuticUse::select('id', 'name')->get()),
        ]);
    }


    public function index(Request $request)
    {
        $validRelations = $this->extractValidRelations(ActiveIngredient::class, $request);
        $activeIngredients = ActiveIngredient::with($validRelations)->get();

        return $this->success(ActiveIngredientResource::collection($activeIngredients));
    }

    public function show(Request $request, $id)
    {
        $validRelations = $this->extractValidRelations(ActiveIngredient::class, $request);
        $activeIngredient = ActiveIngredient::with($validRelations)->findOrFail($id);

        return $this->success(new ActiveIngredientResource($activeIngredient));
    }

    public function store(Request $request, TranslationService $translationService)
    {
        $validated = $request->validate([
            'scientific_name' => 'required|string|max:255|unique:active_ingredients,scientific_name',
            'description' => 'nullable|string',
            'cas_number' => 'nullable|string|max:100',
            'unii_code' => 'nullable|string|max:100',
            'is_active' => 'boolean',

            'side_effect' => 'required|array',      // بدون _ids
            'side_effect.*' => 'exists:side_effects,id',

            'therapeutic_uses' => 'required|array',
            'therapeutic_uses.*.id' => 'required|exists:therapeutic_uses,id',
            'therapeutic_uses.*.is_popular' => 'boolean',

            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
            'translations.*.scientific_name' => 'required|string|max:255',
            'translations.*.description' => 'nullable|string',
        ]);

        $activeIngredient = DB::transaction(function () use ($validated, $translationService) {
            $activeIngredient = ActiveIngredient::create([
                'scientific_name' => $validated['scientific_name'],
                'description' => $validated['description'],
                'cas_number' => $validated['cas_number'],
                'unii_code' => $validated['unii_code'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // حفظ الترجمات
            $sourceLocale = 'en';

            foreach ($validated['translations'] as $translation) {
                $activeIngredient->translations()->updateOrCreate(
                    ['locale' => $translation['locale'], 'field' => 'scientific_name'],
                    ['value' => $translation['locale'] == $sourceLocale ? $validated['scientific_name'] : $translation['scientific_name']]
                );



                if (isset($translation['description'])) {
                    $locale = $translation['locale'];

                    $translatedValue = $locale === $sourceLocale
                        ? $validated['description']
                        : $translationService->translate($validated['description'], $sourceLocale, $locale);

                    $activeIngredient->translations()->updateOrCreate(
                        ['locale' => $locale, 'field' => 'description'],
                        ['value' => $translatedValue]
                    );
                }
            }

            $activeIngredient->sideEffects()->sync($validated['side_effect']);

            $syncData = [];
            foreach ($validated['therapeutic_uses'] as $use) {
                $syncData[$use['id']] = [
                    'is_popular' => $use['is_popular'] ?? false
                ];
            }
            $activeIngredient->therapeuticUses()->sync($syncData);

            return $activeIngredient;
        });

        return $this->success(
            new ActiveIngredientResource($activeIngredient->load(['sideEffects', 'therapeuticUses'])),
            'تم إضافة المادة الفعالة بنجاح',
            201
        );
    }

    public function update(Request $request, $id, TranslationService $translationService)
    {
        $activeIngredient = ActiveIngredient::findOrFail($id);

        $validated = $request->validate([
            'scientific_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('active_ingredients', 'scientific_name')->ignore($activeIngredient->id)
            ],
            'description' => 'nullable|string',
            'cas_number' => 'nullable|string|max:100',
            'unii_code' => 'nullable|string|max:100',
            'is_active' => 'boolean',

            'side_effect' => 'nullable|array',
            'side_effect.*' => 'exists:side_effects,id',

            'therapeutic_uses' => 'nullable|array',
            'therapeutic_uses.*.id' => 'required|exists:therapeutic_uses,id',
            'therapeutic_uses.*.is_popular' => 'boolean',

            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
            'translations.*.scientific_name' => 'required|string|max:255',
            'translations.*.description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($activeIngredient, $validated, $translationService) {
            $activeIngredient->update([
                'scientific_name' => $validated['scientific_name'],
                'description' => $validated['description'] ?? $activeIngredient->description,
                'cas_number' => $validated['cas_number'] ?? $activeIngredient->cas_number,
                'unii_code' => $validated['unii_code'] ?? $activeIngredient->unii_code,
                'is_active' => $validated['is_active'] ?? $activeIngredient->is_active,
            ]);
            $sourceLocale = 'en';

            foreach ($validated['translations'] as $translation) {
                $locale = $translation['locale'];

                $activeIngredient->translations()->updateOrCreate(
                    ['locale' => $locale, 'field' => 'scientific_name'],
                    ['value' =>$translation['locale'] == $sourceLocale ? $validated['scientific_name'] : $translation['scientific_name']]
                );


                if (isset($translation['description'])) {
                    $translatedValue = $locale === $sourceLocale
                        ? ($validated['description'] ?? $activeIngredient->description)
                        : $translationService->translate($validated['description'] ?? $activeIngredient->description, $sourceLocale, $locale);

                    $activeIngredient->translations()->updateOrCreate(
                        ['locale' => $locale, 'field' => 'description'],
                        ['value' => $translatedValue]
                    );
                }
            }

            if (isset($validated['side_effect'])) {
                $activeIngredient->sideEffects()->sync($validated['side_effect']);
            }

            if (isset($validated['therapeutic_uses'])) {
                $syncData = [];
                foreach ($validated['therapeutic_uses'] as $use) {
                    $syncData[$use['id']] = [
                        'is_popular' => $use['is_popular'] ?? false
                    ];
                }
                $activeIngredient->therapeuticUses()->sync($syncData);
            }
        });

        return $this->success(
            new ActiveIngredientResource($activeIngredient),
            'تم تحديث المادة الفعالة بنجاح'
        );
    }

    public function removeSideEffectFromActiveIngredient(Request $request, $activeIngredientId)
    {
        $request->validate([
            'side_effects' => 'required|array',
            'side_effects.*' => 'exists:side_effects,id'
        ]);

        $activeIngredient = ActiveIngredient::findOrFail($activeIngredientId);

        $activeIngredient->sideEffects()->detach($request->side_effects);

        return $this->success([
            'scientific_name_id' => $activeIngredientId,
            'side_effects' => $request->side_effects,
        ], 'تم إزالة الآثار الجانبية بنجاح.');
    }


    public function destroy($id)
    {
        $activeIngredient = ActiveIngredient::findOrFail($id);

        DB::transaction(function () use ($activeIngredient) {
            $activeIngredient->translations()->delete();
            $activeIngredient->sideEffects()->detach();
            $activeIngredient->therapeuticUses()->detach();
            $activeIngredient->forceDelete();
        });

        return $this->success([], 'تم حذف المادة الفعالة بنجاح.');
    }
}
