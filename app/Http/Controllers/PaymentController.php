<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function index()
    {
        return response()->json(Payment::all());
    }

    public function store(PaymentRequest $request)
    {
        $payment = Payment::create($request->validated());

        return response()->json(['message' => 'Payment created successfully!', 'data' => $payment], 201);
    }

    public function show(Payment $payment)
    {
        return response()->json($payment);
    }

    public function update(PaymentRequest $request, Payment $payment)
    {
        $payment->update($request->validated());

        return response()->json(['message' => 'Payment updated successfully!', 'data' => $payment]);
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully!']);
    }
}
