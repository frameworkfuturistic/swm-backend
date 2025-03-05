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

class AdminDashboardController extends Controller
{



    public function getTransactionDetails(Request $request)
    {
        // Validate input parameters
        $validatedData = $request->validate([
            'fromDate' => 'nullable|date',
            'toDate' => 'nullable|date',
            'zone' => 'nullable|string',
            'eventType' => 'nullable|in:PAYMENT,DENIAL,DOOR-CLOSED,DEFERRED,CHEQUE,OTHER'
        ]);

        // Start building the base query
        $query = CurrentTransaction::with([
            'ulb',
            'ratepayer',
            'tc',
            'payment',
            'cancellationReason',
            'verifiedBy',
            'cluster'
        ]);

        // Apply date filters if provided
        if (!empty($validatedData['fromDate']) && !empty($validatedData['toDate'])) {
            $query->whereBetween('event_time', [
                Carbon::parse($validatedData['fromDate']),
                Carbon::parse($validatedData['toDate'])
            ]);
        }

        // Apply zone filter if provided (assuming zone is related to clusters)
        if (!empty($validatedData['zone'])) {
            $query->whereHas('cluster', function ($q) use ($validatedData) {
                $q->where('cluster_name', $validatedData['zone']);
            });
        }

        // Apply event type filter if provided
        if (!empty($validatedData['eventType'])) {
            $query->where('event_type', $validatedData['eventType']);
        }

        // Calculate overview statistics
        $totalTransactions = $query->count();
        $lastTotalTransactions = CurrentTransaction::where('event_time', '<', now()->subMonth())->count();

        // Optimize sum of payments for the current period
        $totalPayments = Payment::whereIn('current_transactions.event_type', ['PAYMENT'])
            ->join('current_transactions', 'current_transactions.payment_id', '=', 'payments.id')
            ->sum('payments.amount');

        $lastTotalPayments = Payment::where('created_at', '<', now()->subMonth())->sum('amount');

        $completedPayments = CurrentTransaction::where('event_type', 'PAYMENT')
            ->where('is_verified', 1)
            ->count();

        $pendingPayments = CurrentTransaction::where('event_type', 'PAYMENT')
            ->where('is_verified', 0)
            ->count();

        $lastPendingPayments = CurrentTransaction::where('event_type', 'PAYMENT')
            ->where('is_verified', 0)
            ->where('event_time', '<', now()->subMonth())
            ->count();

        // Prepare monthly overview data
        $monthlyOverview = $this->getMonthlyTransactionOverview();

        // Prepare event type breakdown
        $eventTypeBreakdown = $this->getEventTypeBreakdown();

        // Prepare payment mode status
        $paymentModeStatus = $this->getPaymentModeStatus();

        // Prepare cluster data
        $clusterData = $this->getClusterData();

        // Fetch paginated transactions
        $transactions = $query->paginate(50);

        // Prepare insights
        $insights = [
            'cancellationData' => $this->getCancellationData(),
            'denialData' => $this->getDenialData(),
            'collectorData' => $this->getCollectorData(),
        ];

        return response()->json([
            'totalTransactions' => $totalTransactions,
            'lastTotalTransactions' => $lastTotalTransactions,
            'totalPayments' => $totalPayments,
            'lastTotalPayments' => $lastTotalPayments,
            'completedPayments' => $completedPayments,
            'pendingPayments' => $pendingPayments,
            'lastPendingPayments' => $lastPendingPayments,
            'overview' => [
                'transactionsData' => $monthlyOverview,
                'eventType' => $eventTypeBreakdown,
                'paymentModeStatus' => $paymentModeStatus,
                'clusters' => $clusterData
            ],
            'transactions' => $transactions,
            'insights' => $insights
        ]);
    }

    // Helper methods to generate specific data sections
    private function getMonthlyTransactionOverview()
    {
        // We will join `payments` with `current_transactions` and sum the payment amounts.
        return CurrentTransaction::select(
            DB::raw('MONTH(event_time) as month'),
            DB::raw('COUNT(*) as transactions'),
            DB::raw('SUM(CASE WHEN event_type = "PAYMENT" THEN 1 ELSE 0 END) as payments'),
            DB::raw('SUM(CASE WHEN event_type = "PAYMENT" THEN payments.amount ELSE 0 END) as amount') // Correctly sum payments.amount
        )
            ->leftJoin('payments', 'current_transactions.payment_id', '=', 'payments.id')  // Ensure correct join condition
            ->groupBy(DB::raw('MONTH(event_time)'))
            ->orderBy(DB::raw('MONTH(event_time)'))
            ->get()
            ->map(function ($item) {
                return [
                    'month' => date('M', mktime(0, 0, 0, $item->month, 1)),
                    'transactions' => $item->transactions,
                    'payments' => $item->payments,
                    'amount' => $item->amount
                ];
            });
    }


    private function getEventTypeBreakdown()
    {
        return CurrentTransaction::select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => ucwords(strtolower(str_replace('-', ' ', $item->event_type))),
                    'count' => $item->count,
                    'color' => $this->getEventTypeColor($item->event_type)
                ];
            });
    }

    private function getPaymentModeStatus()
    {
        // This would typically involve joining with payments table
        return Payment::select('payment_mode')
            ->selectRaw('COUNT(CASE WHEN payment_status = "COMPLETED" THEN 1 END) as completed')
            ->selectRaw('COUNT(CASE WHEN payment_status = "PENDING" THEN 1 END) as pending')
            ->selectRaw('COUNT(CASE WHEN payment_status = "FAILED" THEN 1 END) as failed')
            ->selectRaw('COUNT(CASE WHEN payment_status = "REFUNDED" THEN 1 END) as refunded')
            ->groupBy('payment_mode')
            ->get();
    }

    private function getClusterData()
    {
        return Cluster::select('cluster_name as area')
            ->selectRaw('COUNT(current_transactions.id) as transactions')
            ->selectRaw('SUM(CASE WHEN current_transactions.event_type = "PAYMENT" THEN 1 ELSE 0 END) as payments')
            ->selectRaw('SUM(payments.amount) as amount')
            ->leftJoin('current_transactions', 'clusters.id', '=', 'current_transactions.cluster_id')
            ->leftJoin('payments', 'current_transactions.payment_id', '=', 'payments.id')
            ->groupBy('clusters.id', 'clusters.cluster_name')
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



    private function getCancellationData()
    {
        // Implement cancellation reason breakdown
        return DenialReason::select('reason as type')  // Renamed from 'type' to 'reason'
            ->selectRaw('COUNT(*) as value')
            ->where('reason', 'CANCELLATION')  // Filter by 'reason', not 'type'
            ->groupBy('reason')  // Group by 'reason'
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,  // This is still named 'type' in the returned data
                    'value' => $item->value,
                    'color' => $this->getCancellationColor($item->type)
                ];
            });
    }



    private function getDenialData()
    {
        // Implement denial reason breakdown
        return DenialReason::select('reason as type')  // Renamed from 'type' to 'reason'
            ->selectRaw('COUNT(*) as value')
            ->where('reason', 'DENIAL')  // Filter by 'reason', not 'type'
            ->groupBy('reason')  // Group by 'reason'
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,  // This is still named 'type' in the returned data
                    'value' => $item->value,
                    'color' => $this->getDenialColor($item->type)
                ];
            });
    }



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


    // Utility color mapping methods
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
}
