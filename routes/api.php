<?php

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\ClusterController;
use App\Http\Controllers\ClusterPaymentController;
use App\Http\Controllers\ClusterPmtController;
use App\Http\Controllers\Dashboard\AccountController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\ClusterDemandController;
use App\Http\Controllers\Dashboard\DemandController as DashboardDemandController;
use App\Http\Controllers\Dashboard\ManageTransactionController;
use App\Http\Controllers\Dashboard\RateTransactionController;
use App\Http\Controllers\DemandController;
use App\Http\Controllers\DenialReasonController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\FrontpageController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PaymentZoneController;
use App\Http\Controllers\RateListController;
use App\Http\Controllers\RatepayerController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\TCController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WardController;
use App\Http\Controllers\WebhookController;
use App\Models\Ratepayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Public routes ********************************************************************
//*********************************************************************************** */
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// API-ID: PUBLIC-1 [Public Masters]
Route::get('/public-masters', [FrontpageController::class, 'getPublicMasters']);
// // API-ID: OPER-006 [Add Temp Entity]
// Route::post('new-entities', [TransactionController::class, 'createTempEntity']);  

//Admin Masters [completed] *************************************************************
//*
Route::middleware(['auth:sanctum', 'append-ulb', 'api', 'admin'])->prefix('admin/masters')->group(function () {
    // API-ID: ADMIN-007 [Create Category]
    Route::post('categories', [CategoryController::class, 'store']);
    // API-ID: ADMIN-008 [Create Sub Category]
    Route::post('sub-categories', [SubCategoryController::class, 'store']);
    // API-ID: ADMIN-009 [Create Rate List]
    Route::post('rate-list', [RateListController::class, 'store']);
    // API-ID: ADMIN-010 [Create Denial Reason]
    Route::post('denial-reasons', [DenialReasonController::class, 'store']);
    // API-ID: ADMIN-011 [Create Ward]
    Route::post('wards', [WardController::class, 'store']);
    // API-ID: ADMIN-012 [Create Payment Zone]
    Route::post('payment-zones', [PaymentZoneController::class, 'store']);
    // API-ID: ADMIN-015 [Update Category]
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    // API-ID: ADMIN-016 [Update sub category]
    Route::put('sub-categories/{id}', [SubCategoryController::class, 'update']);
    // API-ID: ADMIN-017 [Update Rate List]
    Route::put('rate-list/{id}', [RateListController::class, 'update']);
    // API-ID: ADMIN-018 [Update Denial Reason]
    Route::put('denial-reasons/{id}', [DenialReasonController::class, 'update']);
    // API-ID: ADMIN-019 [Update Ward]
    Route::put('wards/{id}', [WardController::class, 'update']);
    // API-ID: ADMIN-020 [Update Ward]
    Route::put('payment-zones/{id}', [PaymentZoneController::class, 'update']);
    // API-ID: ADMIN-038 [Update Ward]
    Route::get('all-users', [AuthController::class, 'getAllUsers']);
});

//Masters for everyone
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->prefix('masters')->group(function () {
    // API-ID: ADMIN-026 [List Category]
    Route::get('categories', [CategoryController::class, 'showAll']);
    // API-ID: ADMIN-027 [Get Category by ID]
    Route::get('categories/{id}', [CategoryController::class, 'show'])->where('id', '[0-9]+');
    // API-ID: ADMIN-028 [Get Sub Category by Category ID]
    Route::get('categories/{id}/sub-categories', [SubCategoryController::class, 'showAll'])->where('id', '[0-9]+');
    // API-ID: ADMIN-029 [Get Sub Category by ID]
    Route::get('sub-categories/{id}', [SubCategoryController::class, 'show']);
    // API-ID: ADMIN-030 [Get Rate List]
    Route::get('rate-list', [RateListController::class, 'showAll']);
    // API-ID: ADMIN-031 [Get Rate List by ID]
    Route::get('rate-list/{id}', [RateListController::class, 'show'])->where('id', '[0-9]+');
    // API-ID: ADMIN-032 [Get Denial Reasons]
    Route::get('denial-reasons', [DenialReasonController::class, 'showAll']);
    // API-ID: ADMIN-033 [Get Denial Reason By ID]
    Route::get('denial-reasons/{id}', [DenialReasonController::class, 'show'])->where('id', '[0-9]+');
    // API-ID: ADMIN-034 [Get Wards]
    Route::get('wards', [WardController::class, 'showAll']);
    // API-ID: ADMIN-035 [Get Ward by ID]
    Route::get('wards/{id}', [WardController::class, 'show'])->where('id', '[0-9]+');
    // API-ID: ADMIN-037 [Get Payment Zones]
    Route::get('payment-zones', [PaymentZoneController::class, 'showAll']);
    //Done
    Route::get('payment-zones/{id}', [PaymentZoneController::class, 'show']);
});

