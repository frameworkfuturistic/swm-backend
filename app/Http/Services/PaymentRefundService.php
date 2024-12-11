<?php

namespace App\Http\Services;

use App\Models\PaymentRefund;

class PaymentRefundService
{
    public function createRefund(array $data)
    {
        return PaymentRefund::create($data);
    }

    public function updateRefund(PaymentRefund $refund, array $data)
    {
        $refund->update($data);

        return $refund;
    }

    public function deleteRefund(PaymentRefund $refund)
    {
        $refund->delete();
    }

    public function fetchRefundById($id)
    {
        return PaymentRefund::findOrFail($id);
    }
}
