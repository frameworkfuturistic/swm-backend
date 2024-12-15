<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Verify webhook signature
        $webhookSignature = $request->header('X-Razorpay-Signature');
        $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');

        try {
            // Verify webhook signature
            $this->verifyWebhookSignature(
                $request->getContent(),
                $webhookSignature,
                $webhookSecret
            );

            // Process the webhook payload
            $payload = json_decode($request->getContent(), true);
            $event = $payload['event'];

            switch ($event) {
                case 'payment.captured':
                    $this->handlePaymentCapture($payload['payload']['payment']['entity']);
                    break;

                case 'payment.failed':
                    $this->handlePaymentFailure($payload['payload']['payment']['entity']);
                    break;

                case 'refund.created':
                    $this->handleRefundCreated($payload['payload']['refund']['entity']);
                    break;

                default:
                    Log::info('Unhandled Razorpay Webhook Event', ['event' => $event]);
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Webhook Verification Failed', [
                'error' => $e->getMessage(),
                'payload' => $request->getContent(),
            ]);

            return response()->json(['status' => 'error'], 400);
        }
    }

    protected function verifyWebhookSignature($payload, $webhookSignature, $webhookSecret)
    {
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (hash_equals($expectedSignature, $webhookSignature) === false) {
            throw new \Exception('Invalid webhook signature');
        }
    }

    protected function handlePaymentCapture($payment)
    {
        // Update order status in your database
        DB::table('orders')->where('razorpay_payment_id', $payment['id'])
            ->update([
                'status' => 'paid',
                'payment_status' => 'captured',
                'amount_paid' => $payment['amount'] / 100, // Convert paise to rupees
            ]);

        Log::info('Payment Captured', ['payment_id' => $payment['id']]);
    }

    protected function handlePaymentFailure($payment)
    {
        // Update order status in your database
        DB::table('orders')->where('razorpay_payment_id', $payment['id'])
            ->update([
                'status' => 'failed',
                'payment_status' => 'failed',
                'failure_reason' => $payment['error_description'] ?? 'Unknown error',
            ]);

        Log::error('Payment Failed', [
            'payment_id' => $payment['id'],
            'error' => $payment['error_description'] ?? 'No error description',
        ]);
    }

    protected function handleRefundCreated($refund)
    {
        // Update order and refund status in your database
        DB::table('orders')->where('razorpay_payment_id', $refund['payment_id'])
            ->update([
                'status' => 'refunded',
                'refund_amount' => $refund['amount'] / 100,
            ]);

        Log::info('Refund Created', ['refund_id' => $refund['id']]);
    }

    //Old Implementation (can be discarded)
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        // Razorpay secret key (used to validate the webhook)
        $secret = env('RAZORPAY_WEBHOOK_SECRET');

        // Validate webhook signature
        if (! $this->isValidSignature($payload, $signature, $secret)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // Process the webhook event
        $event = json_decode($payload);

        switch ($event->event) {
            case 'payment.captured':
                $this->handlePaymentCaptured($event->payload->payment->entity);
                break;

            case 'payment.failed':
                $this->handlePaymentFailed($event->payload->payment->entity);
                break;

            default:
                Log::info('Unhandled webhook event: '.$event->event);
        }

        return response()->json(['message' => 'Webhook handled']);
    }

    // Old Implementation
    private function isValidSignature($payload, $signature, $secret)
    {
        $computedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computedSignature, $signature);
    }

    // Old Implementation
    private function handlePaymentCaptured($payment)
    {
        Log::info('Payment captured: '.json_encode($payment));
        // Update your database or perform other actions
    }

    // Old Implementation
    private function handlePaymentFailed($payment)
    {
        Log::info('Payment failed: '.json_encode($payment));
        // Update your database or perform other actions
    }
}