//Admin - API-ID: ADMIN-006
Route::middleware(['auth:sanctum', 'append-ulb', 'api', 'admin'])->prefix('admin')->group(function () {
    // API-ID: ADMIN-001 [Reset Password for Anyone]
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    // API-ID: ADMIN-002 [Update RateID of the Ratepayer]
    Route::put('ratepayers/update-rateid/{ratepayer_id}', [RatepayerController::class, 'updateRateID']);
    // API-ID: ADMIN-003 [Create new TC]
    Route::post('tc', [AuthController::class, 'createTC']);
    // API-ID: ADMIN-004 [Assign Zone to the TC]
    Route::post('tc/assign-zone', [TCController::class, 'assignZone']);
    // API-ID: ADMIN-005 [Generate Demand]
    Route::post('demands/generate', [DemandController::class, 'generateYearlyDemand']);            //Done
    // API-ID: ADMIN-006 [Generate Demand]
    Route::post('tc/revoke-zone', [TCController::class, 'revokeZone']);
    // API-ID: ADMIN-013 [List of current TCs]
    Route::get('tc/current', [TCController::class, 'showCurrentTCs']);
    // API-ID: ADMIN-036 [List of current TCs]
    Route::get('tc/all', [TCController::class, 'showAllTCs']);
    // API-ID: ADMIN-014 [List of Suspended TCs]
    Route::get('tc/suspended', [TCController::class, 'showSuspendedTCs']);
    // API-ID: ADMIN-023 [Suspend TC]
    Route::put('tc/{id}/suspend', [AuthController::class, 'suspendUser']);
    // API-ID: ADMIN-024 [Revoke Suspended TC]
    Route::put('tc/{id}/revoke-suspension', [AuthController::class, 'revokeUser']);
    // API-ID: ADMIN-025 [Save Profile Picture]
    Route::put('saveprofile-picture/{id}', [AuthController::class, 'setProfilePicture']); //Done
    // API-ID: ADMIN-039 [Add Entity and corresponding Ratepayer]
    Route::post('entities', [EntityController::class, 'storeWithRatePayers']);                                   //Done
    // API-ID: ADMIN-040 [Add Entity and corresponding Ratepayer]
    Route::post('clusters', [ClusterController::class, 'storeWithRatePayers']);                                  //Done
    // API-ID: ADMIN-041 [Add Entity and corresponding Ratepayer]
    Route::put('revoke-user/{user_id}', [AuthController::class, 'revokeUser']);                                  //Done
    // API-ID: ADMIN-042 [Activate Ratepayer]
    Route::post('activate-ratepayer', [RatepayerController::class, 'activateRatepayer']);                                  //Done
    // API-ID: ADMIN-043 [Activate Ratepayer]
    Route::post('deactivate-ratepayer', [RatepayerController::class, 'deactiavteRatepayer']);                                  //Done
    // API-ID: ADMIN-044 [Activate Ratepayer]
    Route::get('deactivated-ratepayer', [RatepayerController::class, 'showDeactiavtedRatepayer']);                                  //Done
    // API-ID: ADMIN-045 [All Masters]
    Route::get('all-masters', [MasterController::class, 'getAllMasters']);                                  //Done

    //****** Modify Transaction */
    Route::put('transactions', [EntityController::class, 'transactions/{id}']);

    //****** Generate Demand */
    // API-ID: ADMIN-046 [All Masters]
    Route::post('demands/generate/{id}', [DemandController::class, 'generateRatepayerDemands']);
    // API-ID: ADMIN-047 [Daily Transaction by Zone]
    Route::get('all-transactions', [TransactionController::class, 'getAllTransactions']);
    // API-ID: ADMIN-048 [Daily Transaction by Zone]
    Route::get('transaction-summary', [TransactionController::class, 'getTransactionSummary']);

    // API-ID: ADMIN-049 [Daily Transaction by Zone]
    Route::put('update-ratepayer/{id}', [RatepayerController::class, 'updateRatepayer']);
    
    //  -- Verify New Entities and Create Ratepayers
    //  -- Modify Transaction Records
    //  -- Verify Transactions (Comment on Transaction)
    //  -- Deactivate/ Activate Ratepayers

});



