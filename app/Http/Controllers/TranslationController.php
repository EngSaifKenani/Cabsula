<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    /**
     * إضافة الترجمة
     */
    public function store(Request $request)
    {
        $request->validate([
            'translatable_id' => 'required|integer',
            'translatable_type' => 'required|string',
            'locale' => 'required|string',
            'field' => 'required|string',
            'value' => 'required|string',
        ]);

        $translation = Translation::create([
            'translatable_id' => $request->translatable_id,
            'translatable_type' => $request->translatable_type,
            'locale' => $request['locale'],
            'field' => $request->field,
            'value' => $request->value,
        ]);

        return response()->json([
            'message' => 'Translation added successfully!',
            'translation' => $translation
        ], 201);
    }

    /**
     * الحصول على الترجمات
     */
    public function show($id, $locale, $field)
    {
        $translation = Translation::where('translatable_id', $id)
            ->where('locale', $locale)
            ->where('field', $field)
            ->first();

        if ($translation) {
            return response()->json([
                'value' => $translation->value
            ]);
        } else {
            return response()->json([
                'message' => 'Translation not found'
            ], 404);
        }
    }

    /**
     * تحديث الترجمة
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'locale' => 'required|string',
            'field' => 'required|string',
            'value' => 'required|string',
        ]);

        $translation = Translation::findOrFail($id);

        $translation->update([
            'locale' => $request['locale'],
            'field' => $request->field,
            'value' => $request->value,
        ]);

        return response()->json([
            'message' => 'Translation updated successfully!',
            'translation' => $translation
        ]);
    }

    /**
     * حذف الترجمة
     */
    public function destroy($id)
    {
        $translation = Translation::findOrFail($id);
        $translation->delete();

        return response()->json(['message' => 'Translation deleted successfully']);
    }
}
