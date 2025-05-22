<?php

namespace App\Services;

use App\Contracts\PaymentInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class PayPalService implements PaymentInterface
{
    protected string $clientId;
    protected string $secret;
    protected string $baseUrl;

    public function __construct()
    {
        $this->clientId = config('payment.paypal.client_id');
        $this->secret = config('payment.paypal.secret');
        $mode = config('payment.paypal.mode', 'sandbox');

        $this->baseUrl = $mode === 'live'
            ? 'https://api.paypal.com'
            : 'https://api.sandbox.paypal.com';
    }

    protected function getAccessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->secret)
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            Log::error('PayPal Auth Failed', ['response' => $response->body()]);
            throw new \Exception('Failed to authenticate with PayPal');
        }

        return $response->json()['access_token'];
    }

    public function createOrder(float $amount, string $currency = 'USD'): array
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('payment.approve'), // تحتاج تعدل هذا حسب تطبيقك
                    'cancel_url' => route('payment.cancel'),  // نفس الشيء
                ],
            ]);

        if ($response->failed()) {
            Log::error('PayPal Order Creation Failed', ['response' => $response->body()]);
            throw new \Exception('Failed to create PayPal order');
        }

        return $response->json();
    }

    public function captureOrder(string $orderId): array
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        if ($response->failed()) {
            Log::error('PayPal Capture Failed', ['order_id' => $orderId, 'response' => $response->body()]);
            throw new \Exception('Failed to capture PayPal order');
        }

        return $response->json();
    }

    public function createPayment(float $amount, string $currency = 'USD', array $meta = []): array
    {
        $order = $this->createOrder($amount, $currency);

        return [
            'method' => 'paypal',
            'approve_url' => collect($order['links'])->firstWhere('rel', 'approve')['href'],
            'order_id' => $order['id'],
        ];
    }

    public function confirmPayment(string $paymentId): array
    {
        $response = $this->captureOrder($paymentId);

        return [
            'status' => $response['status'],
            'amount' => $response['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? null,
        ];
    }
}