//Accounts
Route::middleware(['auth:sanctum', 'append-ulb', 'force-json', 'api'])->prefix('accounts')->group(function () {
    // API-ID: ACCOUNTS-001 [Daily Transaction by Zone]
    Route::get('daily-transactions', [AccountsController::class, 'dailyTransactions']);
    // API-ID: ACCOUNTS-002 [Payment Transactions by Zone]
    Route::get('payment-transactions', [AccountsController::class, 'paymentTransactions']);
    // API-ID: ACCOUNTS-003 [Verify Transaction]
    Route::post('verify-transactions', [AccountsController::class, 'verifyTransactions']);
    // API-ID: ACCOUNTS-004 [Uncleared cheques]
    Route::get('uncleared-cheques', [AccountsController::class, 'unclearedCheques']);
    // API-ID: ACCOUNTS-005 [ULB Demand Summary]
    Route::get('ulb-demandsummary', [AccountsController::class, 'currentDemandSummary']);
    // API-ID: ACCOUNTS-006 [ULB Demand Summary]
    Route::post('verify-cancellation', [AccountsController::class, 'verifyCancellation']);
    // API-ID: ACCOUNTS-007 [ULB Demand Summary]
    Route::get('cancelled-transactions', [AccountsController::class, 'showCancelledTransactions']);
    // API-ID: ACCOUNTS-006 [ULB Demand Summary]
    Route::post('cheque-realized', [AccountsController::class, 'realizeCheque']);
    //  -- Modify Payment Records
    //  -- Verify Cancellations
    //  -- Collect Cash
    //  -- Collect Cheque
    //  -- Cheque Verification
    //  -- Cheque Reconciliation and update payment
    //  -- UPI Verification and Reconciliation
    //  -- Initiate UPI Refund
    //  -- Waive off Demand against order

});

//Search
Route::middleware(['auth:sanctum', 'append-ulb', 'force-json', 'api'])->prefix('search')->group(function () {
    // API-ID: SEARCH-001 [List Zones allotted to the TC]
    Route::get('tc/zones', [TCController::class, 'showTCZones']);
    // API-ID: SEARCH-002 [Search TC Assigned zone by id]
    Route::get('zones/{id}', [TCController::class, 'getZoneByID']);
    // API-ID: SEARCH-003 [Search Nearby Ratepayers]
    Route::get('ratepayers/nearby', [RatepayerController::class, 'searchNearby']);
    // API-ID: SEARCH-004 [Search Ratepayers against multiple parameters]
    Route::get('ratepayers', [RatepayerController::class, 'deepSearch']);
    // API-ID: SEARCH-005 [Search Ratepayers by ID]
    Route::get('ratepayers/{id}', [RatepayerController::class, 'show'])->where('id', '[0-9]+');
    //Done

});

