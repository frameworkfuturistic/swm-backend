<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CurrentTransaction;
use App\Models\Payment;
use App\Models\Cluster;
use App\Models\DenialReason;
use App\Models\User;
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
            // Validate incoming data (make sure all necessary fields are provided)
            $validatedData = $request->validate([
                'fromDate' => 'required|date',
                'toDate' => 'required|date',
                'zone' => 'required|string',
                'eventType' => 'required|string',
            ]);

            // Extract data from the request
            $fromDate = $validatedData['fromDate'];
            $toDate = $validatedData['toDate'];
            $zone = $validatedData['zone'];
            $eventType = $validatedData['eventType'];

            // Total Transactions and Payments (Filtered by Date and Zone)
            $totalTransactions = DB::table('transactions')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->whereBetween('transactions.event_time', [$fromDate, $toDate])
                ->where('clusters.cluster_name', $zone)
                ->count();

            $totalPayments = DB::table('payments')
                ->join('transactions', 'transactions.payment_id', '=', 'payments.id')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->whereBetween('payments.created_at', [$fromDate, $toDate])
                ->sum('payments.amount');

            $completedPayments = DB::table('payments')
                ->join('transactions', 'transactions.payment_id', '=', 'payments.id')
                ->where('payments.payment_status', 'completed')
                ->whereBetween('payments.created_at', [$fromDate, $toDate])
                ->count();

            $pendingPayments = DB::table('payments')
                ->join('transactions', 'transactions.payment_id', '=', 'payments.id')
                ->where('payments.payment_status', 'pending')
                ->whereBetween('payments.created_at', [$fromDate, $toDate])
                ->count();

            // Monthly Overview (Filtered by Date and Zone)
            $monthlyOverview = DB::table('transactions')
                ->join('payments', 'transactions.payment_id', '=', 'payments.id')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->select(
                    DB::raw('MONTH(transactions.event_time) as month_number'),
                    DB::raw('MONTHNAME(transactions.event_time) as month_name'),
                    DB::raw('COUNT(*) as transactions'),
                    DB::raw('SUM(IFNULL(payments.amount, 0)) as amount')
                )
                ->whereBetween('transactions.event_time', [$fromDate, $toDate])
                ->where('clusters.cluster_name', $zone)
                ->groupBy(DB::raw('MONTH(transactions.event_time), MONTHNAME(transactions.event_time)'))
                ->having(DB::raw('SUM(IFNULL(payments.amount, 0))'), '>', 0)
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => date('M', mktime(0, 0, 0, $item->month_number, 1)),
                        'transactions' => $item->transactions,
                        'amount' => $item->amount
                    ];
                });

            // Event Type Overview (Filtered by Event Type and Date)
            $eventTypeOverview = DB::table('transactions')
                ->select('event_type', DB::raw('COUNT(*) as count'))
                ->where('event_type', $eventType)
                ->whereBetween('event_time', [$fromDate, $toDate])
                ->groupBy('event_type')
                ->having(DB::raw('COUNT(*)'), '>', 0)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => $item->event_type,
                        'count' => $item->count
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
                ->whereBetween('payments.created_at', [$fromDate, $toDate])
                ->where('clusters.cluster_name', $zone)
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

            // Cluster Data
            $clusterData = $this->getClusterData();

            // Fetch Transactions (Filtered by Date and Zone)
            $transactions = DB::table('transactions')
                ->join('clusters', 'transactions.cluster_id', '=', 'clusters.id')
                ->leftJoin('payments', 'transactions.payment_id', '=', 'payments.id')
                ->select(
                    'transactions.id',
                    'transactions.ulb_id as ulb',
                    'transactions.ratepayer_id as ratepayer',
                    'transactions.event_time as eventTime',
                    'transactions.event_type as eventType',
                    'payments.id as paymentId',
                    'payments.payment_mode as paymentMode',
                    'payments.payment_status as paymentStatus',
                    'payments.amount',
                    'transactions.verification_date as verificationDate',
                    'transactions.cancellation_date as cancellationDate'
                )
                ->whereBetween('transactions.event_time', [$fromDate, $toDate])
                ->where('clusters.cluster_name', $zone)
                ->get()
                ->map(function ($item) {
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
                        'cancellationDate' => $item->cancellationDate
                    ];
                });

            // Cancellation Data
            $cancellationData = $this->getCancellationData();

            // Denial Data
            $denialData = $this->getDenialData();

            // Collector Data
            $collectorData = $this->getCollectorData();

            // Query execution time
            $queryRunTime = $this->responseTime();

            // Capture apiid and Device Name from request
            $apiid = $request->input('apiid', $request->header('apiid', null));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            }

            // Return the response using the format_response helper
            return $this->format_response(
                'Transaction Overview fetched successfully',
                [
                    'totalTransactions' => $totalTransactions,
                    'totalPayments' => $totalPayments,
                    'completedPayments' => $completedPayments,
                    'pendingPayments' => $pendingPayments,
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
                        'alert' => [] // You can customize this part based on your alerts data.
                    ]
                ],
                200, // HTTP Status code
                [
                    'apiid' => $apiid,
                    'version' => '1.0',
                    'queryRunTime' => $queryRunTime,
                    'route' => 'getTransactionOverview',
                    'device' => $request->header('Device-Name', 'Unknown Device')
                ]
            );
        } catch (\Exception $e) {
            // Query execution time
            $queryRunTime = $this->responseTime();

            // Capture apiid and Device Name from request
            $apiid = $request->input('apiid', $request->header('apiid', null));

            // Handle exception and return error response
            return $this->responseMsgs(
                'Error occurred while fetching transaction overview: ' . $e->getMessage(),
                null,
                500, // Internal server error status code
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

    // Helper method to get cluster data
    private function getClusterData()
    {
        return Cluster::select('cluster_name as area')
            ->selectRaw('COUNT(current_transactions.id) as transactions')
            ->selectRaw('SUM(CASE WHEN current_transactions.event_type = "PAYMENT" THEN 1 ELSE 0 END) as payments')
            ->selectRaw('SUM(payments.amount) as amount')
            ->leftJoin('current_transactions', 'clusters.id', '=', 'current_transactions.cluster_id')
            ->leftJoin('payments', 'current_transactions.payment_id', '=', 'payments.id')
            ->groupBy('clusters.id', 'clusters.cluster_name')
            ->havingRaw('SUM(payments.amount) > 0')
            ->get()
            ->map(function ($item) {
                return [
                    'area' => $item->area,
                    'transactions' => $item->transactions,
                    'payments' => $item->payments,
                    'amount' => $item->amount,
                    'color' => $this->getClusterColor($item->area)
                ];
            });
    }

    // Helper method to get cancellation data
    private function getCancellationData()
    {
        return DenialReason::select('reason as type')
            ->selectRaw('COUNT(*) as value')
            ->where('reason', 'CANCELLATION')
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
            ->where('reason', 'DENIAL')
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
        return User::select('name as type')
            ->selectRaw('COUNT(current_transactions.id) as transactions')
            ->selectRaw('SUM(CASE WHEN current_transactions.event_type = "PAYMENT" THEN 1 ELSE 0 END) as payments')
            ->selectRaw('SUM(payments.amount) as amount')
            ->leftJoin('current_transactions', 'users.id', '=', 'current_transactions.tc_id')
            ->leftJoin('payments', 'current_transactions.payment_id', '=', 'payments.id')
            ->where('users.role', 'COLLECTOR')
            ->groupBy('users.id', 'users.name')
            ->get();
    }

    // Utility method for color mapping
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

    private function getCancellationColor($reason)
    {
        $colors = [
            'Incorrect Amount' => '#F44336',
            'Wrong Address' => '#FF9800',
            'Duplicate Payment' => '#2196F3',
            'Service Issue' => '#9C27B0',
            'Other' => '#607D8B'
        ];

        return $colors[$reason] ?? '#607D8B';
    }

    private function getDenialColor($reason)
    {
        $colors = [
            'Unable to Pay' => '#F44336',
            'Disputed Bill' => '#FF9800',
            'Service Not Received' => '#2196F3',
            'Already Paid' => '#9C27B0',
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
