<?php

namespace App\Http\Controllers;

use App\Http\Resources\SideEffectCategoryResource;
use App\Models\SideEffectCategory;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SideEffectCategoryController extends Controller
{



    public function index(Request $request)
    {

        $validRelations = $this->extractValidRelations(SideEffectCategory::class, $request);
        $categories = SideEffectCategory::with($validRelations)->get();

        return $this->success(SideEffectCategoryResource::collection($categories), 'تم جلب التصنيفات بنجاح');
    }

    public function show(Request $request, $id)
    {
        $validRelations = $this->extractValidRelations(SideEffectCategory::class, $request);
        $category = SideEffectCategory::with($validRelations)->findOrFail($id);
        return $this->success(new SideEffectCategoryResource($category), 'تم جلب التصنيف بنجاح');
    }


    public function store(Request $request, TranslationService $translationService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
        ]);

        $category = SideEffectCategory::create([
            'name' => $request->name,
        ]);

        $sourceLocale = 'en';

        foreach ($request->translations as $translation) {
            $locale = $translation['locale'];

            $translatedValue = $locale === $sourceLocale
                ? $request->name
                : $translationService->translate($request->name, $sourceLocale, $locale);

            $category->translations()->create([
                'locale' => $locale,
                'field' => 'name',
                'value' => $translatedValue,
            ]);
        }

        return $this->success(new SideEffectCategoryResource($category), 'تم إنشاء التصنيف بنجاح', 201);
    }


    public function update(Request $request, $id, TranslationService $translationService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = SideEffectCategory::findOrFail($id);

        $category->update([
            'name' => $request->name,
        ]);

        $sourceLocale = 'en';

        $existingTranslations = $category->translations()->where('field', 'name')->get();

        foreach ($existingTranslations as $translation) {
            $locale = $translation->locale;

            try {
                $translatedValue = $locale === $sourceLocale
                    ? $request->name
                    : $translationService->translate($request->name, $sourceLocale, $locale);

                $category->translations()->updateOrCreate(
                    ['locale' => $locale, 'field' => 'name'],
                    ['value' => $translatedValue]
                );
            } catch (\Exception $e) {
            }
        }

        return $this->success(new SideEffectCategoryResource($category), 'تم تحديث التصنيف بنجاح');
    }


    public function destroy($id)
    {
        $category = SideEffectCategory::findOrFail($id);
        $category->translations()->delete();
        $category->delete();
        return $this->success([], 'تم حذف التصنيف بنجاح');
    }
}