//Operations
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->prefix('operations')->group(function () {
    // API-ID: ADMIN-021 [Update Entity with Ratepayer]
    Route::put('entities/{id}', [EntityController::class, 'update']);                                   //Done
    // API-ID: ADMIN-022 [Update Cluster with Ratepayer]
    Route::put('clusters/{id}', [ClusterController::class, 'update']);                                  //Done
    // API-ID: OPER-002 [Ping from TC]
    Route::get('/ping', [AuthController::class, 'ping']);
    // API-ID: OPER-003 [Ping from TC]
    Route::get('clusters/{id}', [ClusterController::class, 'showById']);                                  //Done
    // API-ID: OPER-004 [Ping from TC]
    Route::get('clusters', [ClusterController::class, 'show']);                                  //Done
    // API-ID: OPER-005 [Set Ratepayer geolocation]
    Route::put('ratepayers/geo-location/{id}', [RatepayerController::class, 'updateGeoLocation']); //Done
   //  // API-ID: OPER-006 [Add Temp Entity]
    Route::post('new-entities', [TransactionController::class, 'createTempEntity']);                                   //Done
    // API-ID: OPER-007 [Change Password]
    Route::post('change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');
    // API-ID: OPER-008 [Entity Map]
    Route::post('entities/map', [EntityController::class, 'mapCluster']);
    // API-ID: OPER-009 [Entity Geolocation]
    Route::put('entities/geo-location/{id}', [EntityController::class, 'updateGeoLocation']);      //Done
    // API-ID: OPER-010 [Cluster Geolocation]
    Route::put('clusters/geo-location/{id}', [ClusterController::class, 'updateGeoLocation']);     //Done
    // API-ID: OPER-011 [Zone Transaction Summary]
    Route::get('zone-transaction-summary', [TransactionController::class, 'zoneTransactionSummary']);     //Done

    Route::put('entities/release', [EntityController::class, 'releaseCluster']);

    Route::get('payment-zones/ratepayers/search', [PaymentZoneController::class, 'showRatepayersPaginated']);                   //Done Need Modification

    Route::get('tc-dashboard', [TCController::class, 'tcDashboard']);                                  //Done

    //  Route::get('/ping/{id}', [AuthController::class, 'ping']);
    // API-ID: OPER-015 [Ping from TC]
    Route::get('clusters/{id}/ratepayers', [ClusterController::class, 'showClusterRatepayers']);                                  //Done

});

//Demand
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->prefix('demand')->group(function () {
    // API-ID: DEMAND-001 [Get pending demands of zone]
    Route::get('zone/{id}', [DemandController::class, 'zoneCurrentDemands']);

    // API-ID: DEMAND-003 [Get pending cluster demands of zone]
    Route::get('zone/cluster/{id}', [DemandController::class, 'zoneCurrentClusterDemands']);

    // API-ID: DEMAND-002 [Get pending demands of a ratepayer]
    Route::get('current/ratepayer/{id}', [DemandController::class, 'showRatepayerCurrentDemand']);

    // API-ID: DEMAND-002 [Get pending demands of a ratepayer]
    Route::get('current/cluster-ratepayer/{id}', [DemandController::class, 'showClusterRatepayerCurrentDemand']);

    // API-ID: DEMAND-004 [Get pending demands of a cluster where summary of all pending entity ratepayers is listed]
    Route::get('current/cluster/{id}', [DemandController::class, 'clusterDemands']);

    Route::get('demands/pending/{year}/{id}', [DemandController::class, 'showPendingDemands']);
    //Done

    // API-ID: DEMAND-005 [ZoneRatepayerDemands]
    Route::get('ratepayers', [DemandController::class, 'zoneCurrentDemands']);

    // API-ID: DEMAND-006 [Get pending demands of zone]
    Route::get('pending-demandnotices', [DemandController::class, 'pendingDemandNotices']);

    // API-ID: DEMAND-007 [printable demand notice]
    Route::get('demand-notice/{id}', [DemandController::class, 'printableDemandNotice']);


    //Done

});


//Discarded group
Route::middleware(['auth:sanctum', 'append-ulb', 'api', 'admin'])->prefix('admin/masters')->group(function () {

   // API-ID: DEMAND-001 [ClusterDemandController]
   Route::get('/clusterDemand', [ClusterDemandController::class, 'getClusterDemands']);

   // API-ID: DEMAND-002 [ClusterDemandController]
   Route::get('/ratepayersDemand', [ClusterDemandController::class, 'getRatepayersDemands']);
});


