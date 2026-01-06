<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Generate QRIS payment
     * Supports both Midtrans and Demo mode
     */
    public static function generateQRIS($orderId, $amount, $customerName = 'Customer')
    {
        $useMidtrans = config('services.midtrans.enabled', false);

        if ($useMidtrans) {
            return self::generateMidtransQRIS($orderId, $amount, $customerName);
        } else {
            return self::generateDemoQRIS($orderId, $amount);
        }
    }

    /**
     * Generate Midtrans QRIS
     */
    private static function generateMidtransQRIS($orderId, $amount, $customerName)
    {
        try {
            \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);
            \Midtrans\Config::$isSanitized = true;
            \Midtrans\Config::$is3ds = true;

            $params = [
                'payment_type' => 'qris',
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $amount,
                ],
                'customer_details' => [
                    'first_name' => $customerName,
                ],
            ];

            $charge = \Midtrans\CoreApi::charge($params);

            return [
                'qr_string' => $charge->qr_string ?? null,
                'qr_image_url' => $charge->qr_image_url ?? null,
                'transaction_id' => $charge->transaction_id,
                'expired_at' => now()->addMinutes(15),
                'provider' => 'midtrans',
            ];
        } catch (\Exception $e) {
            \Log::error('Midtrans QRIS generation failed: ' . $e->getMessage());
            // Fallback to demo mode
            return self::generateDemoQRIS($orderId, $amount);
        }
    }

    /**
     * Generate Demo QRIS (for development/testing)
     */
    private static function generateDemoQRIS($orderId, $amount)
    {
        // Generate demo QR string
        $qrString = "00020101021226670016COM.MIDTRANS.WWW011893600918109870370802152022071210303UKE51440014ID.CO.QRIS.WWW0215ID10220004227950303UKE52045411530336054" . str_pad($amount, 10, '0', STR_PAD_LEFT) . "5802ID5913Warnet System6007Jakarta610512340";
        
        // Generate demo QR image URL (using QR code generator API)
        $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrString);

        return [
            'qr_string' => $qrString,
            'qr_image_url' => $qrImageUrl,
            'transaction_id' => 'DEMO-' . strtoupper(Str::random(12)),
            'expired_at' => now()->addMinutes(15),
            'provider' => 'demo',
        ];
    }

    /**
     * Check payment status
     */
    public static function checkPaymentStatus($transactionId)
    {
        if (str_starts_with($transactionId, 'DEMO-')) {
            // Demo mode: randomly return paid status for testing
            return [
                'status' => rand(0, 1) ? 'PAID' : 'PENDING',
                'paid_at' => rand(0, 1) ? now() : null,
            ];
        }

        // Real Midtrans check
        try {
            \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);

            $status = \Midtrans\Transaction::status($transactionId);

            return [
                'status' => self::mapMidtransStatus($status->transaction_status),
                'paid_at' => in_array($status->transaction_status, ['settlement', 'capture']) ? now() : null,
            ];
        } catch (\Exception $e) {
            \Log::error('Midtrans status check failed: ' . $e->getMessage());
            return ['status' => 'PENDING', 'paid_at' => null];
        }
    }

    /**
     * Map Midtrans status to our status
     */
    private static function mapMidtransStatus($midtransStatus)
    {
        return match($midtransStatus) {
            'settlement', 'capture' => 'PAID',
            'pending' => 'PENDING',
            'expire' => 'EXPIRED',
            'cancel', 'deny' => 'FAILED',
            default => 'PENDING',
        };
    }

    /**
     * Simulate payment (for demo/testing)
     */
    public static function simulatePayment($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);
        
        $payment->update([
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        // Update related order/session
        if ($payment->order_id) {
            $payment->order->update(['payment_status' => 'PAID']);
        }
        if ($payment->rental_session_id) {
            $payment->rentalSession->update(['payment_status' => 'PAID']);
        }

        return $payment;
    }
}
