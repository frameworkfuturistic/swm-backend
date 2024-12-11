<?php

namespace App\Http\Controllers;

use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class WardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function showAll(Request $request)
    {
        try {
            $ulbId = $request->input('ulb_id', 1);
            $data = Ward::where('ulb_id', $ulbId)->get();

            return format_response(
                'Ward List',
                $data,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return format_response(
                'success',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $ulbId = $request->ulb_id;
            $validatedData = $request->validate([
                'wardName' => 'required|string|max:80|unique:denial_reasons,reason',
            ]);

            // Create a new denial reason
            $ward = Ward::create([
                'ulb_id' => $ulbId,
                'ward_name' => $validatedData['wardName'],
            ]);

            // Return a success response
            return format_response(
                'Ward created successfully!',
                $ward,
                Response::HTTP_CREATED
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                'Validation Failed',
                $e->errors(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during registration',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {
            $ward = Ward::findOrFail($id);

            return format_response(
                'Ward',
                $ward,
                Response::HTTP_OK
            );

        } catch (\Exception $e) {
            return format_response(
                'An error occurred during registration',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try {
            $ulbId = $request->ulb_id;
            $ward = Ward::where('id', $id)
                ->where('ulb_id', $ulbId)
                ->first();

            if (! $ward) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $validatedData = $request->validate([
                'wardName' => ['required',
                    'string',
                    'max:60',
                    Rule::unique('wards', 'ward_name')
                        ->where('ulb_id', $ulbId)
                        ->ignore($id, 'id'), // Exclude the current record by ID
                ],
            ]);

            $ward->update([
                'ward_name' => $validatedData['wardName'],
            ]);

            return format_response(
                'Denial Reason updated successfully',
                $ward,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during registration',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $denialReason)
    {
        return format_response(
            'Could not Process',
            null,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