//Transactions
Route::middleware(['auth:sanctum', 'append-ulb', 'api'])->prefix('transactions')->group(function () {
    //Transactions - API-ID: TRAN-001
    Route::get('tran-summary', [TransactionController::class, 'tcTransactionSummary']);
    //Transactions - API-ID: TRAN-002
    Route::get('ratepayer/{id}', [TransactionController::class, 'ratepayerTransactions']);
    //Transactions - API-ID: TRAN-003
    Route::post('cash-payment', [TransactionController::class, 'cashPayment']);
    //Transactions - API-ID: TRAN-003
    Route::post('cluster-cash-payment', [ClusterPmtController::class, 'makePayment']);
    //Transactions - API-ID: TRAN-004
    Route::post('denial', [TransactionController::class, 'denial']);
    //Transactions - API-ID: TRAN-005
    Route::post('door-closed', [TransactionController::class, 'doorClosed']);
    //Transactions - API-ID: TRAN-006
    Route::post('deferred', [TransactionController::class, 'deferred']);
    //Transactions - API-ID: TRAN-007
   //  Route::post('cheque-collection', [TransactionController::class, 'chequeCollection']);
    //Transactions - API-ID: TRAN-008
    Route::post('cancel', [TransactionController::class, 'cancellation']);
    //Transactions - API-ID: TRAN-009
    Route::post('cluster-payment', [ClusterPaymentController::class, 'processClusterPayment']);
    //Transactions - API-ID: TRAN-010
    Route::get('receipt/{ratepayerId}', [TransactionController::class, 'getReceipt']);
    //Transactions - API-ID: TRAN-015
    Route::get('receipt-data/{tranId}', [TransactionController::class, 'getReceiptData']);
    //Transactions - API-ID: TRAN-011
    Route::get('ratepayer-transactions/{id}', [TransactionController::class, 'getTransactionsByRatepayer']);
    //Cheque Collection - API-ID: TRAN-012
    Route::post('save-cheque', [ChequeController::class, 'store']);
    //Payment Records - API-ID: TRAN-013
    Route::get('payment-records', [TransactionController::class, 'getTCPaymentRecords']);
    //Payment Records - API-ID: TRAN-014
    Route::post('create-ratepayer', [EntityController::class, 'addEntityWithDemands']);


    //Done

    //  Route::post('payments/upi-qr', [TransactionController::class, 'generateUpiQr']);
    //  Route::post('payments/verify-upi/{qrCodeId}', [TransactionController::class, 'verifyPayment']);
    //  Route::post('/gateway/webhook', [WebhookController::class, 'handleWebhook'])->withoutMiddleware(['csrf', 'web']);

    //  Route::post('transactions/create-order', [TransactionController::class, 'cashPayment']);
    //  Route::post('transactions/denial', [TransactionController::class, 'store']);
    //  Route::post('transactions/door-closed', [TransactionController::class, 'store']);
    //  Route::post('transactions/other', [TransactionController::class, 'store']);

    //  Route::get('transactions/{tran-date}', [TCController::class, 'store']);
    //  Route::get('transactions/payment/{tran-date}', [TCController::class, 'store']);
    //  Route::get('transactions/denial/{tran-date}', [TCController::class, 'store']);
    //  Route::get('transactions/no-show/{tran-date}', [TCController::class, 'store']);
    //  Route::get('transactions/deferred/{tran-date}', [TCController::class, 'store']);

    //  Route::put('transactions/cancel-pmt/{id}', [TCController::class, 'store']);

    //  Route::get('tc/dashboard', [TCController::class, 'dashboard']);
    //  Route::get('tc/profile', [TCController::class, 'store']);

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
   //  Route::get('demand/zone/{id}', [DenialReasonController::class, 'show']);
   //  Route::get('demand/ratepayer/{id}', DenialReasonController::class);
    // Route::get('demand/ward/{id}', DenialReasonController::class);

    // Route::get('transactions/entity/{id}', DenialReasonController::class);
    // Route::get('transactions/ratepayer/{id}', DenialReasonController::class);
    // Route::get('tc/date/{id}/{date}', DenialReasonController::class);
    // Route::get('tc/date/zone/{id}/{date}/{zone-id}}', DenialReasonController::class);
});

//Admin Dashboard *************************************************************
//*

