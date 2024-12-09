<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BillController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ZoneController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClusterController;
use App\Http\Controllers\DenialReasonController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\PaymentZoneController;
use App\Http\Controllers\RateListController;
use App\Http\Controllers\RatepayerController;
use App\Http\Controllers\SubCategoryController;
use Illuminate\Support\Facades\Route;

// No need for /api prefix here as it's automatically added
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

//  Route::get('/entities', [EntityController::class, 'index']);
//  Route::post('/bills', [BillController::class, 'store']);
//  Route::post('/payments', [PaymentController::class, 'store']);
//  Route::get('/zones', [ZoneController::class, 'index']);

// Route::get('entities/show/{id}', [EntityController::class, 'show']);
Route::get('test', [EntityController::class, 'test']);

Route::middleware(['auth:sanctum', 'append-ulb', 'api', 'admin'])->group(function () {
    // Master Entries
    Route::post('rate-list', [RateListController::class, 'store']);                 //Done
    Route::put('rate-list/{id}', [RateListController::class, 'update']);            //Done

    Route::post('denial-reasons', [DenialReasonController::class, 'store']);        //Done
    Route::put('denial-reasons/{id}', [DenialReasonController::class, 'update']);   //Done

    Route::post('payment-zones', [PaymentZoneController::class, 'store']);          //Done
    Route::put('payment-zones/{id}', [PaymentZoneController::class, 'update']);     //Done

    Route::post('categories', [CategoryController::class, 'store']);                //Done
    Route::put('categories/{id}', [CategoryController::class, 'update']);           //Done

    Route::post('sub-categories', [SubCategoryController::class, 'store']);         //Done
    Route::put('sub-categories/{id}', [SubCategoryController::class, 'update']);    //Done

    Route::put('entities/{id}', [EntityController::class, 'update']);
    Route::put('clusters/{id}', [ClusterController::class, 'update']);
    Route::put('ratepayers/{id}', [ClusterController::class, 'update']);

    Route::put('ratepayers/update-rateid/{rateid}/{id}', [ClusterController::class, 'update']);

    //Transactions
    Route::put('transactions', [EntityController::class, 'transactions/{id}']);

    //Demand Generation
    Route::post('demands/generate/{year}', [EntityController::class, 'store']);
    Route::post('demands/generate/ratepayer/{year}/{startmonth}/{ratepayerid}', [EntityController::class, 'store']);

});

Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->group(function () {
    //Masters
    Route::get('rate-list', [RateListController::class, 'showAll']);                            //Done
    Route::get('rate-list/{id}', [RateListController::class, 'show'])->where('id', '[0-9]+');   //Done

    Route::get('denial-reasons', [DenialReasonController::class, 'showAll']);                   //Done
    Route::get('denial-reasons/{id}', [DenialReasonController::class, 'show'])->where('id', '[0-9]+');   //Done

    Route::get('payment-zones', [PaymentZoneController::class, 'showAll']);                              //Done
    Route::get('payment-zones/ratepayers/paginated/{zoneId}/{pagination}', [PaymentZoneController::class, 'showRatepayersPaginated']);                   //Done
    Route::get('payment-zones/ratepayers/entities/paginated/{zoneId}/{pagination}', [PaymentZoneController::class, 'showRatepayerEntitiesPaginated']);   //Done
    Route::get('payment-zones/ratepayers/clusters/paginated/{zoneId}/{pagination}', [PaymentZoneController::class, 'showRatepayerClustersPaginated']);   //Done
    Route::get('payment-zones/{id}', [PaymentZoneController::class, 'show'])->where('id', '[0-9]+');     //Done

    Route::get('categories/{id}', [CategoryController::class, 'show'])->where('id', '[0-9]+');           //Done
    Route::get('categories', [CategoryController::class, 'showAll']);                                    //Done

    //  Entity Controller endpoints
    Route::get('entities/paginated/{paginate}', [EntityController::class, 'showAll']);
    Route::get('entities/{id}', [EntityController::class, 'show'])->where('id', '[0-9]+');
    Route::post('entities', [EntityController::class, 'store']);
    Route::post('entities/with-ratepayers', [EntityController::class, 'storeWithRatePayers']);
    Route::put('entities/geo-location/{id}', [EntityController::class, 'updateGeoLocation']);
    Route::get('entities/search', [EntityController::class, 'deepSearch']);
    Route::get('entities/{id}', [EntityController::class, 'show'])->where('id', '[0-9]+');

    //  Cluster Controller endpoints
    Route::get('clusters/show', [ClusterController::class, 'show']);
    Route::post('clusters', [ClusterController::class, 'store']);
    Route::post('clusters/with-ratepayers', [ClusterController::class, 'storeWithRatePayers']);
    Route::put('clusters/geo-location/{id}', [ClusterController::class, 'updateGeoLocation']);
    Route::get('clusters/search', [ClusterController::class, 'deepSearch']);
    Route::get('clusters/{id}', [ClusterController::class, 'show'])->where('id', '[0-9]+');

    //Ratepayer Controller endpoints
    Route::get('ratepayers/show', [RatepayerController::class, 'show']);
    Route::post('ratepayers', [RatepayerController::class, 'store']);
    Route::put('ratepayers/geo-location/{id}', [RatepayerController::class, 'updateGeoLocation']);
    Route::get('ratepayers/search', [RatepayerController::class, 'deepSearch']);
    Route::get('ratepayers/{id}', [RatepayerController::class, 'show'])->where('id', '[0-9]+');

    //Current Demands

    //Current Transactions
    Route::post('transactions', [EntityController::class, 'store']);

    //Current Payments

    //Demands

    //Transactions

    //Payments

    //TC activities

});

// //update transactions
// Route::middleware(['auth:sanctum','append-ulb','api'])->group(function () {
//    Route::post('generate-demand', DenialReasonController::class);
//    Route::post('transaction', DenialReasonController::class);
// });

//reporting
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->group(function () {
    Route::get('demand/zone/{id}', [DenialReasonController::class, 'show']);
    // Route::get('demand/ratepayer/{id}', DenialReasonController::class);
    // Route::get('demand/ward/{id}', DenialReasonController::class);

    // Route::get('transactions/entity/{id}', DenialReasonController::class);
    // Route::get('transactions/ratepayer/{id}', DenialReasonController::class);
    // Route::get('tc/date/{id}/{date}', DenialReasonController::class);
    // Route::get('tc/date/zone/{id}/{date}/{zone-id}}', DenialReasonController::class);
});

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route not found. Please check the URL and try again.',
    ], 404);
});
