<?php
namespace App\Services;
use App\Jobs\TranslateText;
use Illuminate\Support\Facades\Http;

class TranslationService
{
    public function __construct()
    {
    }

    public function translate(string $text, string $source = 'en', string $target = 'ar', bool $async = false): string
    {
        if ($async) {
            // أرسل الترجمة إلى الطابور وخذ النتيجة لاحقًا إن أردت
            TranslateText::dispatch($text, $source, $target);
            return $text; // ترجمة مؤقتة إلى أن يتم معالجتها
        }

        try {
            $translated = $this->translateLocal($text, $source, $target);
            if (!empty(trim($translated))) {
                return $translated;
            }
        } catch (\Exception $e) {
            try {
                $translated = $this->translateWithMyMemory($text, $source, $target);
                if (!empty(trim($translated))) {
                    return $translated;
                }
            } catch (\Exception $e) {
            }
        }



        return $text;
    }

    private function translateWithMyMemory(string $text, string $source = 'en', string $target = 'ar')
    {
        $response = Http::timeout(10)
            ->retry(2, 100)
            ->get('https://api.mymemory.translated.net/get', [
                'q' => $text,
                'langpair' => $source.'|'.$target,
            ]);

        if ($response->failed()) {
            throw new \Exception("فشل الاتصال بخدمة الترجمة", 503);
        }

        $data = $response->json();

        if (!isset($data['responseData']['translatedText'])) {
            throw new \Exception("استجابة غير صالحة من خدمة الترجمة", 502);
        }

        $translatedText = trim($data['responseData']['translatedText']);

        if (empty($translatedText)) {
            throw new \Exception("النص المترجم فارغ", 422);
        }

        if ($this->isLowQualityTranslation($text, $translatedText)) {
            throw new \Exception("جودة الترجمة منخفضة", 406);
        }

        return $translatedText;
    }

    private function isLowQualityTranslation(string $original, string $translated): bool
    {
        return $original === $translated || strlen($translated) < (strlen($original) / 2);
    }

    private function translateLocal(string $text, string $source = 'en', string $target = 'ar'): string
    {
        $response = Http::timeout(15)
            ->retry(3, 500)
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

        if ($response->failed()) {
            throw new \RuntimeException("فشل الاتصال بخادم الترجمة المحلي: " . $response->status(), 503);
        }

        $data = $response->json();

        if (!isset($data['translatedText'])) {
            throw new \RuntimeException("استجابة غير صالحة من خادم الترجمة: " . json_encode($data), 502);
        }

        $translatedText = trim($data['translatedText']);

        if (empty($translatedText)) {
            throw new \RuntimeException("خادم الترجمة أعاد نصًا فارغًا", 422);
        }

        if ($this->isIdenticalTranslation($text, $translatedText)) {
            throw new \RuntimeException("النص المترجم مطابق للنص الأصلي", 406);
        }

        return $translatedText;
    }

    private function isIdenticalTranslation(string $original, string $translated): bool
    {
        return mb_strtolower(trim($original)) === mb_strtolower(trim($translated));
    }
}
