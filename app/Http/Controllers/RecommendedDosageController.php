<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecommendedDosageResource;
use App\Models\RecommendedDosage;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class RecommendedDosageController extends Controller
{
    public function index(Request $request)
    {
        $validRelations = $this->extractValidRelations(RecommendedDosage::class, $request);
        $dosages = RecommendedDosage::with($validRelations)->get();
        return $this->success(RecommendedDosageResource::collection($dosages), 'تم جلب الجرعات الموصى بها بنجاح');
    }

    public function show(Request $request, $id)
    {
        $validRelations = $this->extractValidRelations(RecommendedDosage::class, $request);
        $dosage = RecommendedDosage::with($validRelations)->findOrFail($id);

        return $this->success(new RecommendedDosageResource($dosage), 'تم جلب الجرعة الموصى بها بنجاح');
    }

    public function store(Request $request, TranslationService $translationService)
    {
        $request->validate([
            'dosage' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
        ]);

        $dosage = RecommendedDosage::create([
            'dosage' => $request->dosage,
            'notes' => $request->notes,
        ]);

        $sourceLocale = 'en';

        foreach ($request->translations as $translation) {
                $locale = $translation['locale'];

            $translatedValue = $locale === $sourceLocale
                ? $request->dosage
                : $translationService->translate($request->dosage, $sourceLocale, $locale);

            $dosage->translations()->create([
                'locale' => $locale,
                'field' => 'dosage',
                'value' => $translatedValue,
            ]);

            if ($request->notes) {
                $translatedValue = $locale === $sourceLocale
                    ? $request->notes
                    : $translationService->translate($request->notes??'', $sourceLocale, $locale);

                $dosage->translations()->create([
                    'locale' => $locale,
                    'field' => 'notes',
                    'value' => $translatedValue,
                ]);
            }
        }

        return $this->success(new RecommendedDosageResource($dosage), 'تم إنشاء الجرعة الموصى بها بنجاح', 201);
    }

    public function update(Request $request, $id, TranslationService $translationService)
    {
        $request->validate([
            'dosage' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $dosage = RecommendedDosage::findOrFail($id);

        $dosage->update([
            'dosage' => $request->dosage,
            'notes' => $request->notes,
        ]);


        $sourceLocale = 'en';

        $existingTranslations = $dosage->translations()->get();
        foreach ($existingTranslations as $translation) {
            $locale = $translation->locale;

            try {
                if ($request->has('dosage')) {
                    $translatedValue = $locale === $sourceLocale
                        ? $request->dosage
                        : $translationService->translate($request->dosage, $sourceLocale, $locale);

                    $dosage->translations()->updateOrCreate(
                        ['locale' => $locale, 'field' => 'dosage'],
                        ['value' => $translatedValue]
                    );
                }

                if ($request->has('notes')) {
                    $translatedValue = $locale === $sourceLocale
                        ? $request->notes
                        : $translationService->translate($request->notes, $sourceLocale, $locale);

                    $dosage->translations()->updateOrCreate(
                        ['locale' => $locale, 'field' => 'notes'],
                        ['value' => $translatedValue]
                    );
                }
            } catch (\Throwable $e) {
            }
        }

        return $this->success(new RecommendedDosageResource($dosage), 'تم تحديث الجرعة الموصى بها بنجاح');
    }

    public function destroy($id)
    {
        $dosage = RecommendedDosage::findOrFail($id);
        $dosage->translations()->delete();
        if ($dosage->drugs()->exists()) {
            return $this->error('لا يمكن حذف هذا النموذج لوجود أدوية مرتبطة به.', 409);
        }
        $dosage->delete();

        return $this->success([], 'تم حذف الجرعة الموصى بها بنجاح');
    }
}
