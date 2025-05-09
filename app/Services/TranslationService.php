<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;

class TranslationService
{
    public function __construct()
    {
    }
    public function translate(string $text, string $source = 'en', string $target = 'ar'): string
    {
        // المحاولة الأولى: الترجمة المحلية
        try {
            $translated = $this->translateLocal($text, $source, $target);
            if (!empty(trim($translated))) {
                return $translated;
            }
        } catch (\Exception $e) {
        }

        try {
            $translated = $this->translateWithMyMemory($text, $source, $target);
            if (!empty(trim($translated))) {
                return $translated;
            }
        } catch (\Exception $e) {
        }

        return $text;
    }

    private function translateWithMyMemory(string $text, string $source = 'en', string $target = 'ar')
    {
        $response = Http::timeout(10) // حد زمني 10 ثواني
        ->retry(2, 100) // إعادة المحاولة مرتين بفاصل 100 مللي ثانية
        ->get('https://api.mymemory.translated.net/get', [
            'q' => $text,
            'langpair' => $source.'|'.$target,
        ]);

        // فشل كلي في الاتصال بالخادم
        if ($response->failed()) {
            throw new \Exception("فشل الاتصال بخدمة الترجمة", 503); // 503 Service Unavailable
        }

        $data = $response->json();

        // خطأ في هيكل البيانات
        if (!isset($data['responseData']['translatedText'])) {
            throw new \Exception("استجابة غير صالحة من خدمة الترجمة", 502); // 502 Bad Gateway
        }

        $translatedText = trim($data['responseData']['translatedText']);

        // نص مترجم فارغ
        if (empty($translatedText)) {
            throw new \Exception("النص المترجم فارغ", 422); // 422 Unprocessable Entity
        }

        // تحقق من جودة الترجمة (اختياري)
        if ($this->isLowQualityTranslation($text, $translatedText)) {
            throw new \Exception("جودة الترجمة منخفضة", 406); // 406 Not Acceptable
        }

        return $translatedText;
    }

// دالة مساعدة للتحقق من جودة الترجمة (مثال)
    private function isLowQualityTranslation(string $original, string $translated): bool
    {
        return $original === $translated || strlen($translated) < (strlen($original) / 2);
    }

    private function translateLocal(string $text, string $source = 'en', string $target = 'ar'): string
    {
        $response = Http::timeout(15) // زيادة المهلة لخدمة محلية
        ->retry(3, 500) // 3 محاولات بفاصل 500 مللي ثانية
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->post("http://localhost:5000/translate", [
                'q' => $text,
                'source' => $source,
                'target' => $target,
                'format' => 'text',
            ]);

        // حالة فشل الاتصال بالخادم
        if ($response->failed()) {
            throw new \RuntimeException(
                "فشل الاتصال بخادم الترجمة المحلي: " . $response->status(),
                503 // Service Unavailable
            );
        }

        $data = $response->json();

        // التحقق من هيكل JSON
        if (!isset($data['translatedText'])) {
            throw new \RuntimeException(
                "استجابة غير صالحة من خادم الترجمة: " . json_encode($data),
                502 // Bad Gateway
            );
        }

        $translatedText = trim($data['translatedText']);

        // التحقق من وجود نص مترجم
        if (empty($translatedText)) {
            throw new \RuntimeException(
                "خادم الترجمة أعاد نصًا فارغًا",
                422 // Unprocessable Entity
            );
        }

        // تحقق إضافي من جودة الترجمة
        if ($this->isIdenticalTranslation($text, $translatedText)) {
            throw new \RuntimeException(
                "النص المترجم مطابق للنص الأصلي",
                406 // Not Acceptable
            );
        }

        return $translatedText;
    }

// دالة مساعدة للتحقق من الترجمة المطابقة
    private function isIdenticalTranslation(string $original, string $translated): bool
    {
        $normalizedOriginal = mb_strtolower(trim($original));
        $normalizedTranslated = mb_strtolower(trim($translated));

        return $normalizedOriginal === $normalizedTranslated;
    }


}
