<?php

namespace App\Http\Services;

use App\Models\PaymentWebhook;

class PaymentWebhookService
{
    public function handleWebhook(array $data)
    {
        return PaymentWebhook::create($data);
    }

    public function getAllWebhooks()
    {
        return PaymentWebhook::all();
    }

    public function getWebhookById($id)
    {
        return PaymentWebhook::findOrFail($id);
    }

    public function deleteWebhook(PaymentWebhook $webhook)
    {
        $webhook->delete();
    }
}
