<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use App\Models\RentalSession;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments
     */
    public function index(Request $request)
    {
        $query = Payment::with('order', 'rentalSession');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('method')) {
            $query->where('method', $request->method);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Create a new payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'rental_session_id' => 'nullable|exists:rental_sessions,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|in:QRIS,CASH',
        ]);

        // Validate that either order_id or rental_session_id is provided
        if (!$validated['order_id'] && !$validated['rental_session_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Either order_id or rental_session_id is required',
            ], 422);
        }

        $payment = Payment::create([
            'order_id' => $validated['order_id'] ?? null,
            'rental_session_id' => $validated['rental_session_id'] ?? null,
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'status' => 'PENDING',
        ]);

        // Generate QRIS for QRIS payments
        if ($validated['method'] === 'QRIS') {
            $customerName = $payment->order ? $payment->order->customer_name : 'Customer';
            $qrisData = PaymentService::generateQRIS(
                'PAY-' . $payment->id,
                $payment->amount,
                $customerName
            );

            $payment->update([
                'qris_code' => $qrisData['qr_string'],
                'qris_image_url' => $qrisData['qr_image_url'],
                'transaction_ref' => $qrisData['transaction_id'],
                'expired_at' => $qrisData['expired_at'],
            ]);
        }

        $payment->load('order', 'rentalSession');

        return response()->json([
            'success' => true,
            'message' => 'Payment created',
            'data' => $payment,
        ], 201);
    }

    /**
     * Display the specified payment
     */
    public function show(Payment $payment)
    {
        $payment->load('order', 'rentalSession');

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Update payment
     */
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'status' => 'in:PENDING,COMPLETED,FAILED',
            'transaction_ref' => 'nullable|string',
        ]);

        $payment->update($validated);

        // Update related order/session status if payment completed
        if ($validated['status'] === 'COMPLETED') {
            if ($payment->order_id) {
                Order::find($payment->order_id)->update(['payment_status' => 'PAID']);
            }
            if ($payment->rental_session_id) {
                RentalSession::find($payment->rental_session_id)->update(['paid' => true]);
            }
            $payment->update(['completed_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment updated',
            'data' => $payment,
        ]);
    }

    /**
     * Remove the specified payment
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted',
        ]);
    }

    /**
     * Confirm payment for CASH method
     */
    public function confirm(Request $request, Payment $payment)
    {
        if ($payment->method !== 'CASH') {
            return response()->json([
                'success' => false,
                'message' => 'Can only confirm CASH payments',
            ], 422);
        }

        $payment->update([
            'status' => 'COMPLETED',
            'completed_at' => now(),
        ]);

        // Update related order/session
        if ($payment->order_id) {
            Order::find($payment->order_id)->update(['payment_status' => 'PAID']);
        }
        if ($payment->rental_session_id) {
            RentalSession::find($payment->rental_session_id)->update(['paid' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed',
            'data' => $payment,
        ]);
    }

    /**
     * Handle QRIS webhook from payment provider
     */
    public function webhookQris(Request $request)
    {
        // TODO: Verify webhook signature from payment provider

        $transactionRef = $request->input('reference_id');
        $status = $request->input('status');

        $payment = Payment::where('transaction_ref', $transactionRef)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        if ($status === 'COMPLETED' || $status === 'PAID') {
            $payment->update([
                'status' => 'COMPLETED',
                'completed_at' => now(),
            ]);

            // Update related order/session
            if ($payment->order_id) {
                Order::find($payment->order_id)->update(['payment_status' => 'PAID']);
            }
            if ($payment->rental_session_id) {
                RentalSession::find($payment->rental_session_id)->update(['paid' => true]);
            }
        } elseif ($status === 'FAILED' || $status === 'CANCELLED') {
            $payment->update(['status' => 'FAILED']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed',
        ]);
    }

    /**
     * Generate QRIS code (placeholder)
     */
    private function generateQRISCode()
    {
        // TODO: Integrate with actual QRIS provider API
        return 'QRIS_' . uniqid();
    }
}
