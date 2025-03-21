<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DenialReason;
use App\Models\PaymentZone;
use App\Models\RateList;
use App\Models\SubCategory;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MasterController extends Controller
{
    public function getAllMasters(Request $request)
    {
        try {
            $ulbId = $request->ulb_id;

            $denialReasons = DenialReason::where('ulb_id', $ulbId)->get();

            $rateLists = RateList::where('ulb_id', $ulbId)->get();
            $wards = Ward::where('ulb_id', $ulbId)->get();
            $paymentZone = PaymentZone::where('ulb_id', $ulbId)->get();
            // $categories = SubCategory::join('categories', 'sub_categories.category_id', '=', 'categories.id')
            //     ->where('categories.ulb_id', $ulbId)
            //     ->select('sub_categories.*', 'categories.category as category_name')
            //     ->get();


            $categories = Category::with('subCategories')
                ->where('ulb_id', 1)
                ->get();
            //Ctrl+Alt+4 ₹
            $subcategories = SubCategory::all();

            $response = [
                'denialReasons' => $denialReasons,
                'rateList' => $rateLists,
                'wards' => $wards,
                'paymentZone' => $paymentZone,
                'categories' => $categories,
                'subcategories' => $subcategories,
            ];

            return format_response(
                'Zone Transaction Summary',
                $response,
                Response::HTTP_OK
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
}
