<?php

namespace App\Http\Controllers;

use App\Http\Resources\DrugCollection;
use App\Http\Resources\DrugResource;
use App\Http\Resources\TherapeuticUseResource;
use App\Models\Drug;
use App\Models\TherapeuticUse;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TherapeuticUseController extends Controller
{
    public function index(Request $request)
    {

        $validRelations = $this->extractValidRelations(TherapeuticUse::class, $request);

        $therapeutic_uses = TherapeuticUse::with($validRelations)->select('id','name','image')->get();

        return $this->success(TherapeuticUseResource::collection($therapeutic_uses), 'تم جلب التصنيفات بنجاح');
    }

    public function show(Request $request, $id)
    {
        $validRelations = $this->extractValidRelations(TherapeuticUse::class, $request);
        $TherapeuticUse = TherapeuticUse::with($validRelations)->with('activeIngredients.drugs')->findOrFail($id);

        $drugs = Drug::whereHas('activeIngredients.therapeuticUses', function($query) use ($TherapeuticUse) {
            $query->where('therapeutic_uses.id', $TherapeuticUse->id);
        })
            ->select('id', 'name', 'description', 'image', 'is_requires_prescription')
            ->with('validBatch')
            ->with(['activeIngredients' => function($query) {
                $query->select('active_ingredients.id', 'scientific_name' );
            }])->distinct()
            ->paginate(10);
        return $this->success(new DrugCollection($drugs), 'تم جلب التصنيف بنجاح');
    }


    public function store(Request $request, TranslationService $translationService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('therapeutic_uses', 'public');
        }

        $TherapeuticUse = TherapeuticUse::create([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath,
        ]);
        $sourceLocale = 'en';
        foreach ($request->translations as $translation) {

            $locale = $translation['locale'];

            try {
                $translatedValue = $locale === $sourceLocale
                    ? $request->name
                    : $translationService->translate($request->name, $sourceLocale, $locale);

                $TherapeuticUse->translations()->create([
                    'locale' => $translation['locale'],
                    'field' => 'name',
                    'value' => $translatedValue,
                ]);

                if ($request->has('description')) {
                    $translatedValue = $locale === $sourceLocale
                        ? $request->description
                        : $translationService->translate($request->description, $sourceLocale, $locale);
                    $TherapeuticUse->translations()->create([
                        'locale' => $translation['locale'],
                        'field' => 'description',
                        'value' => $translatedValue,
                    ]);
                }
            } catch (\Exception $exception) {
            }
        }


        return $this->success(new TherapeuticUseResource($TherapeuticUse), 'تم إنشاء التصنيف بنجاح', 201);
    }

    public function update(Request $request, $id, TranslationService $translationService)
    {
        $TherapeuticUse = TherapeuticUse::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'translations' => 'nullable|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
        ]);

        if ($request->hasFile('image')) {
            if ($TherapeuticUse->image) {
                Storage::disk('public')->delete($TherapeuticUse->image);
            }
            $imagePath = $request->file('image')->store('therapeutic_uses', 'public');
        } else {
            $imagePath = $TherapeuticUse->image;
        }

        $TherapeuticUse->update([
            'name' => $request->name,
            'description' => $request->description ?? $TherapeuticUse->description,
            'image' => $imagePath ?? $TherapeuticUse->image,
        ]);
        $sourceLocale = 'en';

        $existingTranslations = $TherapeuticUse->translations()->get();
        foreach ($existingTranslations as $translation) {
            $locale = $translation->locale;

            try {
                if ($request->has('name')) {
                    $translatedValue = $locale === $sourceLocale
                        ? $request->name
                        : $translationService->translate($request->name, $sourceLocale, $locale);

                    $TherapeuticUse->translations()->updateOrCreate(
                        ['locale' => $locale, 'field' => 'name'],
                        ['value' => $translatedValue]
                    );
                }

                if ($request->has('description')) {
                    $translatedValue = $locale === $sourceLocale
                        ? $request->description
                        : $translationService->translate($request->description, $sourceLocale, $locale);

                    $TherapeuticUse->translations()->updateOrCreate(
                        ['locale' => $locale, 'field' => 'description'],
                        ['value' => $translatedValue]
                    );
                }
            } catch (\Throwable $e) {
            }
        }

        $TherapeuticUse = TherapeuticUse::findOrFail($id);
        return $this->success(new TherapeuticUseResource($TherapeuticUse), 'تم تحديث التصنيف بنجاح');
    }

    public function destroy($id)
    {
        $TherapeuticUse = TherapeuticUse::findOrFail($id);

        if ($TherapeuticUse->image) {
            Storage::disk('public')->delete($TherapeuticUse->image);
        }
        $TherapeuticUse->translations()->delete();
        $TherapeuticUse->activeIngredients()->detach();
        $TherapeuticUse->delete();

        return $this->success([], 'تم حذف التصنيف بنجاح');
    }
}
