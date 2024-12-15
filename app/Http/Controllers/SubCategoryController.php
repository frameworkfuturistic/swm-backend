<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubCategoryRequest;
use App\Models\SubCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Created on 04/12/2024
 * Author:
 *    Anil Mishra
 *
 * GET /sub-categories  - List all categories.
 * POST /sub-categories  - Create a category:
 * {
 *    "category_id": 1,
 *    "sub_category": "Some Subcategory"
 * }
 *
 * GET /sub-categories/{id} - View a single category.
 * PUT /sub-categories/{id} - Update a category
 * {
 *   "sub_category": "Updated Some Subcategory"
 * }
 * DELETE /sub-categories/{id} - Delete a category.
 */
class SubCategoryController extends Controller
{
    public function index()
    {
        return format_response(
            'Not supported',
            null,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /**
     * Display a listing of the resource.
     */
    public function showAll(Request $request, $id)
    {
        try {
            // $subCategories = SubCategory::with('category')->where('category_id', $request->categoryId)->get();

            // $validated = $request->route()->parameters();

            // Validate the ID parameter
            // $request->validate([
            //     'id' => 'required|integer|exists:sub_categories,id',
            // ]);

            $numericId = intval($id);

            if ($numericId <= 0 || (string) $numericId !== $id) {
                abort(404);
            }

            $subCategories = SubCategory::with('category')->where('category_id', $id)->get();

            return format_response(
                'success',
                $subCategories,
                Response::HTTP_OK
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                'Validation Failed',
                $e->errors(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );

        } catch (Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SubCategoryRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $subCategory = SubCategory::create([
                'category_id' => $validatedData['categoryId'],
                'sub_category' => $validatedData['subCategory'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return format_response(
                'Sub Category Created',
                $subCategory,
                Response::HTTP_CREATED
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data insertion, Check if duplicate',
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
            $subCategories = SubCategory::findOrFail($id);

            return format_response(
                'Sub Categoriy record',
                $subCategories,
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
    public function update(SubCategoryRequest $request, int $id)
    {
        try {
            $subCategory = SubCategory::find($id);
            if ($subCategory == null) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $validated = $request->validated();

            $updateData = [
                'sub_category' => $validated['subCategory'],
                'updated_at' => now(),
            ];

            $subCategory->update($updateData);

            return format_response(
                'Sub Category updated successfully',
                $subCategory,
                Response::HTTP_OK
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
     * Remove the specified resource from storage.
     */
    public function destroy(SubCategory $subCategory)
    {
        //   $subCategory->delete();

        return response()->json(['message' => 'SubCategory deleted successfully.']);
    }
}
