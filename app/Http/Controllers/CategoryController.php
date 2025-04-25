<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Created on 04/12/2024
 * Author:
 *    Anil Mishra
 *
 * GET /categories - List all categories.
 * POST /categories - Create a category:
 * {
 *    "ulb_id": 1,
 *    "category": "Health"
 * }
 *
 * GET /categories/{id} - View a single category.
 * PUT /categories/{id} - Update a category
 * {
 *   "ulb_id": 1,
 *  "category": "Education"
 * }
 * DELETE /categories/{id} - Delete a category.
 */
class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * return categories.
     * url: http://127.0.0.1:8000/api/categories
     */
    public function showAll()
    {
        try {
            $categories = Category::with('subCategories')->get();
            // Category::all();

            return format_response(
                'Categories',
                $categories,
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            DB::rollBack();

            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        //
    }

    /**
     * Store a newly created resource in storage.
     * url: http://127.0.0.1:8000/api/categories
     *{
     *    "category": "first category"
     *}}
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'category' => 'required|string|max:50|unique:categories,category,NULL,id,ulb_id,'.$request->ulb_id,
                'categoryCode' => 'required|string|max:50|unique:categories,category_code,NULL,id,ulb_id,' . $request->ulb_id,
            ]);

            $ulbId = $request->ulb_id;
            $category = DB::table('categories')->insert([
                'ulb_id' => $ulbId,
                'category' => $validated['category'],
                'category_code' => $validated['categoryCode'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // $category = Category::create($validated);

            return format_response(
                'Category Created',
                $category,
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
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Display the specified resource.
     */
    // public function show(Category $category)
    // {
    //    return response()->json($category);
    // }

    public function show(int $id)
    {
        try {
            // $categories = Category::findOrFail($id)->with(SubCategory);
            $category = Category::with('subCategories')->findOrFail($id);

            return format_response(
                'Category Record',
                $category,
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
            $category = Category::find($id);
            if ($category == null) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $validated = $request->validate([
                'category' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'category')
                        ->where('ulb_id', $request->ulb_id)
                        ->ignore($id), // Exclude the current record by ID
                ],
            ]);

            $category->update([
                'category' => $validated['category'],
                'updated_at' => now(),
            ]);

            return format_response(
                'Category updated successfully',
                $category,
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

    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function destroy(Category $category)
    // {
    //    $category->delete();
    //    return response()->json(['message' => 'Category deleted successfully.']);
    // }
}
