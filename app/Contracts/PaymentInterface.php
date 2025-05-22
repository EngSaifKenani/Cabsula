<?php

namespace App\Contracts;

interface PaymentInterface
{
    /**
     * إنشاء دفعة وإرجاع البيانات الضرورية (مثل رابط الدفع أو Client Secret).
     */
    public function createPayment(float $amount, string $currency = 'USD', array $meta = []): array;

    /**
     * تأكيد الدفعة (عند العودة من بوابة الدفع).
     */
    public function confirmPayment(string $paymentId): array;
}
