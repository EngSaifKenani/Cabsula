<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActiveIngredientResource;
use App\Http\Resources\DrugResource;
use App\Http\Resources\FormResource;
use App\Http\Resources\ManufacturerResource;
use App\Http\Resources\RecommendedDosageResource;
use App\Models\ActiveIngredient;
use App\Models\Drug;
use App\Models\Form;
use App\Models\Manufacturer;
use App\Models\RecommendedDosage;
use App\Models\Translation;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DrugController extends Controller
{
    public function fetchFormOptions()
    {
        return response()->json([
            'forms' =>  FormResource::collection( Form::select('id', 'name')->get()),
            'manufacturers' =>ManufacturerResource::collection( Manufacturer::select('id', 'name')->get()),
            'active_ingredients' => ActiveIngredientResource::collection(ActiveIngredient::active()->select('id', 'scientific_name')->get()),
            'recommended_dosages' =>RecommendedDosageResource::collection( RecommendedDosage::select('id', 'dosage')->get()),
        ]);
    }

    public function index(Request $request)
    {
        $validRelations = $this->extractValidRelations(Drug::class, $request);
        $drugs = Drug::with($validRelations)->get();

        return $this->success(DrugResource::collection($drugs));
    }

    public function show(Request $request, $id)
    {
        $validRelations = $this->extractValidRelations(Drug::class, $request);
        $drug = Drug::with($validRelations)->findOrFail($id);

        return $this->success(new DrugResource($drug));
    }

    public function store(Request $request, TranslationService $translationService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:drugs',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0|gt:cost',
            'cost' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
           // 'status' => 'required|in:active,expired',
            'is_requires_prescription' => 'boolean',
            'production_date' => 'required|date',
            'expiry_date' => [
                'required',
                'date',
                Rule::when($request->filled('production_date'), ['after:production_date'])
            ], 'admin_notes' => 'nullable|string',
            'form_id' => 'required|exists:forms,id',
            'manufacturer_id' => 'required|exists:manufacturers,id',
            'recommended_dosage_id' => 'required|exists:recommended_dosages,id',
            'active_ingredients' => 'required|array',
            'active_ingredients.*.id' => 'required|exists:active_ingredients,id',
            'active_ingredients.*.concentration' => 'required|numeric|min:0',
            'active_ingredients.*.unit' => 'required|in:mg,g,ml,mcg,IU',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
            'translations.*.name' => 'required|string|max:255',
        ]);
        $profitPercentage = 20;
        // $profitPercentage = config('app.profit_percentage');
        $profit_amount = isset($validated['cost']) && $validated['cost'] > 0
            ? $validated['price'] - $validated['cost']
            : ($validated['price'] * $profitPercentage) / 100;
        $sourceLocale = 'en';
        $drug = DB::transaction(function () use ($sourceLocale, $profit_amount, $validated, $translationService) {
            $drug = Drug::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'cost' => $validated['cost'],
                'profit_amount' => $profit_amount,
                'stock' => $validated['stock'],
                //'status' => $validated['status'],
                'is_requires_prescription' => $validated['is_requires_prescription'] ?? false,
                'production_date' => $validated['production_date'],
                'expiry_date' => $validated['expiry_date'],
                'admin_notes' => $validated['admin_notes'],
                'form_id' => $validated['form_id'],
                'manufacturer_id' => $validated['manufacturer_id'],
                'recommended_dosage_id' => $validated['recommended_dosage_id'],
            ]);

            $ingredientsData = [];
            foreach ($validated['active_ingredients'] as $ingredient) {
                $ingredientsData[$ingredient['id']] = [
                    'concentration' => $ingredient['concentration'],
                    'unit' => $ingredient['unit']
                ];
            }
            $drug->activeIngredients()->sync($ingredientsData);

            foreach ($validated['translations'] as $translation) {
                $locale = $translation['locale'];
                $drug->translations()->updateOrCreate(
                    ['locale' => $translation['locale'], 'field' => 'name'],
                    ['value' => $translation['name']]
                );


                if (isset($validated['description'])) {
                    $translatedValue = $locale === $sourceLocale
                        ? $validated['description']
                        : $translationService->translate($validated['description'], $sourceLocale, $locale);


                    $drug->translations()->updateOrCreate(
                        ['locale' => $translation['locale'], 'field' => 'description'],
                        ['value' => $translatedValue]
                    );
                }

                if (isset($validated['admin_notes'])) {
                    $translatedValue = $locale === $sourceLocale
                        ? $validated['admin_notes']
                        : $translationService->translate($validated['admin_notes'], $sourceLocale, $locale);


                    $drug->translations()->updateOrCreate(
                        ['locale' => $translation['locale'], 'field' => 'admin_notes'],
                        ['value' => $translatedValue]
                    );
                }
            }

            return $drug;
        });

        return $this->success(new DrugResource($drug));
    }

    public function update(Request $request, $id, TranslationService $translationService)
    {
        $drug = Drug::findOrFail($id);


        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('drugs')->ignore($drug->id)
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0|gt:cost',
            'cost' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
         //   'status' => 'required|in:active,expired',
            'is_requires_prescription' => 'boolean',
            'production_date' => 'nullable|date',
            'expiry_date' => [
                'required',
                'date',
                Rule::when($request->filled('production_date'), ['after:production_date'])
            ], 'admin_notes' => 'nullable|string',
            'form_id' => 'nullable|exists:forms,id',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'recommended_dosage_id' => 'nullable|exists:recommended_dosages,id',
            'active_ingredients' => 'sometimes|array',
            'active_ingredients.*.id' => 'required|exists:active_ingredients,id',
            'active_ingredients.*.concentration' => 'required|numeric|min:0',
            'active_ingredients.*.unit' => 'required|in:mg,g,ml,mcg,IU',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
            'translations.*.name' => 'required|string|max:255',
        ]);

        $profitPercentage = 20;
        // $profitPercentage = config('app.profit_percentage');
        $profit_amount = isset($validated['cost']) && $validated['cost'] > 0
            ? $validated['price'] - $validated['cost']
            : ($validated['price'] * $profitPercentage) / 100;
        $sourceLocale = 'en';
        $drug = DB::transaction(function () use ($sourceLocale, $profit_amount, $drug, $validated, $translationService) {
            $drug->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? $drug->description,
                'price' => $validated['price'],
                'cost' => $validated['cost'],
                'profit_amount' => $profit_amount,
                'stock' => $validated['stock'],
              //  'status' => $validated['status'],
                'is_requires_prescription' => $validated['is_requires_prescription'] ?? $drug->is_requires_prescription,
                'production_date' => $validated['production_date'] ?? $drug->production_date,
                'expiry_date' => $validated['expiry_date'],
                'admin_notes' => $validated['admin_notes'] ?? $drug->admin_notes,
                'form_id' => $validated['form_id'] ?? $drug->form_id,
                'manufacturer_id' => $validated['manufacturer_id'] ?? $drug->manufacturer_id,
                'recommended_dosage_id' => $validated['recommended_dosage_id'] ?? $drug->recommended_dosage_id,
            ]);

            if (isset($validated['active_ingredients'])) {
                $ingredientsData = [];
                foreach ($validated['active_ingredients'] as $ingredient) {
                    $ingredientsData[$ingredient['id']] = [
                        'concentration' => $ingredient['concentration'],
                        'unit' => $ingredient['unit']
                    ];
                }
                $drug->activeIngredients()->sync($ingredientsData);
            }

            if (isset($validated['translations'])) {

                foreach ($validated['translations'] as $translation) {
                    $locale = $translation['locale'];
                    $drug->translations()->updateOrCreate(
                        ['locale' => $translation['locale'], 'field' => 'name'],
                        ['value' => $translation['name']]
                    );

                    if (isset($validated['description'])) {
                        $translatedValue = $locale === $sourceLocale
                            ? $validated['description']
                            : $translationService->translate($validated['description'], $sourceLocale, $locale);


                        $drug->translations()->updateOrCreate(
                            ['locale' => $translation['locale'], 'field' => 'description'],
                            ['value' => $translatedValue]
                        );
                    }

                    if (isset($validated['admin_notes'])) {
                        $translatedValue = $locale === $sourceLocale
                            ? $validated['admin_notes']
                            : $translationService->translate($validated['admin_notes'], $sourceLocale, $locale);


                        $drug->translations()->updateOrCreate(
                            ['locale' => $translation['locale'], 'field' => 'admin_notes'],
                            ['value' => $translatedValue]
                        );
                    }
                }
            }

            return $drug;
        });

        return $this->success(new DrugResource($drug));
    }

    public function destroy($id)
    {
        $drug = Drug::findOrFail($id);

        DB::transaction(function () use ($drug) {
            $drug->translations()->delete();
            $drug->activeIngredients()->detach();
            $drug->forceDelete();
        });

        return $this->success([],'deleted successfully.');
    }

    public function filterOptions()
    {
        return response()->json([
            'status_options' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'expired', 'label' => 'Expired']
            ],
            'prescription_options' => [
                ['value' => true, 'label' => 'Requires Prescription'],
                ['value' => false, 'label' => 'Over the Counter']
            ]
        ]);
    }

}
