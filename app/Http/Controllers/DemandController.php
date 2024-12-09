<?php

namespace App\Http\Controllers;

use App\Models\Demand;
use Illuminate\Http\Request;

class DemandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $demands = Demand::with('ratepayer')->get();

        return response()->json($demands);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ratepayer_id' => 'required|exists:ratepayers,id',
            'bill_month' => 'required|integer|between:1,12',
            'bill_year' => 'required|integer|digits:4',
            'demand' => 'nullable|integer|min:0',
            'payment' => 'nullable|integer|min:0',
        ]);

        $demand = Demand::create($validated);

        return response()->json($demand, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Demand $demand)
    {
        return response()->json($demand->load('ratepayer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Demand $demand)
    {
        $validated = $request->validate([
            'ratepayer_id' => 'required|exists:ratepayers,id',
            'bill_month' => 'required|integer|between:1,12',
            'bill_year' => 'required|integer|digits:4',
            'demand' => 'nullable|integer|min:0',
            'payment' => 'nullable|integer|min:0',
        ]);

        $demand->update($validated);

        return response()->json($demand);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Demand $demand)
    {
        $demand->delete();

        return response()->json(['message' => 'Demand deleted successfully.']);
    }
}
