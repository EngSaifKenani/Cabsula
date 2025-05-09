<?php

namespace App\Http\Controllers;

use App\Http\Resources\FormResource;
use App\Models\Form;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormController extends Controller
{

    public function index(Request $request)
    {
        $validRelations = $this->extractValidRelations(Form::class, $request);
        $forms = Form::with($validRelations)->get();

        return $this->success(FormResource::collection($forms), 'تم جلب النماذج بنجاح');
    }

    public function show(Request $request, $id)
    {
        $validRelations = $this->extractValidRelations(Form::class, $request);
        $form = Form::with($validRelations)->findOrFail($id);

        return $this->success(new FormResource($form), 'تم جلب النموذج بنجاح');
    }


    public function store(Request $request, TranslationService $translationService)
    {
        // التحقق من صحة المدخلات
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:en,ar,fr',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('forms', 'public');
        }

        $form = Form::create([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath,
        ]);
        $sourceLocale = 'en';
        if ($request->has('translations')) {
            foreach ($request->translations as $translation) {
                $locale = $translation['locale'];


                $translatedName = $locale === $sourceLocale
                    ? $request->name
                    : $translationService->translate($request->name ?? '', $sourceLocale, $locale);

                $form->translations()->create([
                    'locale' => $translation['locale'],
                    'field' => 'name',
                    'value' => $translatedName ?? '',
                ]);

                $translatedDescription = $locale === $sourceLocale
                    ? $request->description
                    : $translationService->translate($request->description ?? '', $sourceLocale, $locale);

                $form->translations()->create([
                    'locale' => $translation['locale'],
                    'field' => 'description',
                    'value' => $translatedDescription ?? '',
                ]);
            }
        }

        return $this->success(new FormResource($form), 'Form created successfully');
    }

    public function update(Request $request, $id, TranslationService $translationService)
    {
        // التحقق من صحة البيانات
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $form = Form::findOrFail($id);

        if ($request->hasFile('image')) {
            if ($form->image && Storage::disk('public')->exists($form->image)) {
                Storage::disk('public')->delete($form->image);
            }
            $form->image = $request->file('image')->store('forms', 'public');
        }

        // تحديث البيانات الأساسية
        $form->name = $request->name;
        $form->description = $request->description;
        $form->save();


        $sourceLocale = 'en';

        $existingTranslations = $form->translations()->get();
        foreach ($existingTranslations as $translation) {
            $locale = $translation->locale;

            try {
                if ($request->has('name')) {
                    $translatedValue = $locale === $sourceLocale
                        ? $request->name
                        : $translationService->translate($request->name, $sourceLocale, $locale);

                    $form->translations()->updateOrCreate(
                        ['locale' => $locale, 'field' => 'name'],
                        ['value' => $translatedValue]
                    );
                }

                if ($request->has('description')) {
                    $translatedValue = $locale === $sourceLocale
                        ? $request->description
                        : $translationService->translate($request->description, $sourceLocale, $locale);

                    $form->translations()->updateOrCreate(
                        ['locale' => $locale, 'field' => 'description'],
                        ['value' => $translatedValue]
                    );
                }
            } catch (\Throwable $e) {
            }
        }

        return $this->success(new FormResource($form), 'تم تعديل النموذج بنجاح');
    }

    public function destroy($id)
    {
        $form = Form::findOrFail($id);
        if ($form->image && Storage::disk('public')->exists($form->image)) {
            Storage::disk('public')->delete($form->image);
        }

        $form->delete();

        return $this->success([], 'تم حذف النموذج بنجاح');
    }


}
