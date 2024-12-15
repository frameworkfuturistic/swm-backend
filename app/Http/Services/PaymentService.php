<?php

namespace App\Http\Services;

use App\Models\Payment;
use App\Models\PaymentOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class PaymentService
{
    /** Calling Payment Gateway
     * ===================================================================================================
     * function initRazorpayPayment(order) {
     *     var options = {
     *         "key": "YOUR_RAZORPAY_KEY_ID",
     *         "amount": order.amount,
     *         "currency": "INR",
     *         "name": "Your Company Name",
     *         "description": "Test Transaction",
     *         "order_id": order.razorpay_order_id,
     *         "handler": function (response) {
     *             // Send payment verification request to your backend
     *             verifyPayment(response);
     *         },
     *         "prefill": {
     *             "name": "Customer Name",
     *             "email": "customer@example.com",
     *             "contact": "9999999999"
     *         },
     *         "notes": {
     *             "address": "Razorpay Corporate Office"
     *         },
     *         "theme": {
     *             "color": "#3399cc"
     *         }
     *     };
     *
     *     var rzp1 = new Razorpay(options);
     *     rzp1.open();
     * }
     */
    public function createOrder(Request $request)
    {
        $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

        // Create an order
        $order = $api->order->create([
            'receipt' => 'order_'.uniqid(),
            'amount' => $request->amount * 100, // Amount in paise
            'currency' => 'INR',
            'notes' => [
                'user_id' => auth()->id(),
                'order_details' => 'Some additional information',
            ],
        ]);

        // Save order details to your database
        $localOrder = PaymentOrder::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'razorpay_order_id' => $order->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'razorpay_order_id' => $order->id,
            'amount' => $order->amount,
        ]);
    }

    /**
     * Generate UIP QR Code for payment ===============================================================
     * 1. generateUpiQr
     * 2. verifyPayment
     *
     * async function generateUpiQr(amount, orderId) {
     *     try {
     *         const response = await axios.post('/generate-upi-qr', {
     *             amount: amount,
     *             order_id: orderId
     *         });
     *
     *         // Display QR code image
     *         document.getElementById('qr-code-container').innerHTML =
     *             `<img src="${response.data.qr_code_image}" alt="UPI QR Code">`;
     *
     *         // Start polling for payment
     *         pollPaymentStatus(response.data.qr_code_id);
     *     } catch (error) {
     *         console.error('Error generating QR code:', error);
     *     }
     * }
     *
     * function pollPaymentStatus(qrCodeId) {
     *     const interval = setInterval(async () => {
     *         try {
     *             const response = await axios.get(`/verify-payment/${qrCodeId}`);
     *
     *             if (response.data.status === 'success') {
     *                 clearInterval(interval);
     *                 // Handle successful payment
     *                 alert('Payment Successful!');
     *             }
     *         } catch (error) {
     *             console.error('Error checking payment:', error);
     *             clearInterval(interval);
     *         }
     *     }, 5000); // Poll every 5 seconds
     * }
     */
    public function generateUpiQr(Request $request)
    {
        $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

        try {
            $qrCode = $api->qrCode->create([
                'type' => 'upi',
                'name' => 'Store Name', // Your store or business name
                'usage' => 'single',
                'fixed_amount' => true,
                'payment_amount' => $request->amount * 100, // Amount in paise
                'description' => 'Payment for order #'.$request->order_id,
            ]);

            return response()->json([
                'qr_code_id' => $qrCode->id,
                'qr_code_image' => $qrCode->image_url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * After Qr Code being shared, the application should wait for the response
     * from the backend. Following function will be called immediately following
     * QR Code scanning so that any success feedback can be given.
     */
    public function verifyPayment($qrCodeId)
    {
        $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

        try {
            $qrCode = $api->qrCode->fetch($qrCodeId);

            // Check payment status
            $payments = $qrCode->payments();

            if (! empty($payments->items)) {
                // Payment received
                return response()->json([
                    'status' => 'success',
                    'payment_details' => $payments->items[0],
                ]);
            }

            return response()->json([
                'status' => 'pending',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ========================================================================
     * Payment Link Implementation
     * 1. createPaymentLink
     * 2. cancelPaymentLink
     * 3. fetchPaymentLinkDetails
     * 4. handlePaymentLinkEvents (Webhook Handler (Update in previous Webhook Controller)
     */
    public function createPaymentLink(Request $request)
    {
        try {
            $razorpay = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));
            // Validate input
            $validatedData = $request->validate([
                'customer_name' => 'required|string|max:100',
                'customer_email' => 'required|email',
                'customer_phone' => 'required|string|min:10|max:15',
                'amount' => 'required|numeric|min:1',
                'description' => 'nullable|string|max:255',
            ]);

            // Create an order in local database
            $order = PaymentOrder::create([
                'user_id' => auth()->id() ?? null,
                'amount' => $validatedData['amount'],
                'status' => 'pending',
                'notes' => json_encode([
                    'customer_name' => $validatedData['customer_name'],
                    'customer_email' => $validatedData['customer_email'],
                    'customer_phone' => $validatedData['customer_phone'],
                ]),
            ]);

            // Create Razorpay Payment Link
            $paymentLink = $razorpay->paymentLink->create([
                'amount' => $order->amount * 100, // Amount in paise
                'currency' => 'INR',
                'accept_partial' => false,
                'first_min_partial_amount' => null,
                'expire_by' => now()->addDays(7)->timestamp, // Link valid for 7 days
                'reference_id' => $order->id,
                'description' => $validatedData['description'] ?? 'Payment for Order #'.$order->id,
                'customer' => [
                    'name' => $validatedData['customer_name'],
                    'email' => $validatedData['customer_email'],
                    'contact' => $validatedData['customer_phone'],
                ],
                'notify' => [
                    'sms' => true,
                    'email' => true,
                ],
                'reminder_enable' => true,
                'notes' => [
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                ],
            ]);

            // Update order with payment link details
            $order->update([
                'razorpay_payment_link_id' => $paymentLink->id,
                'razorpay_payment_link_url' => $paymentLink->short_url,
            ]);

            return response()->json([
                'order_id' => $order->id,
                'payment_link' => $paymentLink->short_url,
                'payment_link_id' => $paymentLink->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Link Creation Failed: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to create payment link',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancelPaymentLink($paymentLinkId)
    {
        try {
            $razorpay = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

            // Cancel the payment link
            $paymentLink = $razorpay->paymentLink->cancel($paymentLinkId);

            // Update local order status
            $order = PaymentOrder::where('razorpay_payment_link_id', $paymentLinkId)->first();
            if ($order) {
                $order->update([
                    'status' => 'cancelled',
                ]);
            }

            return response()->json([
                'status' => 'Payment link cancelled',
                'payment_link_id' => $paymentLinkId,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Link Cancellation Failed: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to cancel payment link',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchPaymentLinkDetails($paymentLinkId)
    {
        try {
            $razorpay = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

            // Fetch payment link details from Razorpay
            $paymentLink = $razorpay->paymentLink->fetch($paymentLinkId);

            // Find associated local order
            $order = PaymentOrder::where('razorpay_payment_link_id', $paymentLinkId)->first();

            return response()->json([
                'payment_link' => $paymentLink->toArray(),
                'order' => $order,
            ]);

        } catch (\Exception $e) {
            Log::error('Fetch Payment Link Details Failed: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to fetch payment link details',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function handlePaymentLinkEvents($payload)
    {
        $paymentLink = $payload['payload']['payment_link']['entity'];

        // Find associated order
        $order = PaymentOrder::where('razorpay_payment_link_id', $paymentLink['id'])->first();

        if (! $order) {
            Log::warning('Order not found for payment link', [
                'payment_link_id' => $paymentLink['id'],
            ]);

            return;
        }

        switch ($payload['event']) {
            case 'payment_link.issued':
                $order->update([
                    'status' => 'link_issued',
                    'payment_link_issued_at' => now(),
                ]);
                break;

            case 'payment_link.paid':
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'razorpay_payment_id' => $paymentLink['payments'][0]['id'] ?? null,
                ]);
                break;

            case 'payment_link.cancelled':
                $order->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
                break;
        }
    }
}
