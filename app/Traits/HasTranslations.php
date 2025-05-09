<?php

namespace App\Traits;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;

trait HasTranslations

{
    // تعريف العلاقة مع الترجمة
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }
    public function getTranslatableFields()
    {
        // الوصول إلى الحقول القابلة للترجمة
        return $this->translatable;
    }
    // تغيير getAttribute ليشمل الترجمة التلقائية
    public function getAttribute($key)
    {
        if (!in_array($key, $this->translatable ?? [])) {
            return parent::getAttribute($key);
        }
        $locale = $this->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');

        $translation = $this->translations
            ->where('field', $key)
            ->where('locale', $locale)
            ->first();

        if (!$translation && $locale !== $fallbackLocale) {
            $translation = $this->translations
                ->where('field', $key)
                ->where('locale', $fallbackLocale)
                ->first();
        }

        if ($translation) {
            return $translation->value;
        }

        return parent::getAttribute($key);
    }

    // دالة للحصول على اللغة الحالية
    public function getLocale()
    {
        return app()->getLocale(); // إرجاع اللغة الحالية
    }

    // دالة لتنسيق البيانات مع الترجمات
    public function formatWithTranslations($locale = null)
    {
        $locale = $locale ?? $this->getLocale(); // استخدام الدالة للحصول على اللغة الحالية

        // جلب الترجمات حسب اللغة
        $translations = $this->translations->keyBy('field');

        // إعداد البيانات مع الترجمات
        $data = [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'image' => $this->image,
        ];

        // إضافة الحقول القابلة للترجمة (مثل name, description)
        foreach ($this->translatable as $field) {
            $data[$field] = $translations[$field]->value ?? $this->{$field};
        }

        return $data;
    }

    // استخدام eager loading للترجمات بناءً على اللغة
    public function scopeWithTranslations(Builder $query, $locale = null)
    {
        $locale = $locale ?? $this->getLocale(); // استخدام اللغة الحالية إذا لم يتم تمريرها

        return $query->with(['translations' => function ($query) use ($locale) {
            $query->where('locale', $locale);
        }]);
    }

    // لتضمين العلاقات المترجمة
    public function formatWithRelationsAndTranslations( )
    {
        $locale = $this->getLocale();

        // جلب الترجمات الخاصة بالـ Drug
        $translations = $this->translations->where('locale', $locale)->keyBy('field');

        // الإعدادات الخاصة بالـ Drug
        $data = [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'image' => $this->image,
            'trade_name' => $this->trade_name,
            'requires_prescription' => $this->requires_prescription,
        ];

        // إضافة الحقول المترجمة (مثل name, description) بناءً على اللغة
        $data['name'] = isset($translations['name']) ? $translations['name']->value : $this->name;
        $data['description'] = isset($translations['description']) ? $translations['description']->value : $this->description;

        // إضافة العلاقة Category
        if ($this->category) {
            // ترجمة الحقول المناسبة للـ Category
            $categoryTranslations = $this->category->translations->where('locale', $locale)->keyBy('field');
            $data['category'] = [
                'id' => $this->category->id,
                'name' => isset($categoryTranslations['name']) ? $categoryTranslations['name']->value : $this->category->name,
                'description' => isset($categoryTranslations['description']) ? $categoryTranslations['description']->value : $this->category->description,
            ];
        }

        return $data;
    }

}
