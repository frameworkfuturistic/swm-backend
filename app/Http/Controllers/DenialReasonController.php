<?php

namespace App\Http\Controllers;

use App\Models\DenialReason;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * GET /denial-reasons  - List all categories.
 * POST /clusters  - Create a category:
 * {
 *    "ulb_id": 1,
 *    "category": "Health"
 * }
 *
 * GET /clusters/{id} - View a single category.
 * PUT /clusters/{id} - Update a category
 * {
 *   "ulb_id": 1,
 *  "category": "Education"
 * }
 * DELETE /clusters/{id} - Delete a category.
 */
class DenialReasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function showAll(Request $request)
    {
        try {
            $ulbId = $request->input('ulb_id', 1);
            $data = DenialReason::where('ulb_id', $ulbId)->get();

            return format_response(
                'Denial Reasons',
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
                'reason' => 'required|string|max:80|unique:denial_reasons,reason',
            ]);

            // Create a new denial reason
            $denialReason = DenialReason::create([
                'ulb_id' => $ulbId,
                'reason' => $validatedData['reason'],
            ]);

            // Return a success response
            return format_response(
                'Denial reason created successfully!',
                $denialReason,
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
            $denialReason = DenialReason::findOrFail($id);

            return format_response(
                'Denial reason',
                $denialReason,
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
            $denialReason = DenialReason::where('id', $id)
                ->where('ulb_id', $ulbId)
                ->first();

            if (! $denialReason) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $validatedData = $request->validate([
                'reason' => ['required',
                    'string',
                    'max:60',
                    Rule::unique('denial_reasons', 'reason')
                        ->where('ulb_id', $ulbId)
                        ->ignore($id, 'id'), // Exclude the current record by ID
                ],
            ]);

            $denialReason->update([
                'reason' => $validatedData['reason'],
            ]);

            return format_response(
                'Denial Reason updated successfully',
                $denialReason,
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
