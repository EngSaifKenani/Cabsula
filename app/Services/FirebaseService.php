<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $serviceAccountPath = storage_path('app/firebase/service-account.json');

        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $this->messaging = $factory->createMessaging();
    }

    /**
     * إرسال إشعار إلى جهاز واحد أو مجموعة من الأجهزة.
     *
     * @param string|array $deviceTokens
     * @param string $title
     * @param string $body
     * @return array
     */
    public function sendNotification($deviceTokens, string $title, string $body,array $data = [])
    {
        $notification = Notification::create($title, $body);
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data);

        if (is_array($deviceTokens)) {
            //foreach (array_chunk($tokens, 500) as $chunk) {
            //    $firebase->sendNotification($chunk, 'عنوان', 'نص');
            //}

            // إرسال إلى مجموعة من الأجهزة
            $report = $this->messaging->sendMulticast($message, $deviceTokens);

            return [
                'type' => 'multicast',
                'successes' => $report->successes()->count(),
                'failures' => $report->failures()->count(),
                'responses' => $report->responses(),
            ];
        } else {


            //$firebase->sendNotification('DEVICE_TOKEN', 'عنوان', 'محتوى');
            // إرسال إلى جهاز واحد فقط
            $response = $this->messaging->send($message->withChangedTarget('token', $deviceTokens));

            return [
                'type' => 'single',
                'response' => $response,
            ];
        }
    }

    //$firebase->sendNotification(
    //    $deviceToken,
    //    'طلب جديد',
    //    'لديك طلب جديد في المتجر',
    //    [
    //        'order_id' => '12345',
    //        'type' => 'order',
    //        'action' => 'open_order_details'
    //    ]
    //);

}
