<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClusterController;
use App\Http\Controllers\DemandController;
use App\Http\Controllers\DenialReasonController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\PaymentZoneController;
use App\Http\Controllers\RateListController;
use App\Http\Controllers\RatepayerController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\TCController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WardController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Route::middleware(['auth:sanctum'])->group(function () {
//     Route::post('/logout', [AuthController::class, 'logout']);
// });

Route::get('ping', [EntityController::class, 'test']);

//Admin Masters
Route::middleware(['auth:sanctum', 'append-ulb', 'api', 'admin'])->prefix('admin/masters')->group(function () {
    // Master Entries
    Route::post('rate-list', [RateListController::class, 'store']);                                //Done
    Route::put('rate-list/{id}', [RateListController::class, 'update']);                           //Done

    Route::post('denial-reasons', [DenialReasonController::class, 'store']);                       //Done
    Route::put('denial-reasons/{id}', [DenialReasonController::class, 'update']);                  //Done

    Route::post('payment-zones', [PaymentZoneController::class, 'store']);                         //Done
    Route::put('payment-zones/{id}', [PaymentZoneController::class, 'update']);                    //Done

    Route::post('categories', [CategoryController::class, 'store']);                               //Done
    Route::put('categories/{id}', [CategoryController::class, 'update']);                          //Done

    Route::post('categories/{id}/sub-categories', [SubCategoryController::class, 'store']);                        //Done
    Route::put('sub-categories/{id}', [SubCategoryController::class, 'update']);                   //Done

    Route::post('wards', [WardController::class, 'store']);                                        //Done
    Route::put('wards/{id}', [WardController::class, 'update']);                                   //Done

});

//Masters
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->prefix('masters')->group(function () {
    Route::get('categories', [CategoryController::class, 'showAll']);                                    //Done
    Route::get('categories/{id}', [CategoryController::class, 'show'])->where('id', '[0-9]+');           //Done

    Route::get('categories/{id}/sub-categories', [SubCategoryController::class, 'showAll'])->where('id', '[0-9]+');    //Done
    Route::get('sub-categories/{id}', [SubCategoryController::class, 'show']);          //Done

    Route::get('rate-list', [RateListController::class, 'showAll']);                               //Done
    Route::get('rate-list/{id}', [RateListController::class, 'show'])->where('id', '[0-9]+');      //Done

    Route::get('denial-reasons', [DenialReasonController::class, 'showAll']);                      //Done
    Route::get('denial-reasons/{id}', [DenialReasonController::class, 'show'])->where('id', '[0-9]+');   //Done
    Route::get('wards', [WardController::class, 'showAll']);                                       //Done
    Route::get('wards/{id}', [WardController::class, 'show'])->where('id', '[0-9]+');              //Done
    Route::get('payment-zones', [PaymentZoneController::class, 'showAll']);                              //Done
    Route::get('payment-zones/{id}', [PaymentZoneController::class, 'show']);                              //Done

    Route::get('tc/{id}', [TCController::class, 'store']);
});

//Admin
Route::middleware(['auth:sanctum', 'append-ulb', 'api', 'admin'])->prefix('admin')->group(function () {
    Route::put('ratepayers/{id}', [ClusterController::class, 'update']);                           //Done

    //****** Update RateID of the Ratepayer */
    Route::put('ratepayers/update-rateid/{rateid}/{id}', [ClusterController::class, 'update']);

    //****** Modify Transaction */
    Route::put('transactions', [EntityController::class, 'transactions/{id}']);

    //****** Generate Demand */
    Route::post('demands/mergeandclean', [DemandController::class, 'mergeAndCleanCurrentDemand']); //Done
    Route::post('demands/merge', [DemandController::class, 'mergeCurrentDemand']);                 //Done
    Route::post('demands/clean', [DemandController::class, 'cleanCurrentDemand']);                 //Done
    Route::post('demands/generate', [DemandController::class, 'generateYearlyDemand']);            //Done
    Route::post('demands/generate/{id}', [DemandController::class, 'generateRatepayerDemands']);

    //****** See a list of all TCs */
    Route::get('tc', [TCController::class, 'store']);

    //****** See a list of Active TCs */
    Route::get('tc/current-tc', [TCController::class, 'store']);

    //****** See a list of Suspended TCs */
    Route::get('tc/suspended', [TCController::class, 'store']);

    Route::get('tc/suspend/{id}', [TCController::class, 'store']);

});

//Search
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->prefix('search')->group(function () {
    Route::get('ratepayers', [RatepayerController::class, 'deepSearch']);
    Route::get('ratepayers/{id}', [RatepayerController::class, 'show']);
    Route::get('ratepayers/payment-zone/{id}', [RatepayerController::class, 'showZoneRatepayers']);
    Route::get('ratepayers/payment-zone/paginated/{id}/{page_size}', [RatepayerController::class, 'showZoneRatepayersPaginated']);
    Route::get('ratepayers/nearby', [RatepayerController::class, 'searchNearby']);

    //  Route::get('entities/search', [EntityController::class, 'deepSearch']);                        //Done
    //  Route::get('payment-zones/ratepayers/search', [PaymentZoneController::class, 'showRatepayersPaginated']);                   //Done Need Modification
    //  Route::get('entities/{id}', [EntityController::class, 'show'])->where('id', '[0-9]+');         //Done
    //  Route::get('entities/nearby', [EntityController::class, 'searchNearby']);
    //  Route::get('clusters', [ClusterController::class, 'showAll']);
    //  Route::get('clusters/nearby', [ClusterController::class, 'searchNearby']);
    //  Route::get('clusters/{id}', [ClusterController::class, 'show'])->where('id', '[0-9]+');        //Done
    //  Route::get('ratepayers/{id}', [RatepayerController::class, 'show'])->where('id', '[0-9]+');    //Done

});

//Operations
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->prefix('operations')->group(function () {
    Route::get('payment-zones/ratepayers/search', [PaymentZoneController::class, 'showRatepayersPaginated']);                   //Done Need Modification
    Route::post('entities', [EntityController::class, 'storeWithRatePayers']);                                   //Done
    Route::put('entities/{id}', [EntityController::class, 'update']);                                   //Done
    // Entity can be mapped to a cluster. Mapping automatically disables entity's default ratepayer
    Route::post('entities/map', [EntityController::class, 'mapCluster']);
    // Entity can be released from cluster thus reenabling ratepayer
    Route::put('entities/release', [EntityController::class, 'releaseCluster']);
    Route::put('entities/geo-location/{id}', [EntityController::class, 'updateGeoLocation']);      //Done

    Route::post('clusters', [ClusterController::class, 'storeWithRatePayers']);                                  //Done
    Route::put('clusters/geo-location/{id}', [ClusterController::class, 'updateGeoLocation']);     //Done
    Route::put('ratepayers/geo-location/{id}', [RatepayerController::class, 'updateGeoLocation']); //Done

});

//Demand
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->prefix('demand')->group(function () {
    Route::get('demands/{year}/{id}', [DemandController::class, 'showYearlyDemand']);
    Route::get('demands/pending/{year}/{id}', [DemandController::class, 'showPendingDemands']);
    Route::get('demands/current/{id}', [DemandController::class, 'showCurrentDemand']);

});

//Transactions
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->prefix('transactions')->group(function () {
    Route::post('transactions/payment/cash', [TransactionController::class, 'cashPayment']);
    Route::post('payments/upi-qr', [TransactionController::class, 'generateUpiQr']);
    Route::post('payments/verify-upi/{qrCodeId}', [TransactionController::class, 'verifyPayment']);
    Route::post('/gateway/webhook', [WebhookController::class, 'handleWebhook'])->withoutMiddleware(['csrf', 'web']);

    Route::post('transactions/create-order', [TransactionController::class, 'cashPayment']);
    Route::post('transactions/denial', [TransactionController::class, 'store']);
    Route::post('transactions/door-closed', [TransactionController::class, 'store']);
    Route::post('transactions/deferred', [TransactionController::class, 'store']);
    Route::post('transactions/other', [TransactionController::class, 'store']);

    Route::get('transactions/{tran-date}', [TCController::class, 'store']);
    Route::get('transactions/payment/{tran-date}', [TCController::class, 'store']);
    Route::get('transactions/denial/{tran-date}', [TCController::class, 'store']);
    Route::get('transactions/no-show/{tran-date}', [TCController::class, 'store']);
    Route::get('transactions/deferred/{tran-date}', [TCController::class, 'store']);

    Route::put('transactions/cancel-pmt/{id}', [TCController::class, 'store']);

    Route::get('tc/dashboard', [TCController::class, 'store']);
    Route::get('tc/profile', [TCController::class, 'store']);

});

//Ratepayers
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->prefix('ratepayers')->group(function () {
    Route::get('ratepayer/reputation/{ratepayer_d}', [TCController::class, 'store']);
    Route::get('ratepayer/zone/entity/search', [TCController::class, 'store']);
    Route::get('ratepayer/zone/cluster', [TCController::class, 'store']);
    Route::get('ratepayer/zone/ratepayers/search', [TCController::class, 'store']);

    Route::post('/payment-links/create', [TransactionController::class, 'createPaymentLink']);
    Route::delete('/payment-links/{paymentLinkId}', [TransactionController::class, 'cancelPaymentLink']);
    Route::get('/payment-links/{paymentLinkId}', [TransactionController::class, 'fetchPaymentLinkDetails']);

});

Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->group(function () {

    //************* Search Payment zone wise ratepayers */ */
    //  Route::get('payment-zones/ratepayers/entities/paginated/{zoneId}/{pagination}', [PaymentZoneController::class, 'showRatepayerEntitiesPaginated']);   //Done Delete
    //  Route::get('payment-zones/ratepayers/clusters/paginated/{zoneId}/{pagination}', [PaymentZoneController::class, 'showRatepayerClustersPaginated']);   //Done Delete

    //************** Create New Entity */
    //************** Show an Entity */

    //Ratepayer Controller endpoints

    // Demands

    // Transactions

    //Use PaymentService

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

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route not found. Please check the URL and try again.',
    ], 404);
});