Route::middleware(['auth:sanctum', 'append-ulb', 'api', 'admin'])->prefix('admin/masters')->group(function () {

    // API-ID: ADASH-001 [Admin Dashboard]
    // Route::post('/dashboard/admin', [AdminDashboardController::class, 'getTransactionDetails']);
    Route::post('/dashboard/admin', [AdminDashboardController::class, 'getDashboardData']);

    // // API-ID: ADASH-002 [Admin Dashboard]
    // Route::post('/overview-details', [AdminDashboardController::class, 'getOverviewDetails']);

    // // API-ID: ADASH-003 [Admin Dashboard]
    // Route::get('/fetch-transactions', [AdminDashboardController::class, 'fetchTransactions']);

    // // API-ID: ADASH-004 [Admin Dashboard]
    // Route::post('/fetch-insights', [AdminDashboardController::class, 'fetchInsights']);



    // Route::post('/dashboard/admin/denial', [AdminDashboardController::class, 'getTransactionData']);
    // // API-ID: ADMIN-007 [Create Category]
    // Route::post('dashboard/accountant', [CategoryController::class, 'store']);
    // // API-ID: ADMIN-007 [Create Category]
    // Route::post('dashboard/transactions', [CategoryController::class, 'store']);
});




Route::middleware(['auth:sanctum', 'append-ulb', 'api', 'admin'])->prefix('admin')->group(function () {

    // API-ID: MDASH-001 [Manager Dashboard]
    Route::get('/payment-trans', [ManageTransactionController::class, 'getTransactionData']);

    // API-ID: MDASH-002 [Active and Deactive Transaction]
    Route::post('/transaction/toggle', [ManageTransactionController::class, 'toggleTransactionStatus']);
});



Route::middleware(['auth:sanctum', 'append-ulb', 'api', 'admin'])->prefix('accounts')->group(function () {

   // API-ID: ACDASH-001 [Non Cash Verification]
   Route::get('/otherpayment-verification', [AccountController::class, 'getDateOtherPaymentsForVerification']);

   // API-ID: ACDASH-002 [Cheque Verification]
    Route::get('/cheque-verification', [AccountController::class, 'getDateChequeCollection']);

    // API-ID: ACDASH-003 [Cash Verification]
    Route::get('/cash-verification', [AccountController::class, 'getDateCashForVerification']);

    // API-ID: ACDASH-004 [Date Receipt Summary] -OK
    Route::get('/date-collection-summary', [AccountController::class, 'getDatePaymentSummary']);
});





Route::middleware(['auth:sanctum', 'append-ulb', 'api', 'admin'])->prefix('admin/masters')->group(function () {
    // API-ID: RTRANS-002 [RateTransaction]
    Route::post('/bill/admin/post', [RateTransactionController::class, 'postPaymentTransaction']);
});

Route::middleware(['auth:sanctum', 'api', 'admin'])->prefix('admin/masters')->group(function () {
    // API-ID: RTRANS-001 [RateTransaction]
    Route::post('/bill/admin', [RateTransactionController::class, 'getBillAmountModified']);
});

// API-ID: RTRANS-001 [RateTransaction]
Route::get('/tp/demand', [RateTransactionController::class, 'getCurrentBill']);
// API-ID: RTRANS-002 [RateTransaction]
Route::post('/tp/payment', [RateTransactionController::class, 'postPayment']);

//Payment Records - API-ID: OPEN-015 [Download PDF receipt by receipt number]
Route::get('receipts/{receipt_no}', [ReceiptController::class, 'generateReceipt'])
   ->name('receipts.download');

//Payment Receipt - API-ID: OPEN-016 [Download PDF receipt by receipt number]
Route::get('receipts/{receipt_no}/pdf', [ReceiptController::class, 'downloadPdf'])
   ->name('receipts.pdf');



Route::get('/debug-headers', function (Request $request) {
    return [
        'all_headers' => getallheaders(),
        'auth_key_direct' => $request->header('AUTH_KEY'),
        'server_vars' => array_filter($_SERVER, function ($key) {
            return strpos($key, 'HTTP_') === 0;
        }, ARRAY_FILTER_USE_KEY)
    ];
});

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route not found. Please check the URL and try again.',
    ], 404);
});


// Route::get('/welcome', [AdminDashboardController::class, 'welcome']);
