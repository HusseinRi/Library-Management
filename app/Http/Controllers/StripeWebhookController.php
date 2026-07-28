<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // 1. قراءة الـ Payload الخادم المباشر
        $payload = file_get_contents('php://input');
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret, 86400);
        } catch (UnexpectedValueException $e) {
            Log::error('Stripe Webhook: Invalid payload.');
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook Verification Error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // 2. معالجة أحداث الدفع
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentSuccess($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentFailed($paymentIntent);
                break;

            default:
                Log::info('Received unhandled event type ' . $event->type);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * معالجة نجاح عملية الدفع
     */
    private function handlePaymentSuccess($paymentIntent)
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        DB::transaction(function () use ($paymentIntent, $orderId) {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)
                ->orWhere('order_id', $orderId)
                ->first();

            if ($payment && $payment->order) {
                // 1. تحديث حالة الدفع والطلب
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                $payment->order->update([
                    'status' => 'completed',
                ]);

                // 2. إدراج الكتب في جدول my_books للعميل تلقائياً
                foreach ($payment->order->items as $item) {
                    \App\Models\MyBook::firstOrCreate(
                        [
                            'user_id' => $payment->order->user_id,
                            'book_id' => $item->book_id,
                        ],
                        [
                            'purchase_date' => now(),
                            'price' => $item->price,
                        ]
                    );
                }

                Log::info("Order #{$payment->order_id} completed and books added to MyLibrary successfully.");
            }
        });
    }

    /**
     * معالجة فشل عملية الدفع
     */
    private function handlePaymentFailed($paymentIntent)
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        DB::transaction(function () use ($paymentIntent, $orderId) {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)
                ->orWhere('order_id', $orderId)
                ->first();

            if ($payment) {
                $payment->update(['status' => 'failed']);

                if ($payment->order) {
                    $payment->order->update(['status' => 'failed']);
                }

                Log::warning("Order #{$payment->order_id} marked as FAILED.");
            }
        });
    }
}