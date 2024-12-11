<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BillController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ZoneController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClusterController;
use App\Http\Controllers\DemandController;
use App\Http\Controllers\DenialReasonController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\PaymentZoneController;
use App\Http\Controllers\RateListController;
use App\Http\Controllers\RatepayerController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WardController;
use App\Http\Controllers\WebhookController;
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
    Route::post('rate-list', [RateListController::class, 'store']);                                //Done
    Route::put('rate-list/{id}', [RateListController::class, 'update']);                           //Done

    Route::post('denial-reasons', [DenialReasonController::class, 'store']);                       //Done
    Route::put('denial-reasons/{id}', [DenialReasonController::class, 'update']);                  //Done

    Route::post('payment-zones', [PaymentZoneController::class, 'store']);                         //Done
    Route::put('payment-zones/{id}', [PaymentZoneController::class, 'update']);                    //Done

    Route::post('categories', [CategoryController::class, 'store']);                               //Done
    Route::put('categories/{id}', [CategoryController::class, 'update']);                          //Done

    Route::post('wards', [WardController::class, 'store']);                                        //Done
    Route::put('wards/{id}', [WardController::class, 'update']);                                   //Done

    Route::post('sub-categories', [SubCategoryController::class, 'store']);                        //Done
    Route::put('sub-categories/{id}', [SubCategoryController::class, 'update']);                   //Done

    Route::put('entities/{id}', [EntityController::class, 'update']);                              //Done
    Route::put('clusters/{id}', [ClusterController::class, 'update']);                             //Done
    Route::put('ratepayers/{id}', [ClusterController::class, 'update']);                           //Done

    Route::put('ratepayers/update-rateid/{rateid}/{id}', [ClusterController::class, 'update']);

    //Transactions
    Route::put('transactions', [EntityController::class, 'transactions/{id}']);

    //Demand Generation
    Route::post('demands/mergeandclean', [DemandController::class, 'mergeAndCleanCurrentDemand']); //Done
    Route::post('demands/merge', [DemandController::class, 'mergeCurrentDemand']);                 //Done
    Route::post('demands/clean', [DemandController::class, 'cleanCurrentDemand']);                 //Done
    Route::post('demands/generate', [DemandController::class, 'generateYearlyDemand']);            //Done
    Route::post('demands/generate/{id}', [DemandController::class, 'generateRatepayerDemands']);

});

Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->group(function () {
    //Masters
    Route::get('rate-list', [RateListController::class, 'showAll']);                               //Done
    Route::get('rate-list/{id}', [RateListController::class, 'show'])->where('id', '[0-9]+');      //Done

    Route::get('denial-reasons', [DenialReasonController::class, 'showAll']);                      //Done
    Route::get('denial-reasons/{id}', [DenialReasonController::class, 'show'])->where('id', '[0-9]+');   //Done

    Route::get('wards', [WardController::class, 'showAll']);                                       //Done
    Route::get('wards/{id}', [WardController::class, 'show'])->where('id', '[0-9]+');              //Done

    Route::get('payment-zones', [PaymentZoneController::class, 'showAll']);                              //Done
    Route::get('payment-zones/ratepayers/paginated/{zoneId}/{pagination}', [PaymentZoneController::class, 'showRatepayersPaginated']);                   //Done
    Route::get('payment-zones/ratepayers/entities/paginated/{zoneId}/{pagination}', [PaymentZoneController::class, 'showRatepayerEntitiesPaginated']);   //Done
    Route::get('payment-zones/ratepayers/clusters/paginated/{zoneId}/{pagination}', [PaymentZoneController::class, 'showRatepayerClustersPaginated']);   //Done
    Route::get('payment-zones/{id}', [PaymentZoneController::class, 'show'])->where('id', '[0-9]+');     //Done

    Route::get('categories/{id}', [CategoryController::class, 'show'])->where('id', '[0-9]+');           //Done
    Route::get('categories', [CategoryController::class, 'showAll']);                                    //Done

    //  Entity Controller endpoints
    Route::post('entities', [EntityController::class, 'store']);                                   //Done
    Route::post('entities/with-ratepayers', [EntityController::class, 'storeWithRatePayers']);     //Done
    Route::get('entities/paginated/{paginate}', [EntityController::class, 'showAll']);             //Done
    Route::get('entities/{id}', [EntityController::class, 'show'])->where('id', '[0-9]+');         //Done
    Route::put('entities/geo-location/{id}', [EntityController::class, 'updateGeoLocation']);      //Done
    Route::get('entities/search', [EntityController::class, 'deepSearch']);                        //Done

    //  Cluster Controller endpoints
    Route::post('clusters', [ClusterController::class, 'store']);                                  //Done
    Route::get('clusters', [ClusterController::class, 'showAll']);                                 //Done
    Route::get('clusters/{id}', [ClusterController::class, 'show'])->where('id', '[0-9]+');        //Done
    Route::post('clusters/with-ratepayers', [ClusterController::class, 'storeWithRatePayers']);    //Done
    Route::put('clusters/geo-location/{id}', [ClusterController::class, 'updateGeoLocation']);     //Done
    Route::get('clusters/search', [ClusterController::class, 'deepSearch']);

    //Ratepayer Controller endpoints
    Route::post('ratepayers', [RatepayerController::class, 'store']);                              //Done
    Route::get('ratepayers/{id}', [RatepayerController::class, 'show'])->where('id', '[0-9]+');    //Done
    Route::put('ratepayers/geo-location/{id}', [RatepayerController::class, 'updateGeoLocation']); //Done
    Route::get('ratepayers/search', [RatepayerController::class, 'deepSearch']);

    // Demands
    Route::get('demands/{year}/{id}', [DemandController::class, 'showYearlyDemand']);
    Route::get('demands/pending/{year}/{id}', [DemandController::class, 'showPendingDemands']);
    Route::get('demands/current/{id}', [DemandController::class, 'showCurrentDemand']);

    // Transactions
    Route::post('transactions/payment', [TransactionController::class, 'cashPayment']);
    Route::post('transactions/denial', [TransactionController::class, 'store']);
    Route::post('transactions/door-closed', [TransactionController::class, 'store']);
    Route::post('transactions/deferred', [TransactionController::class, 'store']);
    Route::post('transactions/other', [TransactionController::class, 'store']);

    //Current Payments

    //TC activities
    //Get Todays Transactions
    //Get Date Transactions
    //Get Payment Transactions
    //Get Denial Transactions
    //Get Cancelled Transactions
    //Get DoorClosed Transactions
    //Get Deferred Transactions
    //Get Other Transactions
    //Update Transaction
    //Generate Payment Link
    //Get Nearby Entity Ratepayers
    //Get Nearby Cluster Ratepayers
    //Get Todays Deferred Payments
    //Get Diamond Ratepayers
    //Get Green Ratepayers
    //Get Silver Ratepayers
    //Get Red Ratepayers
    //Update Ratepayer Grade

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

Route::post('/webhooks/razorpay', [WebhookController::class, 'handle']);

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route not found. Please check the URL and try again.',
    ], 404);
});
