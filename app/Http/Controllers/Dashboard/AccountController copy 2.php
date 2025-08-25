<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class AccountController extends Controller
{

    // API-ID: ACDASH-004 [Date Receipt Summary]
    public function getDatePaymentSummary(Request $request)
    {
      try{
         $validated = $request->validate([
            'tranDate' => [
                'required',
                'date',
                'after_or_equal:' . now()->subYear()->format('Y-m-d'),
                'before_or_equal:' . now()->format('Y-m-d'),
            ],
        ]);
        
        $result = DB::table('payments as p')
               ->select([
                     'u.id as tc_id',
                     'u.name as tc_name',
                     DB::raw("SUM(IF(p.payment_mode = 'CASH', p.amount, 0)) as cash"),
                     DB::raw("SUM(IF(p.payment_mode = 'CARD', p.amount, 0)) as card"),
                     DB::raw("SUM(IF(p.payment_mode = 'UPI', p.amount, 0)) as upi"),
                     DB::raw("SUM(IF(p.payment_mode = 'CHEQUE', p.amount, 0)) as cheque"),
                     DB::raw("SUM(IF(p.payment_mode = 'ONLINE', p.amount, 0)) as online"),
                     DB::raw("SUM(p.amount) as amount"), // Total amount
               ])
               ->join('users as u', 'p.tc_id', '=', 'u.id')
               ->whereDate('p.payment_date', $validated['tranDate'])
               ->groupBy('u.id')
               ->get();
         return format_response(
            'Success',
            $result,
            Response::HTTP_CREATED
         );         
      } catch (Exception $e) {
         return format_response(
             'An error occurred during insertion. '.$e->getMessage(),
             null,
             Response::HTTP_INTERNAL_SERVER_ERROR
         );
     }


    }

   // API-ID: ACDASH-003 [Cash Verification]    
    public function getDateCashForVerification(Request $request)
    {
      try {
         // Validate the date input
         $validated = $request->validate([
            'tranDate' => [
                'required',
                'date',
                'after_or_equal:' . now()->subYear()->format('Y-m-d'),
                'before_or_equal:' . now()->format('Y-m-d'),
            ],
            'tc_id' => 'required|exists:users,id',
        ]);

        $date = $validated['tranDate'];
        $tcId = $validated['tc_id'];

         $cashTransactions = DB::table('current_transactions as t')
            ->join('ratepayers as r', 't.ratepayer_id', '=', 'r.id')
            ->join('payments as p', 't.payment_id', '=', 'p.id')
            ->join('users as u', 't.tc_id', '=', 'u.id')
            ->select(
               'p.id as payment_id',
               'r.id as ratepayer_id',
               'u.name as tc_name',
               'r.ratepayer_name',
               'r.ratepayer_address',
               'r.consumer_no',
               'r.mobile_no',
               'r.usage_type',
               'r.monthly_demand',
               'p.payment_mode',
               'p.amount'
            )
            ->whereRaw('DATE(t.event_time) = ?', [$date])
            ->where('p.payment_mode', '=', 'CASH')
            ->where('t.tc_id', '=', $tcId)
            ->get();

         return format_response(
            'Success',
            $cashTransactions,
            Response::HTTP_OK
         );                

      } catch (Exception $e) {
         return format_response(
             'An error occurred during insertion. '.$e->getMessage(),
             null,
             Response::HTTP_INTERNAL_SERVER_ERROR
         );
      }
      
    }

    // API-ID: ACDASH-001 [Non Cash Verification]
    public function getDateOtherPaymentsForVerification(Request $request)
    {
      try {
         // Validate the date input
         $validated = $request->validate([
            'tranDate' => [
                'required',
                'date',
                'after_or_equal:' . now()->subYear()->format('Y-m-d'),
                'before_or_equal:' . now()->format('Y-m-d'),
            ],
            'tc_id' => 'required|exists:users,id',
        ]);

         $date = $validated['tranDate'];
         $tcId = $validated['tc_id'];

         $cashTransactions = DB::table('current_transactions as t')
            ->join('ratepayers as r', 't.ratepayer_id', '=', 'r.id')
            ->join('payments as p', 't.payment_id', '=', 'p.id')
            ->join('users as u', 't.tc_id', '=', 'u.id')
            ->select(
               'r.id as ratepayer_id',
               'p.id as payment_id',
               'u.name as tc_name',
               'r.ratepayer_name',
               'r.ratepayer_address',
               'r.consumer_no',
               'r.mobile_no',
               'r.usage_type',
               'r.monthly_demand',
               'p.payment_mode',
               'p.amount'
            )
            ->whereRaw('DATE(t.event_time) = ?', [$date])
            ->where('p.payment_mode', '<>', 'CASH')
            ->where('t.tc_id', '=', $tcId)
            ->get();

         return format_response(
            'Success',
            $cashTransactions,
            Response::HTTP_OK
         );                

      } catch (Exception $e) {
         return format_response(
             'An error occurred during insertion. '.$e->getMessage(),
             null,
             Response::HTTP_INTERNAL_SERVER_ERROR
         );
      }
      
    }

    // API-ID: ACDASH-002 [Cheque Verification]
    public function getDateChequeCollection(Request $request)
    {
      try {
         // Validate the date input
         $validated = $request->validate([
            'tranDate' => [
                'required',
                'date',
                'after_or_equal:' . now()->subYear()->format('Y-m-d'),
                'before_or_equal:' . now()->format('Y-m-d'),
            ],
        ]);

         $date = $validated['tranDate'];

         $cheques = DB::table('ratepayer_cheques as c')
            ->join('ratepayers as r', 'c.ratepayer_id', '=', 'r.id')
            ->join('current_transactions as t', 'c.tran_id', '=', 't.id')
            ->select(
                  'c.id',
                  'r.ratepayer_name as ratepayerName',
                  'r.ratepayer_address as ratepayerAddress',
                  'r.mobile_no as mobileNo',
                  'c.cheque_no as chequeNo', 
                  'c.cheque_date as chequeDate',
                  'c.bank_name as bankName',
                  'c.amount',
                  'c.realization_date as realizationDate',
                  'c.is_returned as isReturned',
                  'c.is_verified as isVerified'
            )
            ->whereRaw('DATE(c.created_at) = ?', [$date])
            ->get();

         return format_response(
            'Success',
            $cheques,
            Response::HTTP_OK
         );                

      } catch (Exception $e) {
         return format_response(
             'An error occurred during insertion. '.$e->getMessage(),
             null,
             Response::HTTP_INTERNAL_SERVER_ERROR
         );
      }
      
    }
    
}
