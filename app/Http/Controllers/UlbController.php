<?php

namespace App\Http\Controllers;

use App\Models\Ulb;
use Illuminate\Http\Request;

class UlbController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ulbs = Ulb::all();
        return response()->json($ulbs);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'ulb_name' => 'required|string|max:80|unique:denial_reasons,ulb_name',
        ]);

        $ulb = Ulb::create($validatedData);
        return response()->json([
            'message' => 'Ulb created successfully!',
            'data' => $ulb
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ulb $ulb)
    {
      return response()->json($ulb);
    }

    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ulb $ulb)
    {
         $validatedData = $request->validate([
            'ulb_name' => 'required|string|max:80|unique:denial_reasons,ulb_name,' . $ulb->id,
         ]);

         $ulb->update($validatedData);

         return response()->json([
               'message' => 'Denial reason updated successfully!',
               'data' => $ulb
         ]);        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ulb $ulb)
    {
         $ulb->delete();
         return response()->json([
             'message' => 'Ulb deleted successfully!'
         ]);

    }
}
