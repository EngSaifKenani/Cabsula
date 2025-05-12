<?php

namespace App\Jobs;

use App\Services\TranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranslateText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $text;
    public $source;
    public $target;
    public $callback;

    public function __construct(string $text, string $source = 'en', string $target = 'ar', callable $callback = null)
    {
        $this->text = $text;
        $this->source = $source;
        $this->target = $target;
        $this->callback = $callback;
    }

    public function handle(TranslationService $translator)
    {
        $translated = $translator->translate($this->text, $this->source, $this->target, false);

        // تنفيذ callback اختياري إذا تم تمريره
        if (is_callable($this->callback)) {
            call_user_func($this->callback, $translated);
        }
    }
}
