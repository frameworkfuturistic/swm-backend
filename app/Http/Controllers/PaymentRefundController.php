<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRefundRequest;
use App\Models\PaymentRefund;

class PaymentRefundController extends Controller
{
    public function index()
    {
        return response()->json(PaymentRefund::all());
    }

    public function store(PaymentRefundRequest $request)
    {
        $refund = PaymentRefund::create($request->validated());

        return response()->json(['message' => 'Refund created successfully!', 'data' => $refund], 201);
    }

    public function show(PaymentRefund $refund)
    {
        return response()->json($refund);
    }

    public function update(PaymentRefundRequest $request, PaymentRefund $refund)
    {
        $refund->update($request->validated());

        return response()->json(['message' => 'Refund updated successfully!', 'data' => $refund]);
    }

    public function destroy(PaymentRefund $refund)
    {
        $refund->delete();

        return response()->json(['message' => 'Refund deleted successfully!']);
    }
}
