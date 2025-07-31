<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActiveIngredientResource;
use App\Http\Resources\DrugCollection;
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
        /*   $perPage = 10;
           $currentPage = $request->query('page', 1);
           $offset = ($currentPage - 1) * $perPage;
           $total = Drug::count();
           $totalPages = ceil($total / $perPage);*/

        /*            $validRelations = $this->extractValidRelations(Drug::class, $request);*/
        $drugs = Drug::/*skip($offset)->take($perPage)->*//*with($validRelations)->*/
        with('validBatches')
            ->select('id','name','description','image','is_requires_prescription')
            /*  ->with(['activeIngredients' => function($query) {
              $query->select('active_ingredients.id', 'scientific_name');
          }])*/
            ->paginate(10);

        return $this->success(new DrugCollection($drugs));
    }

    public function show(Request $request, $id)
    {
        $validRelations = $this->extractValidRelations(Drug::class, $request);
        $drug= Drug::with(array_merge($validRelations, [
            'activeIngredients:id,scientific_name',
            'validBatches'
        ]))->findOrFail($id);
        return $this->success(new DrugResource($drug));
    }

    public function store(Request $request, TranslationService $translationService)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:drugs',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'is_requires_prescription' => 'boolean',
            'admin_notes' => 'nullable|string',
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
        $imagePath=null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('drugs', 'public');
        }
        $sourceLocale = 'en';
        $drug = DB::transaction(function () use ($imagePath, $sourceLocale, $request, $translationService) {
            $drug = Drug::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $imagePath,
                'is_requires_prescription' => $request['is_requires_prescription'] ?? false,
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
                'sometimes',
                'string',
                'max:255',
                Rule::unique('drugs')->ignore($drug->id)
            ],
            'description' => 'nullable|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'is_requires_prescription' => 'boolean',
            'admin_notes' => 'sometimes|string',
            'form_id' => 'sometimes|exists:forms,id',
            'manufacturer_id' => 'sometimes|exists:manufacturers,id',
            'recommended_dosage_id' => 'sometimes|exists:recommended_dosages,id',
            'active_ingredients' => 'sometimes|array',
            'active_ingredients.*.id' => 'required|exists:active_ingredients,id',
            'active_ingredients.*.concentration' => 'required|numeric|min:0',
            'active_ingredients.*.unit' => 'required|in:mg,g,ml,mcg,IU',
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
            'translations.*.name' => 'required|string|max:255',
        ]);




        $imagePath=null;
        if ($request->hasFile('image')) {
            if ($drug->image) {
                Storage::disk('public')->delete($drug->image);
            }
            $imagePath = $request->file('image')->store('drugs', 'public');
        }

        $sourceLocale = 'en';
        $drug = DB::transaction(function () use ( $imagePath,$sourceLocale, $drug, $request, $translationService) {

            $drug->update([
                'name' => $request['name']?? $drug->name,
                'description' => $request['description'] ?? $drug->description,
                'image' => $imagePath ??$drug->image,
                'is_requires_prescription' => $request['is_requires_prescription'] ?? $drug->is_requires_prescription,
                'admin_notes' => $request['admin_notes'] ?? $drug->admin_notes,
                'form_id' => $request['form_id'] ?? $drug->form_id,
                'manufacturer_id' => $request['manufacturer_id'] ?? $drug->manufacturer_id,
                'recommended_dosage_id' => $request['recommended_dosage_id'] ?? $drug->recommended_dosage_id,
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

    public function getAlternativeDrugById($id)
    {
        $drug = Drug::select('id', 'name', 'image','description','is_requires_prescription')
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
            }, '=', count($activeIngredientIds))
            ->whereDoesntHave('activeIngredients', function($query) use ($activeIngredientIds) {
                $query->whereNotIn('active_ingredients.id', $activeIngredientIds);
            })
            ->with('validBatches')
            ->select('id', 'name', 'image','description','is_requires_prescription')
            ->with(['activeIngredients' => function($query) {
                $query->select(
                    'active_ingredients.id',
                    'active_ingredients.scientific_name',
                    'drug_ingredients.concentration',
                    'drug_ingredients.unit'
                );
            }])
            ->paginate(10);


        /*return $this->success(['original_drug' => new DrugResource($drug),
             'alternative_drugs_count' => $alternativeDrugs->count(),
             'alternative_drugs' => new DrugCollection($alternativeDrugs)]
         );*/
        return $this->success(new DrugCollection($alternativeDrugs));

    }


    public function getAlternativeDrugByActiveIngredients(Request $request)
    {
        $validated = $request->validate([
            'active_ingredients' => 'required|array',
            'active_ingredients.*' => 'integer|exists:active_ingredients,id'
        ]);

        $ingredientIds = $validated['active_ingredients'];
        $count = count($ingredientIds);

        $alternativeDrugs = Drug::select('id', 'name', 'image','description','is_requires_prescription')
            ->with(['activeIngredients' => function($query) {
                $query->select(
                    'active_ingredients.id',
                    'active_ingredients.scientific_name',
                    'drug_ingredients.concentration',
                    'drug_ingredients.unit'
                );
            }])
            ->with('validBatches')
            ->whereHas('activeIngredients', function($query) use ($ingredientIds) {
                $query->whereIn('active_ingredients.id', $ingredientIds);
            }, '=', $count)
            ->whereDoesntHave('activeIngredients', function($query) use ($ingredientIds) {
                $query->whereNotIn('active_ingredients.id', $ingredientIds);
            })
            ->paginate(10);

        return $this->success(new DrugCollection($alternativeDrugs));

    }

}
