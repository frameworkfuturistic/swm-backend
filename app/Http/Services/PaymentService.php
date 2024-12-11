<?php

namespace App\Http\Services;

use App\Models\Payment;

class PaymentService
{
    public function createPayment(array $data)
    {
        return Payment::create($data);
    }

    public function updatePayment(Payment $payment, array $data)
    {
        $payment->update($data);

        return $payment;
    }

    public function deletePayment(Payment $payment)
    {
        $payment->delete();
    }

    public function fetchPaymentById($id)
    {
        return Payment::findOrFail($id);
    }
}
