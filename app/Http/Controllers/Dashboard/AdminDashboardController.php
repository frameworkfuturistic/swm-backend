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

    public function getTransactionDetails(Request $request)
    {
        try {

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
            $totalTransactions = DB::table('transactions')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('transactions.event_time', [$fromDate, $toDate]);
                })
                ->when($zone !== 'All Zones', function ($query) use ($zone) {
                    return $query->where('clusters.cluster_name', $zone);
                })
                ->count();





            // Total Payments (Filtered by Date and Zone)
            $totalPayments = DB::table('payments')
                ->join('transactions', 'transactions.payment_id', '=', 'payments.id')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('payments.created_at', [$fromDate, $toDate]);
                })
                ->when($zone !== 'All Zones', function ($query) use ($zone) {
                    return $query->where('clusters.cluster_name', $zone);
                })
                ->sum('payments.amount');






            // Completed Payments (Filtered by Date and Zone)
            $completedPayments = DB::table('payments')
                ->join('transactions', 'transactions.payment_id', '=', 'payments.id')
                ->where('payments.payment_status', 'completed')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('payments.created_at', [$fromDate, $toDate]);
                })
                ->count();





            // Pending Payments (Filtered by Date and Zone)
            $pendingPayments = DB::table('payments')
                ->join('transactions', 'transactions.payment_id', '=', 'payments.id')
                ->where('payments.payment_status', 'pending')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('payments.created_at', [$fromDate, $toDate]);
                })
                ->count();





            // lastTotalTransactions (Filtered by Date and Zone)

            $lastTotalTransactions = DB::table('transactions')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('transactions.event_time', [$fromDate, $toDate]);
                })
                ->when($zone && $zone !== 'All Zones', function ($query) use ($zone) {
                    return $query->where('clusters.cluster_name', $zone);
                })
                ->where('transactions.paymentStatus', 'completed')
                ->count();





            // lastTotalPayments (Filtered by Date and Zone)

            $lastTotalPayments = DB::table('payments')
                ->join('transactions', 'transactions.payment_id', '=', 'payments.id')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('payments.created_at', [$fromDate, $toDate]);
                })
                ->when($zone && $zone !== 'All Zones', function ($query) use ($zone) {
                    return $query->where('clusters.cluster_name', $zone);
                })
                ->sum('payments.amount');




            // lastPendingPayments (Filtered by Date and Zone)

            $lastPendingPayments = DB::table('payments')
                ->join('transactions', 'transactions.payment_id', '=', 'payments.id')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('payments.created_at', [$fromDate, $toDate]);
                })
                ->when($zone && $zone !== 'All Zones', function ($query) use ($zone) {
                    return $query->where('clusters.cluster_name', $zone);
                })
                ->where('payments.payment_status', 'pending')
                ->count();





            // Monthly Overview (Filtered by Date and Zone)
            $monthlyOverview = DB::table('transactions')
                ->join('payments', 'transactions.payment_id', '=', 'payments.id')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->select(
                    DB::raw('MONTH(transactions.event_time) as month_number'),
                    DB::raw('MONTHNAME(transactions.event_time) as month_name'),
                    DB::raw('COUNT(*) as transactions'),
                    DB::raw('SUM(IFNULL(payments.amount, 0)) as amount'),
                    DB::raw('SUM(CASE WHEN payments.payment_status = "completed" THEN IFNULL(payments.amount, 0) ELSE 0 END) as total_collected_payments') // Total payments where status is 'completed'
                )
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('transactions.event_time', [$fromDate, $toDate]);
                })
                ->when($zone !== 'All Zones', function ($query) use ($zone) {
                    return $query->where('clusters.cluster_name', $zone);
                })
                ->groupBy(DB::raw('MONTH(transactions.event_time), MONTHNAME(transactions.event_time)'))
                ->having(DB::raw('SUM(IFNULL(payments.amount, 0))'), '>', 0)
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => date('M', mktime(0, 0, 0, $item->month_number, 1)),
                        'transactions' => $item->transactions,
                        'amount' => $item->amount,
                        'payments' => $item->total_collected_payments
                    ];
                });




            // Event Type Overview (Filtered by Event Type and Date)
            $eventTypeOverview = DB::table('transactions')
                ->select('event_type', DB::raw('COUNT(*) as count'))
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('event_time', [$fromDate, $toDate]);
                })
                ->when($eventType !== 'All Events', function ($query) use ($eventType) {
                    return $query->where('event_type', $eventType);
                })
                ->groupBy('event_type')
                ->having(DB::raw('COUNT(*)'), '>', 0)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => ucwords(strtolower(str_replace('-', ' ', $item->event_type))),
                        'count' => $item->count,
                        'color' => $this->getEventTypeColor($item->event_type)
                    ];
                });





            // Payment Mode Status (Filtered by Date and Zone)
            $paymentModeStatus = DB::table('payments')
                ->join('transactions', 'transactions.payment_id', '=', 'payments.id')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->select(
                    'payments.payment_mode',
                    DB::raw('SUM(CASE WHEN payments.payment_status = "completed" THEN 1 ELSE 0 END) as completed'),
                    DB::raw('SUM(CASE WHEN payments.payment_status = "pending" THEN 1 ELSE 0 END) as pending'),
                    DB::raw('SUM(CASE WHEN payments.payment_status = "failed" THEN 1 ELSE 0 END) as failed'),
                    DB::raw('SUM(CASE WHEN payments.payment_status = "refunded" THEN 1 ELSE 0 END) as refunded')
                )
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('payments.created_at', [$fromDate, $toDate]);
                })
                ->when($zone !== 'All Zones', function ($query) use ($zone) {
                    return $query->where('clusters.cluster_name', $zone);
                })
                ->groupBy('payments.payment_mode')
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





            // Fetch Transactions (Filtered by Date and Zone)
            $transactions = DB::table('transactions')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->leftJoin('payments', 'transactions.payment_id', '=', 'payments.id')
                ->select(
                    DB::raw("CONCAT('TRX1000', transactions.id) AS id"),
                    DB::raw("CONCAT('ULB-', transactions.ulb_id) AS ulb"),
                    DB::raw("CONCAT('RATEPAYER100', transactions.ratepayer_id) AS ratepayer"),
                    DB::raw("CONCAT('PMT-2000', payments.id) AS paymentId"),
                    'transactions.event_time as eventTime',
                    'transactions.event_type as eventType',
                    'payments.payment_mode as paymentMode',
                    'payments.payment_status as paymentStatus',
                    'payments.amount',
                    'transactions.verification_date as verificationDate',
                    'transactions.cancellation_date as cancellationDate'
                )
                // Only apply date filters if both dates are provided
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('transactions.event_time', [$fromDate, $toDate]);
                })
                // Ensure the 'zone' filter works properly, even if it's not provided
                ->when($zone !== 'All Zones', function ($query) use ($zone) {
                    return $query->where('clusters.cluster_name', $zone);
                })
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id, // This will be in the format 'TRX1000-{id}'
                        'ulb' => $item->ulb,
                        'ratepayer' => $item->ratepayer,
                        'eventTime' => $item->eventTime,
                        'eventType' => $item->eventType,
                        'paymentId' => $item->paymentId,
                        'paymentMode' => $item->paymentMode,
                        'paymentStatus' => $item->paymentStatus,
                        'amount' => $item->amount,
                        'verificationDate' => $item->verificationDate,
                        'cancellationDate' => $item->cancellationDate
                    ];
                });




            // Alert Data
            $alertData = $this->getAlertData($request);

            // Cluster Data
            $clusterData = $this->getClusterData();

            // Cancellation Data
            $cancellationData = $this->getCancellationData();

            // Denial Data
            $denialData = $this->getDenialData();

            // Collector Data
            $collectorData = $this->getCollectorData();

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
                    'overview' => [
                        'transactionsData' => $monthlyOverview,
                        'eventType' => $eventTypeOverview,
                        'paymentModeStatus' => $paymentModeStatus,
                        'clusters' => $clusterData
                    ],
                    'transactions' => $transactions,
                    'insights' => [
                        'cancellationData' => $cancellationData,
                        'denialData' => $denialData,
                        'collectorData' => $collectorData,
                        'alert' => $alertData
                    ]
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



    private function getClusterData()
    {
        // Using Eloquent models to ensure proper joins and data retrieval
        $clusterData = Cluster::select(
            'clusters.id as cluster_id',
            'clusters.cluster_name as zone',
            DB::raw('COUNT(current_transactions.id) as transactions'),
            DB::raw('SUM(CASE WHEN current_transactions.event_type = "PAYMENT" THEN 1 ELSE 0 END) as payments'),
            DB::raw('SUM(CASE WHEN payments.amount IS NOT NULL THEN payments.amount ELSE 0 END) as amount'),
            DB::raw('CASE
            WHEN clusters.cluster_name = "North Cluster" THEN "#4CAF50"
            WHEN clusters.cluster_name = "South Cluster" THEN "#2196F3"
            WHEN clusters.cluster_name = "East Cluster" THEN "#FF9800"
            WHEN clusters.cluster_name = "West Cluster" THEN "#9C27B0"
            ELSE "#000000" END as color')
        )
            ->leftJoin('current_transactions', 'clusters.id', '=', 'current_transactions.cluster_id')
            ->leftJoin('payments', 'payments.id', '=', 'current_transactions.payment_id')
            ->groupBy('clusters.id', 'clusters.cluster_name')
            ->get();

        // Check if we received any data
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
                'color' => $item->color,
            ];
        });
    }


    // Helper method to get cancellation data
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



    // Helper method to get denial data
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



    // Helper method to get collector data
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


    // Helper method to get Alert data
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


    // Utility method for color mapping

    //EventType
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



    //Cluster
    private function getClusterColor($clusterName)
    {
        $colors = [
            'North Cluster' => '#4CAF50',
            'South Cluster' => '#2196F3',
            'East Cluster' => '#FF9800',
            'West Cluster' => '#9C27B0'
        ];

        return $colors[$clusterName] ?? '#607D8B';
    }



    //Cancellation
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



    //Denial
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



    // Helper function to calculate the response time
    private function responseTime()
    {
        return microtime(true) - LARAVEL_START;
    }

    // Helper function to format the response
    private function format_response($message, $data = null, $status_code = 200, $extra = [])
    {
        return response()->json([
            'status' => $status_code,
            'message' => $message,
            'data' => $data,
            'extra' => $extra
        ]);
    }

    // Helper function to format error messages
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
