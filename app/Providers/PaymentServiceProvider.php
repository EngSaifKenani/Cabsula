<?php

namespace App\Providers;

use App\Contracts\PaymentInterface;
use App\Services\PayPalService;
use App\Services\StripeService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */

    public function register(): void
    {
        $this->app->singleton(StripeService::class, fn() => new StripeService());

        $this->app->singleton(PayPalService::class, fn() => new PayPalService());

        // ربط PaymentInterface بالمزود المناسب حسب الإعدادات
        $this->app->bind(PaymentInterface::class, function ($app) {
            $method = config('payment.default', 'stripe');

            return match ($method) {
                'paypal' => $app->make(PayPalService::class),
                default => $app->make(StripeService::class),
            };
        });
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


    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
