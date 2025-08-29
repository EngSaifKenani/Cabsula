<?php

namespace App\Jobs;

use App\Services\FirebaseService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    //php artisan queue:work
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $message;
    public string $type;
    public ?string $title;
    public array $userIds;
    public array $deviceTokens;

    /**
     * Create a new job instance.
     *
     * @param string $message الرسالة المراد إرسالها.
     * @param string $type نوع الإشعار (مثال: 'stock_depleted', 'new_invoice').
     * @param string|null $title عنوان الإشعار (خاص بـ Firebase).
     * @param array $userIds معرّفات المستخدمين المستهدفين.
     * @param array $deviceTokens توكنز أجهزة Firebase للمستخدمين.
     */
    public function __construct(string $message, string $type, ?string $title, array $userIds, array $deviceTokens)
    {
        $this->message = $message;
        $this->type = $type;
        $this->title = $title;
        $this->userIds = $userIds;
        $this->deviceTokens = $deviceTokens;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService, FirebaseService $firebaseService): void
    {
        // إرسال الإشعار عبر الخدمة المحلية
        $notificationService->createAndSendNotification($this->message, $this->type, $this->userIds);

        // إرسال إشعار Firebase
        if (!empty($this->deviceTokens) && $this->title) {
            $firebaseService->sendNotification($this->deviceTokens, $this->title, $this->message);
        }
    }
}
