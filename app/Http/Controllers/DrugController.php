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
use Illuminate\Support\Facades\Storage;
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
        $request->validate([
            'name' => 'required|string|max:255|unique:drugs',
            'description' => 'nullable|string',
            'price' => ['required',
                'numeric',
                'min:0',
                Rule::when($request->filled('cost'), ['gt:cost'])
            ],            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
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

        if ($request->hasFile('image')) {
            $request['image'] = $request->file('image')->store('drugs', 'public');
        }

        $profitPercentage = 20;
        // $profitPercentage = config('app.profit_percentage');
        $profit_amount = isset($request['cost']) && $request['cost'] > 0
            ? $request['price'] - $request['cost']
            : ($request['price'] * $profitPercentage) / 100;
        $sourceLocale = 'en';
        $drug = DB::transaction(function () use ($sourceLocale, $profit_amount, $request, $translationService) {
            $drug = Drug::create([
                'name' => $request['name'],
                'description' => $request->description,
                'price' => $request['price'],
                'image' => $request['image'],
                'cost' => $request['cost'],
                'profit_amount' => $profit_amount,
                'stock' => $request['stock'],
                //'status' => $request['status'],
                'is_requires_prescription' => $request['is_requires_prescription'] ?? false,
                'production_date' => $request['production_date'],
                'expiry_date' => $request['expiry_date'],
                'admin_notes' => $request['admin_notes'],
                'form_id' => $request['form_id'],
                'manufacturer_id' => $request['manufacturer_id'],
                'recommended_dosage_id' => $request['recommended_dosage_id'],
            ]);

            $ingredientsData = [];
            foreach ($request['active_ingredients'] as $ingredient) {
                $ingredientsData[$ingredient['id']] = [
                    'concentration' => $ingredient['concentration'],
                    'unit' => $ingredient['unit']
                ];
            }
            $drug->activeIngredients()->sync($ingredientsData);

            foreach ($request['translations'] as $translation) {
                $locale = $translation['locale'];
                $drug->translations()->updateOrCreate(
                    ['locale' => $translation['locale'], 'field' => 'name'],
                    ['value' => $translation['name']]
                );


                if (isset($request['description'])) {
                    $translatedValue = $locale === $sourceLocale
                        ? $request['description']
                        : $translationService->translate($request['description'], $sourceLocale, $locale);


                    $drug->translations()->updateOrCreate(
                        ['locale' => $translation['locale'], 'field' => 'description'],
                        ['value' => $translatedValue]
                    );
                }

                if (isset($request['admin_notes'])) {
                    $translatedValue = $locale === $sourceLocale
                        ? $request['admin_notes']
                        : $translationService->translate($request['admin_notes'], $sourceLocale, $locale);


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


         $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('drugs')->ignore($drug->id)
            ],
            'description' => 'nullable|string',
            'price' => ['required',
                'numeric',
                'min:0',
                Rule::when($request->filled('cost'), ['gt:cost'])
            ],
            'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
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

        if ($request->hasFile('image')) {
            if ($drug->image) {
                Storage::disk('public')->delete($drug->image);
            }
            $request['image'] = $request->file('image')->store('drugs', 'public');
        }


        $profitPercentage = 20;
        // $profitPercentage = config('app.profit_percentage');
        $profit_amount = isset($request['cost']) && $request['cost'] > 0
            ? $request['price'] - $request['cost']
            : ($request['price'] * $profitPercentage) / 100;
        $sourceLocale = 'en';
        $drug = DB::transaction(function () use ($sourceLocale, $profit_amount, $drug, $request, $translationService) {
            $drug->update([
                'name' => $request['name'],
                'description' => $request['description'] ?? $drug->description,
                'price' => $request['price'],
                'image' => $request['image'] ??$drug->image,
                'cost' => $request['cost'],
                'profit_amount' => $profit_amount,
                'stock' => $request['stock'],
              //  'status' => $request['status'],
                'is_requires_prescription' => $request['is_requires_prescription'] ?? $drug->is_requires_prescription,
                'production_date' => $request['production_date'] ?? $drug->production_date,
                'expiry_date' => $request['expiry_date'],
                'admin_notes' => $request['admin_notes'] ?? $drug->admin_notes,
                'form_id' => $request['form_id'] ?? $drug->form_id,
                'manufacturer_id' => $request['manufacturer_id'] ?? $drug->manufacturer_id,
                'recommended_dosage_id' => $request['recommended_dosage_id'] ?? $drug->recommended_dosage_id,
                'translations' => 'required|array',
                'translations.*.locale' => 'required|string|in:en,ar,fr',
                'translations.*.name' => 'required|string|max:255',
            ]);

            if (isset($request['active_ingredients'])) {
                $ingredientsData = [];
                foreach ($request['active_ingredients'] as $ingredient) {
                    $ingredientsData[$ingredient['id']] = [
                        'concentration' => $ingredient['concentration'],
                        'unit' => $ingredient['unit']
                    ];
                }
                $drug->activeIngredients()->sync($ingredientsData);
            }

            if (isset($request['translations'])) {

                foreach ($request['translations'] as $translation) {
                    $locale = $translation['locale'];
                    $drug->translations()->updateOrCreate(
                        ['locale' => $translation['locale'], 'field' => 'name'],
                        ['value' => $translation['name']]
                    );

                    if (isset($request['description'])) {
                        $translatedValue = $locale === $sourceLocale
                            ? $request['description']
                            : $translationService->translate($request['description'], $sourceLocale, $locale);


                        $drug->translations()->updateOrCreate(
                            ['locale' => $translation['locale'], 'field' => 'description'],
                            ['value' => $translatedValue]
                        );
                    }

                    if (isset($request['admin_notes'])) {
                        $translatedValue = $locale === $sourceLocale
                            ? $request['admin_notes']
                            : $translationService->translate($request['admin_notes'], $sourceLocale, $locale);


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

    public function getAlternativeDrug($id)
    {
        $drug = Drug::select('id', 'name', 'image', 'price')
            ->with(['activeIngredients' => function($query) {
                $query->select(
                    'active_ingredients.id',
                    'active_ingredients.scientific_name',
                    'drug_ingredients.concentration',
                    'drug_ingredients.unit'
                );
            }])
            ->findOrFail($id);

        $activeIngredientIds = $drug->activeIngredients->pluck('id');

        $alternativeDrugs = Drug::where('id', '!=', $id)
            ->whereHas('activeIngredients', function($query) use ($activeIngredientIds) {
                $query->whereIn('active_ingredients.id', $activeIngredientIds);
            }, '=', count($activeIngredientIds)) // الشرط: يحتوي على نفس عدد المواد
            ->whereDoesntHave('activeIngredients', function($query) use ($activeIngredientIds) {
                $query->whereNotIn('active_ingredients.id', $activeIngredientIds);
            }) // الشرط: لا يحتوي على مواد إضافية
            ->select('id', 'name', 'image', 'price')
            ->with(['activeIngredients' => function($query) {
                $query->select(
                    'active_ingredients.id',
                    'active_ingredients.scientific_name',
                    'drug_ingredients.concentration',
                    'drug_ingredients.unit'
                );
            }])
            ->get();


        return response()->json([
            'original_drug' => $drug,
            'alternative_drugs' => $alternativeDrugs
        ]);
    }

}
