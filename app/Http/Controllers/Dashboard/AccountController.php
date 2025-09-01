<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    // API-ID: ACDASH-004 [Date Receipt Summary]
    public function getDatePaymentSummary(Request $request)
    {
        try {
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
            $validator = Validator::make($request->all(), [
                'tranDate' => [
                    'required',
                    'date',
                    'after_or_equal:' . now()->subYear()->format('Y-m-d'),
                    'before_or_equal:' . now()->format('Y-m-d'),
                ],
                'searchKey' => 'nullable|string|in:tc_id,paymentStatus',
                'tc_id' => 'nullable|exists:users,id',
                'paymentStatus' => 'nullable|in:verified,pending'
            ]);

            if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();
                return format_response('validation error', $errorMessages, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $date = $request->tranDate;
            $query = DB::table('current_transactions as t')
                ->join('ratepayers as r', 't.ratepayer_id', '=', 'r.id')
                ->join('payments as p', 't.payment_id', '=', 'p.id')
                ->join('users as u', 't.tc_id', '=', 'u.id')
                ->select(
                    't.id as tran_id',
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
                    'p.amount',
                    'p.payment_verified',
                    't.is_verified as transaction_verified',
                    't.verification_date',
                    't.auto_remarks as verification_remarks',
                    'u.id as tc_id'
                )
                ->whereDate('t.event_time', $date)
                ->where('p.payment_mode', 'CASH');

            // Apply search filters if provided
            if ($request->filled('searchKey') && $request->searchKey === 'tc_id' && $request->filled('tc_id')) {
                $query->where('t.tc_id', $request->tc_id);
            }

            if ($request->filled('searchKey') && $request->searchKey === 'paymentStatus') {
                if ($request->paymentStatus === 'verified') {
                    $query->where('p.payment_verified', true);
                } else if ($request->paymentStatus === 'pending') {
                    $query->whereNull('p.payment_verified');
                }
            }

            $transactions = $query->orderBy('p.payment_verified')
                                ->orderBy('t.id')
                                ->get();

            return format_response(
                'Success',
                $transactions,
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return format_response(
                'An error occurred: ' . $e->getMessage(),
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
                // Make tc_id optional
               //  'tc_id' => 'sometimes|exists:users,id',
            ]);

            $date = $validated['tranDate'];
            // Check if tc_id is provided, otherwise set to null
            // $tcId = $validated['tc_id'] ?? null;

            $otherPayments = DB::table('current_transactions as t')
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
                // Conditionally apply tc_id filter
               //  ->when($tcId, function($query, $tcId) {
               //      return $query->where('t.tc_id', '=', $tcId);
               //  })
                ->get();

            return format_response(
                'Success',
                $otherPayments,
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
                // Make tc_id optional for cheque verification as well
               //  'tc_id' => 'sometimes|exists:users,id',
            ]);

            $date = $validated['tranDate'];
            // Check if tc_id is provided, otherwise set to null
           //  $tcId = $validated['tc_id'] ?? null;

            $cheques = DB::table('ratepayer_cheques as c')
                ->join('ratepayers as r', 'c.ratepayer_id', '=', 'r.id')
                ->join('current_transactions as t', 'c.tran_id', '=', 't.id')
                ->join('users as u', 't.tc_id', '=', 'u.id')
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
                    'c.is_verified as isVerified',
                    'u.name as tc_name' // Add tax collector name
                )
                ->whereRaw('DATE(c.created_at) = ?', [$date])
                // Conditionally apply tc_id filter
               //  ->when($tcId, function($query, $tcId) {
               //      return $query->where('t.tc_id', '=', $tcId);
               //  })
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

    // API-ID: ACDASH-005 [Verified Transactions]
    public function getVerifiedTransactions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tranDate' => [
                    'required',
                    'date',
                    'after_or_equal:' . now()->subYear()->format('Y-m-d'),
                    'before_or_equal:' . now()->format('Y-m-d'),
                ],
                'searchKey' => 'nullable|string|in:tc_id',
                'tc_id' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();
                return format_response('validation error', $errorMessages, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $query = DB::table('current_transactions as t')
                ->join('ratepayers as r', 't.ratepayer_id', '=', 'r.id')
                ->join('payments as p', 't.payment_id', '=', 'p.id')
                ->join('users as u', 't.tc_id', '=', 'u.id')
                ->select(
                    't.id as tran_id',
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
                    'p.amount',
                    'p.payment_verified',
                    't.is_verified as transaction_verified',
                    't.verification_date',
                    't.auto_remarks as verification_remarks',
                    'u.id as tc_id'
                )
                ->whereDate('t.event_time', $request->tranDate)
                ->where('p.payment_verified', 1)
                ->where('t.is_verified', 1);

            // Apply search filter if tc_id is provided
            if ($request->filled('searchKey') && $request->searchKey === 'tc_id' && $request->filled('tc_id')) {
                $query->where('t.tc_id', $request->tc_id);
            }

            $transactions = $query->orderBy('t.id')
                                ->get();

            return format_response(
                'Success',
                $transactions,
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return format_response(
                'An error occurred: ' . $e->getMessage(),
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}