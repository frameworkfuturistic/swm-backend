<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentWebhookRequest;
use App\Models\PaymentWebhook;

class PaymentWebhookController extends Controller
{
    public function index()
    {
        return response()->json(PaymentWebhook::all());
    }

    public function store(PaymentWebhookRequest $request)
    {
        $webhook = PaymentWebhook::create($request->validated());

        return response()->json(['message' => 'Webhook received successfully!', 'data' => $webhook], 201);
    }

    public function show(PaymentWebhook $webhook)
    {
        return response()->json($webhook);
    }

    public function destroy(PaymentWebhook $webhook)
    {
        $webhook->delete();

        return response()->json(['message' => 'Webhook deleted successfully!']);
    }
}
