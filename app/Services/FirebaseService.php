<?php

namespace App\Services;

use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $serviceAccountPath = storage_path('app/firebase/service-account.json');

        if (!file_exists($serviceAccountPath)) {
            throw new \RuntimeException('Firebase service account file not found');
        }

        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $this->messaging = $factory->createMessaging();
    }

    /**
     * إرسال إشعار إلى جهاز واحد أو مجموعة من الأجهزة.
     *
     * @param string|array $deviceTokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendNotification($deviceTokens, string $title, string $body, array $data = [])
    {
        $notification = Notification::create($title, $body);
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data);

        if (is_array($deviceTokens)) {
            return $this->sendMulticastMessage($message, $deviceTokens);
        }

        return $this->sendSingleMessage($message, $deviceTokens);
    }

    protected function sendMulticastMessage(CloudMessage $message, array $tokens): array
    {
        $allResponses = [];
        $totalSuccesses = 0;
        $totalFailures = 0;

        try {
            foreach (array_chunk($tokens, 500) as $chunk) {
                $report = $this->messaging->sendMulticast($message, $chunk);

                $successes = $report->successes()->count();
                $failures = $report->failures()->count();

                $totalSuccesses += $successes;
                $totalFailures += $failures;

                foreach ($report->successes() as $target => $messageId) {
                    $allResponses[] = [
                        'token' => $target,
                        'success' => true,
                        'messageId' => $messageId,
                    ];
                }

                foreach ($report->failures() as $target => $error) {
                    $allResponses[] = [
                        'token' => $target,
                        'success' => false,
                        'error' => $error->getMessage(),
                    ];
                }
            }

            return [
                'type' => 'multicast',
                'total' => count($tokens),
                'successes' => $totalSuccesses,
                'failures' => $totalFailures,
                'responses' => $allResponses,
            ];
        } catch (MessagingException|FirebaseException $e) {
            Log::error('Firebase multicast error: ' . $e->getMessage());
            return [
                'type' => 'error',
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    protected function sendSingleMessage(CloudMessage $message, string $token): array
    {
        try {
            $messageId = $this->messaging->send($message->withChangedTarget('token', $token));

            return [
                'type' => 'single',
                'success' => true,
                'messageId' => $messageId,
                'token' => $token,
            ];
        } catch (MessagingException|FirebaseException $e) {
            Log::error('Firebase single message error: ' . $e->getMessage());
            return [
                'type' => 'error',
                'success' => false,
                'error' => $e->getMessage(),
                'token' => $token,
                'code' => $e->getCode(),
            ];
        }
    }
}
