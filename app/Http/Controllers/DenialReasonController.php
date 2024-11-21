<?php

namespace App\Http\Controllers;

use App\Models\DenialReason;
use Illuminate\Http\Request;

class DenialReasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $denialReasons = DenialReason::all(); // Fetch all denial reasons
        return response()->json($denialReasons);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Return a view for creating denial reasons (if applicable)
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'reason' => 'required|string|max:80|unique:denial_reasons,ulb_name',
        ]);

        $denialReason = DenialReason::create($validatedData);
        return response()->json([
            'message' => 'Denial reason created successfully!',
            'data' => $denialReason
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(DenialReason $denialReason)
    {
        return response()->json($denialReason);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DenialReason $denialReason)
    {
        // Return a view for editing denial reasons (if applicable)
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DenialReason $denialReason)
    {
        $validatedData = $request->validate([
            'reason' => 'required|string|max:80|unique:denial_reasons,ulb_name,' . $denialReason->id,
        ]);

        $denialReason->update($validatedData);

        return response()->json([
            'message' => 'Denial reason updated successfully!',
            'data' => $denialReason
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DenialReason $denialReason)
    {
        $denialReason->delete();
        return response()->json([
            'message' => 'Denial reason deleted successfully!'
        ]);
    }
}

