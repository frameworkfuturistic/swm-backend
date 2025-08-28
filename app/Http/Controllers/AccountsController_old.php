<?php

namespace App\Http\Controllers;

use App\Models\CurrentDemand;
use App\Models\CurrentTransaction;
use App\Models\Demand;
use App\Models\Payment;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

//  -- Verify Payments
//  -- Modify Payment Records
//  -- Verify Cancellations
//  -- Collect Cash
//  -- Collect Cheque
//  -- Cheque Verification
//  -- Cheque Reconciliation and update payment
//  -- UPI Verification and Reconciliation
//  -- Initiate UPI Refund
//  -- Waive off Demand against order

class AccountsController extends Controller
{
    public function dailyTransactions(Request $request)
    {
      $today = Carbon::today();
      $oneYearAgo = Carbon::today()->subYear();
      $ulbId = $request->ulb_id;
      try {
            $validator = Validator::make($request->all(), [
               'dateFrom' => [
                  'required',
                  'date_format:Y-m-d',
                  function ($attribute, $value, $fail) use ($oneYearAgo, $today) {
                     if ($value < $oneYearAgo->format('Y-m-d') || $value > $today->format('Y-m-d')) {
                           $fail('The '.$attribute.' must be between '.$oneYearAgo->format('Y-m-d').' and '.$today->format('Y-m-d').'.');
                     }
                  },
               ],
               'dateTo' => [
                  'required',
                  'date_format:Y-m-d',
                  function ($attribute, $value, $fail) use ($today) {
                     if ($value > $today->format('Y-m-d')) {
                           $fail('The '.$attribute.' cannot be after '.$today->format('Y-m-d').'.');
                     }
                  },
               ],
               'zoneId' => 'required|numeric|between:1,100',
               'tranType' => 'required|in:ALL,PAYMENT,DENIAL,DOOR-CLOSED,DEFERRED,CHEQUE,OTHER',
            ]);
            
            $validator->after(function ($validator) use ($request) {
                  if ($request->dateFrom > $request->dateTo) {
                     $validator->errors()->add('dateFrom', 'The dateFrom must be before or equal to dateTo.');
                  }
            });

            if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();

                return format_response(
                    'validation error',
                    $errorMessages,
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $query = DB::table('current_transactions as c')
                ->select(
                    'c.id',
                    'c.tc_id',
                    'c.payment_id',
                    'c.event_type as eventType',
                    'c.event_time as eventTime',
                    'r.ratepayer_name as ratepayerName',
                    'r.ratepayer_address as ratepayerAddress',
                    'r.mobile_no as ratepayerMobile',
                    'u1.name as tcName',
                  //   'p.payment_mode as paymentMode',
                  //   'p.payment_status as paymentStatus',
                    'u2.name as cancelledBy',
                    'c.cancellation_date as cancellationDate',
                    'c.schedule_date as scheduleDate',
                    'c.remarks',
                    'c.photo_path as photoPath',
                  //   'p.payment_verified as paymentVerified',
                  //   'p.refund_initiated as refundInitiated',
                  //   'p.refund_verified as refundVerified',
                  //   'u3.name as verifiedBy',
                    'c.is_verified as isVerified',
                    'c.is_cancelled as isCancelled'
                )
                ->join('ratepayers as r', 'c.ratepayer_id', '=', 'r.id')
                ->join('users as u1', 'c.tc_id', '=', 'u1.id')
               //  ->leftJoin('payments as p', 'c.id', '=', 'p.tran_id')
                ->leftJoin('users as u2', 'c.cancelledby_id', '=', 'u2.id')
               //  ->leftJoin('users as u3', 'p.verified_by', '=', 'u3.id')
                ->where('c.ulb_id', $ulbId)
                ->where('r.paymentzone_id', $request->zoneId)
                ->where('c.is_cancelled', false)
                ->whereDate('c.event_time', '>=', $request->dateFrom)
                ->whereDate('c.event_time', '<=', $request->dateTo);

            if ($request->tranType !== 'ALL') {
                  $query->where('event_type', $request->tranType);
            }
            $currentTransactions = $query->get();

            return format_response(
                'Day Transactions',
                $currentTransactions,
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

    public function paymentTransactions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tranDate' => [
                    'required',
                    'regex:/^\d{4}-\d{2}-\d{2}$/',
                    function ($attribute, $value, $fail) {
                        if (! \DateTime::createFromFormat('Y-m-d', $value)) {
                            $fail('The '.$attribute.' is not a valid date.');
                        }
                    },
                ],
                'zoneId' => 'required|numeric|between:1,100',
            ]);

            if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();

                return format_response(
                    'validation error',
                    $errorMessages,
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $ulbId = $request->ulb_id;
            $payments = DB::table('payments as p')
                ->select(
                    'p.id',
                    'p.ratepayer_id as ratepayerId',
                    'r.paymentzone_id as paymentZoneId',
                    'p.tc_id',
                    'u.name as tcName',
                    'r.ratepayer_name as ratepayerName',
                    'r.mobile_no as mobileNo',
                    'p.tran_id as tranId',
                    'c.remarks',
                    'p.payment_date as paymentDate',
                    'p.payment_mode as paymentMode',
                    'p.payment_status as paymentStatus',
                    'p.amount',
                    DB::raw("IF(p.payment_verified IS NULL, '', 'Yes') as paymentVerified"),
                    DB::raw("IF(p.refund_initiated IS NULL, '', 'Yes') as refundInitiated"),
                    DB::raw("IF(p.refund_verified IS NULL, '', 'Yes') as refundVerified"),
                    'p.verified_by as paymentVerifiedBy',
                    'p.card_number as cardNumber',
                    'p.upi_id as upiId',
                    'p.cheque_number as chequeNo',
                    'p.is_canceled as isPaymentCancelled',
                    'c.is_verified as tranVerified',
                    'c.is_cancelled as tranCancelled'
                )
                ->join('current_transactions as c', 'p.tran_id', '=', 'c.id')
                ->join('ratepayers as r', 'p.ratepayer_id', '=', 'r.id')
                ->join('users as u', 'c.tc_id', '=', 'u.id')
                ->where('p.ulb_id', $ulbId)
                ->where('c.is_cancelled', false)
                ->where('r.paymentzone_id', $request->zoneId)
                ->where('r.paymentzone_id', $request->zoneId)
                ->whereDate('p.created_at', $request->tranDate)
                ->get();

            return format_response(
                'Payment Transactions',
                $payments,
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

    public function verifyTransactions(Request $request)
    {
        $userId = Auth::user()->id;

        try {
            $validator = Validator::make($request->all(), [
                'tranId' => 'required|integer|exists:current_transactions,id', // Ensures the ID is valid and exists in the 'ratepayers' table
                'remarks' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();

                return format_response(
                    'validation error',
                    $errorMessages,
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $transaction = CurrentTransaction::find($request->tranId);
            $transaction->verification_date = now();
            $transaction->verifiedby_id = $userId;
            $transaction->is_verified = true;
            $transaction->auto_remarks = $request->remarks;
            $transaction->save();

            $payment = Payment::find($transaction->payment_id);
            if ($payment != null) {
                if ($payment->payment_mode == 'CASH') {
                    $payment->payment_verified = true;
                    $payment->save();
                }
            }

            return format_response(
                'Success',
                $transaction,
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

    public function verifyCancellation(Request $request)
    {
        $userId = Auth::user()->id;

        DB::beginTransaction();
        try {
            //Validate Input
            $validator = Validator::make($request->all(), [
                'tranId' => 'required|integer|exists:current_transactions,id', // Ensures the ID is valid and exists in the 'ratepayers' table
                'remarks' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();

                return format_response(
                    'validation error',
                    $errorMessages,
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            //Find Current Transaction
            $transaction = CurrentTransaction::find($request->tranId);
            $transaction->verification_date = now();
            $transaction->verifiedby_id = $userId;
            $transaction->auto_remarks = $request->remarks;
            $transaction->save();

            $paymentId = $transaction->payment_id;
            if ($paymentId == null) {
                DB::rollBack();

                return format_response(
                    'An error occurred during insertion',
                    null,
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            //Find Payment
            $payment = Payment::find($transaction->payment_id);
            if ($payment == null) {
                DB::rollBack();

                return format_response(
                    'An error occurred during insertion',
                    null,
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
            $payment->is_canceled = true;
            $payment->verified_by = $userId;
            $payment->save();

            //Rollback Demands to current_demand
            $demands = Demand::where('payment_id', $transaction->payment_id)->get();
            foreach ($demands as $demand) {
                CurrentDemand::create($demand->toArray()); // Create a new record using the attributes of each demand
                $demand->delete();
            }

            DB::commit();

            return format_response(
                'Cancelled and updated Demand',
                $request->tranId,
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

    public function unclearedCheques(Request $request)
    {
        try {
            $payments = DB::table('payments as p')
                ->select(
                    'p.id',
                    'p.ratepayer_id as ratepayerId',
                    'r.paymentzone_id as paymentZoneId',
                    'p.tc_id',
                    'u.name as tcName',
                    'r.ratepayer_name as ratepayerName',
                    'r.mobile_no as mobileNo',
                    'p.tran_id as tranId',
                    'c.remarks',
                    'p.payment_date as paymentDate',
                    'p.payment_mode as paymentMode',
                    'p.payment_status as paymentStatus',
                    'p.amount',
                    DB::raw("IF(p.payment_verified IS NULL, '', 'Yes') as paymentVerified"),
                    DB::raw("IF(p.refund_initiated IS NULL, '', 'Yes') as refundInitiated"),
                    DB::raw("IF(p.refund_verified IS NULL, '', 'Yes') as refundVerified"),
                    'p.verified_by as paymentVerifiedBy',
                    'p.card_number as cardNumber',
                    'p.upi_id as upiId',
                    'p.cheque_number as chequeNo',
                    'p.is_canceled as isPaymentCancelled',
                    'c.is_verified as tranVerified',
                    'c.is_cancelled as tranCancelled'
                )
                ->join('current_transactions as c', 'p.tran_id', '=', 'c.id')
                ->join('ratepayers as r', 'p.ratepayer_id', '=', 'r.id')
                ->join('users as u', 'c.tc_id', '=', 'u.id')
                ->where('p.ulb_id', 1)
                ->where('p.payment_verified', false)
                ->where('p.payment_mode', 'CHEQUE')
                ->get();

            return format_response(
                'Payment Transactions',
                $payments,
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

    public function currentDemandSummary(Request $request)
    {
        $ulbId = $request->ulb_id;
        $rules = [
            'usageType' => 'nullable|string|in:Residential,Commercial,Industrial',  // Adjust the values as needed
            'categoryId' => 'nullable|integer|exists:categories,id',
            'subcategoryId' => 'nullable|integer|exists:sub_categories,id',
            'paymentzoneId' => 'nullable|integer|exists:payment_zones,id',
        ];

        // Validate the request, only including the allowed parameters
        $validator = Validator::make($request->only(array_keys($rules)), $rules);

        if ($validator->fails()) {
            return format_response(
                'Error, Invalid parameter used',
                null,
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $query = DB::table('current_demands as c')
                ->select(
                    'c.ratepayer_id as ratepayerId',
                    'r.ratepayer_name as ratepayerName',
                    'r.ratepayer_address as ratepayerAddress',
                    'r.mobile_no as mobileNo',
                    'z.payment_zone as paymentZone',
                    'r.usage_type as usageType',
                    'cat.category',
                    's.sub_category as subCategory',
                    'r.lastpayment_date as lastPaymentDate',
                    'r.lastpayment_amt as lastPaymentAmount',
                    DB::raw('SUM(c.total_demand) as totalDemand')
                )
                ->join('ratepayers as r', 'c.ratepayer_id', '=', 'r.id')
                ->join('sub_categories as s', 'r.subcategory_id', '=', 's.id')
                ->join('categories as cat', 's.category_id', '=', 'cat.id')
                ->join('payment_zones as z', 'r.paymentzone_id', '=', 'z.id')
                ->where('c.bill_year', '<=', DB::raw('YEAR(CURRENT_DATE)'))
                ->where('c.bill_month', '<=', DB::raw('MONTH(CURRENT_DATE)'))
                ->where('r.ulb_id', $ulbId);

            // Apply filters if provided
            if ($request->has('usageType')) {
                $query->where('r.usage_type', $request->input('usageType'));
            }
            if ($request->has('paymentzoneId')) {
                $query->where('r.paymentzone_id', $request->input('paymentzoneId'));
            }
            if ($request->has('categoryId')) {
                $query->where('cat.id', $request->input('categoryId'));
            }
            if ($request->has('subcategoryId')) {
                $query->where('s.id', $request->input('subcategoryId'));
            }

            $currentDemands = $query->groupBy(
                'c.ratepayer_id',
                'r.ratepayer_name',
                'r.ratepayer_address',
                'r.mobile_no',
                'r.usage_type',
                'cat.category',
                's.sub_category',
                'r.lastpayment_date',
                'r.lastpayment_amt'
            )->get();

            return format_response(
                'Demand Summary',
                $currentDemands,
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

    public function showCancelledTransactions(Request $request)
    {
        $ulbId = $request->ulb_id;
        try {
            $validator = Validator::make($request->all(), [
                'tranDate' => [
                    'required',
                    'regex:/^\d{4}-\d{2}-\d{2}$/',
                    function ($attribute, $value, $fail) {
                        if (! \DateTime::createFromFormat('Y-m-d', $value)) {
                            $fail('The '.$attribute.' is not a valid date.');
                        }
                    },
                ],
                'zoneId' => 'required|numeric|between:1,100',
            ]);

            if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();

                return format_response(
                    'validation error',
                    $errorMessages,
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $currentTransactions = DB::table('current_transactions as c')
                ->select(
                    'c.id',
                    'c.tc_id',
                    'c.event_type as eventType',
                    'c.event_time as eventTime',
                    'r.ratepayer_name as ratepayerName',
                    'u1.name as tcName',
                    'p.payment_mode as paymentMode',
                    'p.payment_status as paymentStatus',
                    'u2.name as cancelledBy',
                    'c.cancellation_date as cancellationDate',
                    'c.schedule_date as scheduleDate',
                    'c.remarks',
                    'c.photo_path as photoPath',
                    'p.payment_verified as paymentVerified',
                    'p.refund_initiated as refundInitiated',
                    'p.refund_verified as refundVerified',
                    'u3.name as verifiedBy',
                    'c.is_verified as isVerified',
                    'c.is_cancelled as isCancelled'
                )
                ->join('ratepayers as r', 'c.ratepayer_id', '=', 'r.id')
                ->join('users as u1', 'c.tc_id', '=', 'u1.id')
                ->leftJoin('payments as p', 'c.id', '=', 'p.tran_id')
                ->leftJoin('users as u2', 'c.cancelledby_id', '=', 'u2.id')
                ->leftJoin('users as u3', 'p.verified_by', '=', 'u3.id')
                ->where('c.ulb_id', $ulbId)
                ->where('r.paymentzone_id', $request->zoneId)
                ->where('c.is_cancelled', true)
                ->whereDate('c.created_at', $request->tranDate)
                ->whereIn('c.event_type', ['DENIAL', 'DOOR-CLOSED', 'DEFERRED'])
                ->get();

            return format_response(
                'Cancelled Transactions',
                $currentTransactions,
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
