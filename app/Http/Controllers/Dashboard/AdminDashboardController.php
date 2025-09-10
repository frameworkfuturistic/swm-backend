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

    /**
     * Get dashboard data for the specified date range
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getDashboardData(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        try {
            // Get card summary data
            $cardSummary = $this->getCardSummary($startDate, $endDate);

            // Get monthly transaction data
            $monthlyTransactions = $this->getMonthlyTransactions($startDate, $endDate);

            // Get payment mode distribution
            $paymentModeDistribution = $this->getPaymentModeDistribution($startDate, $endDate);

            // Get TC wise transaction data
            $tcWiseTransactions = $this->getTcWiseTransactions($startDate, $endDate);

            // Get monthly payment data
            $monthlyPayments = $this->getMonthlyPayments($startDate, $endDate);

            // Get transactions list
            $transactions = $this->getTransactions($startDate, $endDate);

            // Get outcome distribution (pie chart)
            $outcomeDistribution = $this->getVisitOutcomesDistribution($startDate, $endDate);

            // Compile all data into a single response
            $ret = ([
                'status' => 'success',
                'data' => [
                    'card_summary' => $cardSummary,
                    'monthly_transactions' => $monthlyTransactions,
                    'payment_mode_distribution' => $paymentModeDistribution,
                    'tc_wise_transactions' => $tcWiseTransactions,
                    'monthly_payments' => $monthlyPayments,
                    'transactions' => $transactions,
                    'outcome_distribution' => $outcomeDistribution
                ],
                'metadata' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'generated_at' => Carbon::now()->toDateTimeString()
                ]
            ]);

            return format_response(
                'Success',
                $ret,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get card summary data
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getCardSummary($startDate, $endDate)
    {
        $transactionSummary = DB::select("
            SELECT
                COUNT(id) AS totaltransactions,
                SUM(IF(event_type='PAYMENT',1,0)) AS payments,
                SUM(IF(event_type='DENIAL',1,0)) AS denials,
                SUM(IF(event_type='DOOR-CLOSED',1,0)) AS doorclosed,
                SUM(IF(event_type='DEFERRED',1,0)) AS reschedules,
                SUM(IF(event_type='CHEQUE',1,0)) AS cheques
            FROM current_transactions
            WHERE DATE(event_time) BETWEEN ? AND ?
        ", [$startDate, $endDate]);

        $demandSummary = DB::selectOne("
            SELECT
                  SUM(
                     IF(
                        ((bill_year * 12) + bill_month) < ((YEAR(SYSDATE()) * 12) + MONTH(SYSDATE())),
                        demand,
                        0
                     )
                  ) AS outstanding_demand,
                  
                  SUM(
                     IF(
                        ((bill_year * 12) + bill_month) = ((YEAR(SYSDATE()) * 12) + MONTH(SYSDATE())),
                        demand,
                        0
                     )
                  ) AS month_demand,
                  
                  SUM(demand) AS total_demand
            FROM current_demands
            WHERE ((bill_year * 12) + bill_month) <= ((YEAR(SYSDATE()) * 12) + MONTH(SYSDATE()))
         ");

         // Merge both summaries into one array/object
         return [
            'transactions' => $transactionSummary,
            'demand_summary' => $demandSummary
         ];

      //   return $result[0] ?? [];
    }

    /**
     * Get monthly transaction data
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getMonthlyTransactions($startDate, $endDate)
    {
        return DB::select("
            SELECT
                MONTH(event_time) AS month,
                MONTHNAME(event_time) AS monthName,
                COUNT(id) AS transactions,
                SUM(IF(event_type='PAYMENT',1,0)) AS payments
            FROM current_transactions
            WHERE DATE(event_time) BETWEEN ? AND ?
            GROUP BY MONTH(event_time), MONTHNAME(event_time)
            ORDER BY MONTH(event_time)
        ", [$startDate, $endDate]);
    }

    /**
     * Get payment mode distribution
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getPaymentModeDistribution($startDate, $endDate)
    {
        return DB::select("
            SELECT
                payment_mode,
                SUM(amount) AS total_amount
            FROM payments
            WHERE DATE(payment_date) BETWEEN ? AND ?
            GROUP BY payment_mode
        ", [$startDate, $endDate]);
    }

    /**
     * Get TC wise transaction data
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getTcWiseTransactions($startDate, $endDate)
    {
        return DB::select("
            SELECT 
                t.tc_id, 
                u.name,
                COUNT(t.id) AS transactions, 
                COUNT(p.id) AS payments
            FROM current_transactions t
            INNER JOIN users u ON t.tc_id = u.id
            LEFT JOIN payments p ON t.payment_id = p.id
            WHERE DATE(t.event_time) BETWEEN ? AND ?
            GROUP BY t.tc_id, u.name
        ", [$startDate, $endDate]);
    }

    /**
     * Get monthly payment data
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getMonthlyPayments($startDate, $endDate)
    {
        return DB::select("
            SELECT
                MONTH(payment_date) AS month,
                MONTHNAME(payment_date) AS monthName,
                SUM(amount) AS payments
            FROM payments
            WHERE DATE(payment_date) BETWEEN ? AND ?
            GROUP BY MONTH(payment_date), MONTHNAME(payment_date)
            ORDER BY MONTH(payment_date)
        ", [$startDate, $endDate]);
    }

    /**
     * Get transactions list
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return array
     */

    private function getTransactions($startDate, $endDate, $limit = 1000)
    {
      //   return DB::select("
      //       SELECT 
      //           t.id AS transaction_id,
      //           u.ulb_name,
      //           r.ratepayer_name,
      //           t.event_time,
      //           t.event_type,
      //           p.payment_mode,
      //           p.payment_status,
      //           p.amount
      //       FROM current_transactions t
      //       INNER JOIN ulbs u ON u.id = t.ulb_id
      //       INNER JOIN ratepayers r ON t.ratepayer_id = r.id
      //       LEFT JOIN payments p ON p.id = t.payment_id
      //       WHERE DATE(t.event_time) BETWEEN ? AND ?
      //       ORDER BY t.event_time DESC
      //       LIMIT ?
      //   ", [$startDate, $endDate, $limit]);

      return DB::select("
         SELECT 
            t.id AS transaction_id,
            u.ulb_name,
            r.ratepayer_name,
            t.event_time,
            t.event_type,
            p.payment_mode,
            p.payment_status,
            p.amount
         FROM (
            SELECT id, ulb_id, ratepayer_id, event_time, event_type, payment_id 
            FROM current_transactions
            WHERE DATE(event_time) BETWEEN ? AND ?

            UNION ALL

            SELECT id, ulb_id, ratepayer_id, event_time, event_type, payment_id 
            FROM transactions
            WHERE DATE(event_time) BETWEEN ? AND ?
         ) t
         INNER JOIN ulbs u ON u.id = t.ulb_id
         INNER JOIN ratepayers r ON t.ratepayer_id = r.id
         LEFT JOIN payments p ON p.id = t.payment_id
         ORDER BY t.event_time DESC
         LIMIT ?
      ", [$startDate, $endDate, $startDate, $endDate, $limit]);


    }

   //  private function getTransactions($startDate, $endDate, $limit = 100)
   //  {
   //      return DB::select("
   //          SELECT 
   //              t.id AS transaction_id,
   //              u.ulb_name,
   //              r.ratepayer_name,
   //              t.event_time,
   //              t.event_type,
   //              p.payment_mode,
   //              p.payment_status,
   //              p.amount
   //          FROM current_transactions t
   //          INNER JOIN ulbs u ON u.id = t.ulb_id
   //          INNER JOIN ratepayers r ON t.ratepayer_id = r.id
   //          LEFT JOIN payments p ON p.id = t.payment_id
   //          WHERE DATE(t.event_time) BETWEEN ? AND ?
   //          ORDER BY t.event_time DESC
   //          LIMIT ?
   //      ", [$startDate, $endDate, $limit]);
   //  }

    private function getVisitOutcomesDistribution($startDate, $endDate)
    {
        $result = DB::select("
        SELECT
            event_type,
            COUNT(*) as count,
            ROUND((COUNT(*) / (SELECT COUNT(*) FROM current_transactions 
                               WHERE DATE(event_time) BETWEEN ? AND ?)) * 100, 0) as percentage
        FROM current_transactions
        WHERE DATE(event_time) BETWEEN ? AND ?
        GROUP BY event_type
        ORDER BY count DESC
    ", [$startDate, $endDate, $startDate, $endDate]);

        // Map the event types to the labels used in the chart
        $mappedResults = [];
        foreach ($result as $row) {
            $label = $row->event_type;
            // Map the event types to more readable labels if needed
            switch ($row->event_type) {
                case 'PAYMENT':
                    $label = 'Payment';
                    $color = '#4CAF50'; // Green
                    break;
                case 'DENIAL':
                    $label = 'Denial';
                    $color = '#F44336'; // Red
                    break;
                case 'DOOR-CLOSED':
                    $label = 'Door Closed';
                    $color = '#2196F3'; // Blue
                    break;
                case 'DEFERRED':
                    $label = 'Deferred';
                    $color = '#FFA726'; // Orange
                    break;
                case 'CHEQUE':
                    $label = 'Cheque';
                    $color = '#9C27B0'; // Purple
                    break;
                default:
                    $label = 'Other';
                    $color = '#607D8B'; // Gray
                    break;
            }

            $mappedResults[] = [
                'label' => $label,
                'percentage' => (int)$row->percentage,
                'count' => (int)$row->count,
                'color' => $color
            ];
        }

        return $mappedResults;
    }
}
