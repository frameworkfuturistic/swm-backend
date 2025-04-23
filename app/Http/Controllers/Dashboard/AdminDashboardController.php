<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cluster;
use App\Models\DenialReason;
use App\Models\User;
use App\Models\Alert;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{

    // API-ID: ADASH-001 [Admin Dashboard]


    public function getTransactionDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fromDate' => 'nullable|date',
                'toDate' => 'nullable|date|after_or_equal:fromDate',
                'zone' => 'nullable|string|max:255',
                'eventType' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                    'success' => false,
                ], 422);
            }


            $fromDate = $request->input('fromDate', null);
            $toDate = $request->input('toDate', null);
            $zone = $request->input('zone', 'All Zones');
            $eventType = $request->input('eventType', 'All Events');

            // Log the input values for debugging
            Log::info('Input Values:', [
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'zone' => $zone,
                'eventType' => $eventType
            ]);


            // Total Transactions (Filtered by Date and Zone)
            $totalTransactions = DB::table('current_transactions')
                ->selectRaw('COUNT(id) as totaltransactions')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween(DB::raw('DATE(event_time)'), [$fromDate, $toDate]);
                })
                ->value('totaltransactions');





            // Total Payments (Filtered by Date and Zone)
            $totalPayments = DB::table('payments')
                ->selectRaw('SUM(amount) as totalPayments')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween(DB::raw('DATE(payment_date)'), [$fromDate, $toDate]);
                })
                ->value('totalPayments');





            // Completed Payments (Filtered by Date and Zone)
            $completedPayments = DB::table('payments')
                ->selectRaw('COUNT(*) as completedPayments')
                ->where('payment_status', 'COMPLETED')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween(DB::raw('DATE(payment_date)'), [$fromDate, $toDate]);
                })
                ->value('completedPayments');





            // Pending Payments (Filtered by Date and Zone)
            $pendingPayments = DB::table('payments')
                ->selectRaw('COUNT(*) as pendingPayments')
                ->where('payment_status', 'PENDING')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween(DB::raw('DATE(payment_date)'), [$fromDate, $toDate]);
                })
                ->value('pendingPayments');




            // lastTotalTransactions (Filtered by Date and Zone)

            $lastTotalTransactions = DB::table('current_transactions')
                ->selectRaw('COUNT(id) as lastTotalTransactions')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween(DB::raw('DATE(event_time)'), [$fromDate, $toDate]);
                })
                ->value('lastTotalTransactions');




            // lastTotalPayments (Filtered by Date and Zone)

            $lastTotalPayments = DB::table('payments')
                ->selectRaw('SUM(amount) as lastTotalPayments')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween(DB::raw('DATE(payment_date)'), [$fromDate, $toDate]);
                })
                ->value('lastTotalPayments');




            // lastPendingPayments (Filtered by Date and Zone)

            $lastPendingPayments = DB::table('payments')
                ->selectRaw('COUNT(*) as lastPendingPayments')
                ->where('payment_status', 'PENDING')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween(DB::raw('DATE(payment_date)'), [$fromDate, $toDate]);
                })
                ->value('lastPendingPayments');

            // Query execution time
            $queryRunTime = $this->responseTime();

            // Capture apiid and Device Name from request
            $apiid = $request->input('apiid', $request->header('apiid', 'ADASH-001'));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            }

            // Return the response using the format_response helper
            return $this->format_response(
                'Transaction Overview fetched successfully',
                [

                    'totalTransactions' => $totalTransactions,
                    'lastTotalTransactions' => $lastTotalTransactions,
                    'totalPayments' => $totalPayments,
                    'lastTotalPayments' => $lastTotalPayments,
                    'completedPayments' => $completedPayments,
                    'pendingPayments' => $pendingPayments,
                    'lastPendingPayments' => $lastPendingPayments,


                ],
                true,
                [
                    'apiid' => $apiid,
                    'version' => '1.0',
                    'queryRunTime' => $queryRunTime,
                    'route' => 'getTransactionOverview',
                    'device' => $request->header('Device-Name', 'Unknown Device')
                ]
            );
        } catch (\Exception $e) {
            $queryRunTime = $this->responseTime();
            $apiid = $request->input('apiid', $request->header('apiid', null));
            return $this->responseMsgs(
                'Error occurred while fetching transaction overview: ' . $e->getMessage(),
                null,
                false,
                [
                    'apiid' => $apiid,
                    'version' => '1.0',
                    'queryRunTime' => $queryRunTime,
                    'route' => 'getTransactionOverview',
                    'device' => $request->header('Device-Name', 'Unknown Device')
                ]
            );
        }
    }




    // API-ID: ADASH-002 [Admin Dashboard]

    public function getOverviewDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fromDate' => 'nullable|date',
                'toDate' => 'nullable|date|after_or_equal:fromDate',
                'zone' => 'nullable|string|max:255',
                'eventType' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                    'success' => false,
                ], 422);
            }


            $fromDate = $request->input('fromDate', null);
            $toDate = $request->input('toDate', null);
            $zone = $request->input('zone', 'All Zones');
            $eventType = $request->input('eventType', 'All Events');

            // Log the input values for debugging
            Log::info('Input Values:', [
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'zone' => $zone,
                'eventType' => $eventType
            ]);

            // Fetch the required data
            $monthlyOverview = $this->getMonthlyOverview($fromDate, $toDate, $zone);
            $eventTypeOverview = $this->getEventTypeOverview($fromDate, $toDate, $eventType);
            $paymentModeStatus = $this->getPaymentModeStatus($fromDate, $toDate, $zone);
            $clusterData = $this->getClusterData();


            // Capture apiid and Device Name from request
            $apiid = $request->input('apiid', $request->header('apiid', 'ADASH-002'));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            }

            // Query execution time
            $queryRunTime = $this->responseTime();

            // Return the response using the format_response helper
            return $this->format_response(
                'Overview details fetched successfully',
                [

                    'transactionsData' => $monthlyOverview,
                    'eventType' => $eventTypeOverview,
                    'paymentModeStatus' => $paymentModeStatus,
                    'clusters' => $clusterData

                ],
                true,
                [
                    'apiid' => $apiid,
                    'version' => '1.0',
                    'queryRunTime' => $queryRunTime,
                    'route' => 'getOverviewDetails',
                    'device' => $request->header('Device-Name', 'Unknown Device')
                ]
            );
        } catch (\Exception $e) {
            $queryRunTime = $this->responseTime();
            $apiid = $request->input('apiid', $request->header('apiid', null));
            return $this->responseMsgs(
                'Error occurred while fetching overview details: ' . $e->getMessage(),
                null,
                false,
                [
                    'apiid' => $apiid,
                    'version' => '1.0',
                    'queryRunTime' => $queryRunTime,
                    'route' => 'getOverviewDetails',
                    'device' => $request->header('Device-Name', 'Unknown Device')
                ]
            );
        }
    }




    private function getMonthlyOverview($fromDate, $toDate, $zone)
    {
        return DB::table('current_transactions as ct')
            ->join('payments as p', 'ct.id', '=', 'p.tran_id')
            ->select(
                // DB::raw('MONTH(ct.event_time) as month_number'),
                DB::raw('MONTHNAME(ct.event_time) as month'),
                DB::raw('COUNT(ct.id) as transactions'),
                DB::raw("SUM(IF(ct.event_type = 'PAYMENT', 1, 0)) as payments"),
                DB::raw('SUM(p.amount) as amount')
            )
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                return $query->whereBetween(DB::raw('DATE(ct.event_time)'), [$fromDate, $toDate]);
            })
            ->groupBy(DB::raw('MONTH(ct.event_time), MONTHNAME(ct.event_time)'))
            ->orderBy(DB::raw('MONTH(ct.event_time)'))
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'transactions' => $item->transactions,
                    'payments' => $item->payments,
                    'amount' => $item->amount,
                ];
            });
    }



    private function getEventTypeOverview($fromDate, $toDate, $eventType)
    {
        return DB::table('current_transactions')
            ->select(
                'event_type as type',
                DB::raw("SUM(IF(event_type = 'PAYMENT', 1, 0)) as count"),
                DB::raw("SUM(IF(event_type = 'DENIAL', 1, 0)) as denials"),
                DB::raw("SUM(IF(event_type = 'DOOR-CLOSED', 1, 0)) as doorclosed"),
                DB::raw("SUM(IF(event_type = 'DEFERRED', 1, 0)) as reschedules"),
                DB::raw("SUM(IF(event_type = 'CHEQUE', 1, 0)) as cheques")
            )
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                return $query->whereBetween(DB::raw('DATE(event_time)'), [$fromDate, $toDate]);
            })
            ->when($eventType !== 'All Events', function ($query) use ($eventType) {
                return $query->where('event_type', $eventType);
            })
            ->groupBy('event_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => ucwords(strtolower(str_replace('-', ' ', $item->type))),
                    'count' => $item->count,
                    'denials' => $item->denials,
                    'doorclosed' => $item->doorclosed,
                    'reschedules' => $item->reschedules,
                    'cheques' => $item->cheques,
                    'color' => $this->getEventTypeColor($item->type)
                ];
            });
    }



    private function getEventTypeColor($eventType)
    {
        $colors = [
            'PAYMENT' => '#4CAF50',
            'DENIAL' => '#F44336',
            'DOOR-CLOSED' => '#2196F3',
            'DEFERRED' => '#FF9800',
            'CHEQUE' => '#9C27B0',
            'OTHER' => '#607D8B'
        ];

        return $colors[$eventType] ?? '#607D8B';
    }

    private function getPaymentModeStatus($fromDate, $toDate, $zone)
    {
        return DB::table('payments')
            ->select(
                'payment_mode',
                DB::raw("SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN payment_status = 'PENDING' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN payment_status = 'refunded' THEN 1 ELSE 0 END) as refunded"),
                DB::raw("SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed")
            )
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                return $query->whereBetween(DB::raw('DATE(payment_date)'), [$fromDate, $toDate]);
            })
            // The zone filter is removed to match the query, but can be re-added if needed.
            ->groupBy('payment_mode')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->payment_mode,
                    'completed' => $item->completed,
                    'pending' => $item->pending,
                    'failed' => $item->failed,
                    'refunded' => $item->refunded
                ];
            });
    }




    private function getClusterData()
    {

        $clusterData = DB::table('clusters as cl')
            ->join('payment_zones as pz', 'cl.ulb_id', '=', 'pz.ulb_id')
            ->leftJoin('payments as p', 'p.cluster_id', '=', 'cl.id')
            ->leftJoin('current_transactions as ct', 'ct.cluster_id', '=', 'cl.id')
            ->select(
                DB::raw("CASE
                        WHEN pz.payment_zone = 1 THEN 'East Cluster'
                        WHEN pz.payment_zone = 2 THEN 'West Cluster'
                        WHEN pz.payment_zone = 3 THEN 'North Cluster'
                        WHEN pz.payment_zone = 4 THEN 'South Cluster'
                       
                    END AS zone"),
                DB::raw('SUM(p.amount) AS amount'),
                DB::raw('COUNT(p.id) AS payments'),
                DB::raw('COUNT(ct.id) AS transactions')
            )
            ->groupBy('pz.payment_zone', 'cl.ulb_id')
            ->get();


        if ($clusterData->isEmpty()) {
            Log::debug('No cluster data found.');
        }

        // Return the data in the format you want
        return $clusterData->map(function ($item) {
            return [
                'zone' => $item->zone,
                'transactions' => $item->transactions,
                'payments' => $item->payments,
                'amount' => $item->amount,
                // 'color' => $item->color,
            ];
        });
    }






    // API-ID: ADASH-003 [Admin Dashboard]
    public function fetchTransactions(Request $request)
    {
        try {
            // Extract input parameters from query string
            $fromDate = $request->query('fromDate', null);
            $toDate = $request->query('toDate', null);
            $zone = $request->query('zone', 'All Zones');
            $eventType = $request->query('eventType', 'All Events');

            // Log the input values for debugging
            Log::info('Input Values:', [
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'zone' => $zone,
                'eventType' => $eventType
            ]);

            // Build the query with logging to capture the SQL query
            $query = DB::table('current_transactions')
                ->join('payment_zones as pz', 'current_transactions.ulb_id', '=', 'pz.ulb_id')
                ->leftJoin('payments', 'current_transactions.payment_id', '=', 'payments.id')
                ->select(
                    DB::raw("CONCAT('TRX1000', current_transactions.id) AS id"),
                    DB::raw("CONCAT('ULB-', current_transactions.ulb_id) AS ulb"),
                    DB::raw("CONCAT('RATEPAYER100', current_transactions.ratepayer_id) AS ratepayer"),
                    DB::raw("CONCAT('PMT-2000', payments.id) AS paymentId"),
                    'current_transactions.event_time as eventTime',
                    'current_transactions.event_type as eventType',
                    'payments.payment_mode as paymentMode',
                    'payments.payment_status as paymentStatus',
                    'payments.amount',
                    'current_transactions.verification_date as verificationDate',
                    'current_transactions.cancellation_date as cancellationDate',
                    'pz.payment_zone as zone'
                );

            // Add date filter if both fromDate and toDate are provided
            if ($fromDate && $toDate) {
                $query->whereBetween('current_transactions.event_time', [$fromDate, $toDate]);
            }

            // Add zone filter
            if ($zone !== 'All Zones') {
                $query->where('pz.payment_zone', $zone);
            }

            // Add eventType filter
            if ($eventType !== 'All Events') {
                $query->where('current_transactions.event_type', $eventType);
            }

            // Log the generated SQL query for debugging
            Log::info('Generated SQL Query: ' . $query->toSql());

            // Execute the query and map the results
            $transactions = $query->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'ulb' => $item->ulb,
                    'ratepayer' => $item->ratepayer,
                    'eventTime' => $item->eventTime,
                    'eventType' => $item->eventType,
                    'paymentId' => $item->paymentId,
                    'paymentMode' => $item->paymentMode,
                    'paymentStatus' => $item->paymentStatus,
                    'amount' => $item->amount,
                    'verificationDate' => $item->verificationDate,
                    'cancellationDate' => $item->cancellationDate,
                    'zone' => $item->zone
                ];
            });

            // Capture API ID and Device Name
            $apiid = $request->query('apiid', $request->header('apiid', 'ADASH-003'));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            }

            // Query execution time
            $queryRunTime = $this->responseTime();

            // Return the response using the format_response helper
            return response()->json([
                'status' => true,
                'message' => 'Transactions fetched successfully',
                'data' => $transactions->toArray(),
                'extra' => [
                    'apiid' => $apiid,
                    'version' => '1.0',
                    'queryRunTime' => $queryRunTime,
                    'route' => 'fetchTransactions',
                    'device' => $request->header('Device-Name', 'Unknown Device')
                ]
            ]);
        } catch (\Exception $e) {
            // Log the exception for debugging
            Log::error('Error in fetching transactions', ['error' => $e->getMessage()]);

            // Query execution time
            $queryRunTime = $this->responseTime();
            $apiid = $request->query('apiid', $request->header('apiid', null));

            return response()->json([
                'status' => false,
                'message' => 'Error occurred while fetching transactions: ' . $e->getMessage(),
                'data' => null,
                'extra' => [
                    'apiid' => $apiid,
                    'version' => '1.0',
                    'queryRunTime' => $queryRunTime,
                    'route' => 'fetchTransactions',
                    'device' => $request->header('Device-Name', 'Unknown Device')
                ]
            ]);
        }
    }






    // API-ID: ADASH-004 [Admin Dashboard]
    public function fetchInsights(Request $request)
    {
        try {
            // Extract input parameters
            $fromDate = $request->input('fromDate', null);
            $toDate = $request->input('toDate', null);
            $zone = $request->input('zone', 'All Zones');
            $eventType = $request->input('eventType', 'All Events');

            // Log the input values for debugging
            Log::info('Input Values:', [
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'zone' => $zone,
                'eventType' => $eventType
            ]);

            // Fetch Insights Data
            $alertData = $this->getAlertData($request);
            $cancellationData = $this->getCancellationData();
            $denialData = $this->getDenialData();
            $collectorData = $this->getCollectorData();




            // Capture apiid and Device Name from request
            $apiid = $request->input('apiid', $request->header('apiid', 'ADASH-004'));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            }

            // Query execution time
            $queryRunTime = $this->responseTime();

            // Return the response using the format_response helper
            return $this->format_response(
                'Insights fetched successfully',
                [

                    'cancellationData' => $cancellationData,
                    'denialData' => $denialData,
                    'collectorData' => $collectorData,
                    'alert' => $alertData

                ],
                true,
                [
                    'apiid' => $apiid,
                    'version' => '1.0',
                    'queryRunTime' => $queryRunTime,
                    'route' => 'fetchInsights',
                    'device' => $request->header('Device-Name', 'Unknown Device')
                ]
            );
        } catch (\Exception $e) {
            $queryRunTime = $this->responseTime();
            $apiid = $request->input('apiid', $request->header('apiid', null));
            return $this->responseMsgs(
                'Error occurred while fetching insights: ' . $e->getMessage(),
                null,
                false,
                [
                    'apiid' => $apiid,
                    'version' => '1.0',
                    'queryRunTime' => $queryRunTime,
                    'route' => 'fetchInsights',
                    'device' => $request->header('Device-Name', 'Unknown Device')
                ]
            );
        }
    }


    // // Helper method to get denial data
    private function getDenialData()
    {
        return DenialReason::select('reason as type')
            ->selectRaw('COUNT(*) as value')
            ->groupBy('reason')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'value' => $item->value,
                    'color' => $this->getDenialColor($item->type)
                ];
            });
    }



    // // Helper method to get collector data
    private function getCollectorData()
    {
        return User::select(DB::raw('"Tax Collector" as type'), 'users.name as collector_name')
            ->selectRaw('COUNT(transactions.id) as transactions')
            ->selectRaw('SUM(CASE WHEN transactions.event_type = "PAYMENT" THEN 1 ELSE 0 END) as payments')
            ->selectRaw('SUM(payments.amount) as amount')
            ->leftJoin('transactions', 'users.id', '=', 'transactions.tc_id')
            ->leftJoin('payments', 'transactions.payment_id', '=', 'payments.id')
            ->where('users.role', 'TAX_COLLECTOR')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type . ' ' . $item->collector_name,
                    'transactions' => $item->transactions,
                    'payments' => $item->payments,
                    'amount' => $item->amount
                ];
            });
    }


    // // Helper method to get Alert data
    private function getAlertData(Request $request)
    {
        Log::info('Alert Data:', $request->all());
        $defaultAlerts = [
            [
                'title' => 'System Update',
                'message' => 'System maintenance is scheduled for tonight from 12:00 AM to 2:00 AM.',
                'dateTime' => now()->toDateTimeString(),
                'priority' => 'High',
                'category' => 'Failed',
            ],
            [
                'title' => 'New User Registration',
                'message' => 'A new user has registered in the system.',
                'dateTime' => now()->toDateTimeString(),
                'priority' => 'Low',
                'category' => 'Unverified',
            ],
            [
                'title' => 'Payment Received',
                'message' => 'A new payment has been received successfully.',
                'dateTime' => now()->toDateTimeString(),
                'priority' => 'Medium',
                'category' => 'Delayed',
            ]
        ];

        if (!$request->has('title') && !$request->has('message')) {
            Log::info('Returning default alerts.');
            return $defaultAlerts;
        }
        $alert = [
            'title' => $request->input('title', ''),
            'message' => $request->input('message', ''),
            'dateTime' => $request->input('dateTime', now()->toDateTimeString()),
            'priority' => $request->input('priority', 'Medium'),
            'category' => $request->input('category', 'Unverified'),
        ];
        Log::info('Final Alert Data:', $alert);
        return [$alert];
    }



    // // Helper method to get cancellation data
    private function getCancellationData()
    {
        return DenialReason::select('reason as type', DB::raw('COUNT(*) as value'))
            ->groupBy('reason')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'value' => $item->value,
                    'color' => $this->getCancellationColor($item->type)
                ];
            });
    }


    private function getCancellationColor($reason)
    {
        $colors = [
            'Unauthorized usaget' => '#F44336',
            'Misrepresentation of facts' => '#FF9800',
            'Duplicate application' => '#2196F3',
            'Unverified identity' => '#9C27B0',
            'Other' => '#607D8B'
        ];

        return $colors[$reason] ?? '#607D8B';
    }


    private function getDenialColor($reason)
    {
        $colors = [
            'Taxpayer unavailable' => '#F44336',
            'Refused to pay' => '#FF9800',
            'Disputed bill amount' => '#2196F3',
            'Door closed' => '#9C27B0',
            'Other' => '#607D8B'
        ];

        return $colors[$reason] ?? '#607D8B';
    }




    // // Helper function to calculate the response time
    private function responseTime()
    {
        return microtime(true) - LARAVEL_START;
    }

    // // Helper function to format the response
    private function format_response($message, $data = null, $status_code = 200, $extra = [])
    {
        return response()->json([
            'status' => $status_code,
            'message' => $message,
            'data' => $data,
            'extra' => $extra
        ]);
    }

    // // Helper function to format error messages
    private function responseMsgs($message, $data = null, $status_code = 500, $extra = [])
    {
        return response()->json([
            'status' => $status_code,
            'message' => $message,
            'data' => $data,
            'extra' => $extra
        ]);
    }
}
