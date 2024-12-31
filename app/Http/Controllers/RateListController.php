<?php

namespace App\Http\Controllers;

use App\Http\Requests\RateListRequest;
use App\Models\RateList;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Created on 06/12/2024
 * Author: Anil Mishra
 *
 * APIs
 * [POST]  /api/ratelist               1. Admin can add Rate List
 * [PUT]   /api/ratelist/{id}          2. Admin can update Rate List data
 * [GET]   /api/ratelist/{id}          3. Admin can See Rate List data
 */
class RateListController extends Controller
{
    /**
     * [POST]  /api/ratelist               1. Admin can add Rate List
     * Completed [OK]
     * Request json =>json/entities/entityRequest.json
     * Response json =>json/entities/entityResponse.json
     */
    public function store(RateListRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $ulbId = $request->ulb_id;

            // Validate and extract entity data
            // $entityData = $request->input('entity');
            $rateList = RateList::create([
                'ulb_id' => $ulbId,
                'rate_list' => $validatedData['rateList'],
                'amount' => $validatedData['amount'],
                'vrno' => 1,
            ]);

            return format_response(
                'Rate created successfully',
                $rateList,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * [PUT]   /api/entities/{id}          3. Admin can update Entity data
     * Completed [OK]
     * Request json =>json/entities/entityRequest.json
     * Response json =>json/entities/entityResponse.json
     */
    public function update(RateListRequest $request, int $id)
    {
        try {
            $rateList = RateList::find($id);
            if ($rateList == null) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $validatedData = $request->validated();

            $updateData = [
                'rate_list' => $validatedData['rateList'],
                'amount' => $validatedData['amount'],
                'updated_at' => now(),
            ];

            $rateList->update($updateData);

            return format_response(
                'Rate List updated successfully',
                $rateList,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * [GET]   /api/ratelist/{id}          3. Admin can See Rate List data
     * Completed [OK]
     */
    public function showAll(Request $request)
    {
        try {
            $entity = RateList::all();

            return format_response(
                'Rate List',
                $entity,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * [GET]   /api/ratelist/{id}          3. Admin can See Rate List data
     * Completed [OK]
     */
    public function show($id)
    {
        try {
            $entity = RateList::findOrFail($id);

            return format_response(
                'Rate List',
                $entity,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
