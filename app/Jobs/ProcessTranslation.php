<?php

namespace App\Jobs;

use App\Services\TranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTranslation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 2;
    public $timeout = 120;
    public $backoff = [30, 60, 120];

    public function __construct(
        public string $text,
        public string $source,
        public string $target,
        public $callback = null
    ) {}

    public function handle(TranslationService $translationService)
    {
        try {
            // استخدم خدمة الترجمة مباشرة
            $translatedText = $translationService->translate(
                $this->text,
                $this->source,
                $this->target,
                false // Force synchronous translation
            );

            if ($this->callback && is_callable($this->callback)) {
                call_user_func($this->callback, $translatedText);
            }

            return $translatedText;
        } catch (\Exception $e) {
            Log::error('Translation job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Translation job failed after all attempts', [
            'text' => $this->text,
            'source' => $this->source,
            'target' => $this->target,
            'error' => $exception->getMessage()
        ]);

        if ($this->callback && is_callable($this->callback)) {
            try {
                call_user_func($this->callback, null, $exception);
            } catch (\Exception $e) {
                Log::error('Failed to execute error callback: ' . $e->getMessage());
            }
        }
    }
}
