<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PaymentZone;
use App\Models\SubCategory;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FrontpageController extends Controller
{
   public function getPublicMasters(Request $request)
   {
       try {
           $ulbId = $request->ulb_id;

           $wards = Ward::where('ulb_id', $ulbId)->get();
           $paymentZone = PaymentZone::where('ulb_id', $ulbId)->get();
           $categories = Category::with('subCategories')
               ->where('ulb_id', 1)
               ->get();
           //Ctrl+Alt+4 ₹
           $subcategories = SubCategory::all();

           $response = [
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
