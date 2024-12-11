<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
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

    private function isValidSignature($payload, $signature, $secret)
    {
        $computedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computedSignature, $signature);
    }

    private function handlePaymentCaptured($payment)
    {
        Log::info('Payment captured: '.json_encode($payment));
        // Update your database or perform other actions
    }

    private function handlePaymentFailed($payment)
    {
        Log::info('Payment failed: '.json_encode($payment));
        // Update your database or perform other actions
    }
}
