<?php

namespace App\Services;

use App\Contracts\PaymentInterface;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;


class StripeService implements PaymentInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('payment.stripe.secret'));
    }

    public function createCustomer(string $email, string $name): Customer
    {
        return Customer::create([
            'email' => $email,
            'name' => $name,
        ]);
    }

    public function createPayment(
        float $amount,
        string $currency = 'usd',
        array $meta = []
    ): array {
        try {
            $intent = PaymentIntent::create([
                'amount' => intval($amount * 100),
                'currency' => $currency,
                'metadata' => $meta,
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            return [
                'method' => 'stripe',
                'client_secret' => $intent->client_secret,
                'payment_id' => $intent->id,
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception("Stripe Error: " . $e->getMessage(), 500);
        }
    }

    public function confirmPayment(string $paymentId): array
    {
        try {
            $intent = PaymentIntent::retrieve($paymentId);

            return [
                'status' => $intent->status,
                'amount_received' => $intent->amount_received / 100,
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception("Stripe Error: " . $e->getMessage(), 500);
        }
    }
}


// public function checkout(Request $request): JsonResponse
//    {
//        $amount = $request->input('amount', 50.00);
//        $currency = $request->input('currency', 'USD');
//        $meta = $request->input('meta', ['order_id' => uniqid('ORD_')]);
//
//        $payment = $this->payment->createPayment($amount, $currency, $meta);
//
//        return response()->json($payment);
//    }
//
//    /**
//     * تأكيد الدفع بعد العودة من PayPal أو بعد الدفع عبر Stripe.
//     */
//    public function confirm(Request $request): JsonResponse
//    {
//        $paymentId = $request->input('payment_id');
//
//        if (!$paymentId) {
//            return response()->json(['error' => 'payment_id مطلوب'], 422);
//        }
//
//        $confirmation = $this->payment->confirmPayment($paymentId);
//
//        // هنا يمكن تخزين النتيجة في قاعدة البيانات إذا أردت...
//
//        return response()->json([
//            'message' => 'تم تأكيد الدفع',
//            'data' => $confirmation,
//        ]);
//    }
//}
