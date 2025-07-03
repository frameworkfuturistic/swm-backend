<?php

namespace App\Http\Services;

use App\Models\Ratepayer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntityPaymentService
{

   public function postPayment(array $validatedData)
   {
        $data = DB::table('ratepayers as r')
            ->select(
               'w.ward_name',
               'r.consumer_no',
               'r.ratepayer_name',
               'r.ratepayer_address',
               'c.category',
               'sc.sub_category',
               'r.monthly_demand'
            )
            ->join('wards as w', 'r.ward_id', '=', 'w.id')
            ->leftJoin('sub_categories as sc', 'r.subcategory_id', '=', 'sc.id')
            ->leftJoin('categories as c', 'sc.category_id', '=', 'c.id')
            ->where('r.id', $validatedData['ratepayer_id'])
            ->first();

        $tranService = new TransactionService;

        $ratepayer = Ratepayer::find($validatedData['ratepayer_id']);
      //   $validatedData['ulbId'] = $request->ulb_id;
        $validatedData['tcId'] = 1;
        $validatedData['entityId'] = $ratepayer->entity_id;
        $validatedData['clusterId'] = $ratepayer->cluster_id;
        $validatedData['eventType'] = 'PAYMENT';
        $validatedData['paymentMode'] = 'WHATSAPP';

        $validatedData['rec_ward'] = $data->ward_name ?? '';
        $validatedData['rec_consumerno'] = $data->consumer_no ?? '';
        $validatedData['rec_name'] = $this->truncateString($data->ratepayer_name ?? '',40);
        $validatedData['rec_address'] = $this->truncateString($data->ratepayer_address ?? '',40);
        $validatedData['rec_category'] = $data->category ?? '';
        $validatedData['rec_subcategory'] = $data->sub_category ?? '';
        $validatedData['rec_monthlycharge'] = $data->monthly_demand ?? '';
        $validatedData['rec_amount'] = $validatedData['amount'];
        $validatedData['rec_paymentmode'] = $validatedData['paymentMode'];
        $validatedData['rec_tcname'] = 'BOT';
        $validatedData['rec_tcmobile'] ='';
        $validatedData['rec_chequeno'] = '';
        $validatedData['rec_chequedate'] = '';
        $validatedData['rec_bankname'] = '';
        $validatedData['remarks'] = 'WHATSAPP Payment';
        $validatedData['utrNo'] = '';
        $validatedData['upiId'] = '';

        // Start a transaction to ensure data integrity

        DB::beginTransaction();
        try {

            $tranService->extractRatepayerDetails($validatedData['ratepayerId']);
            $transaction = $tranService->createNewTransaction($validatedData);
            $payment = $tranService->createNewPayment($validatedData, $transaction->id);

            $transaction->payment_id = $payment->id;
            $transaction->rec_receiptno =$payment->receipt_no;
            $transaction->rec_period = $payment->payment_from.' to '.$payment->payment_to;
            $transaction->rec_nooftenants = "1";

            $transaction->save();

            if ($transaction != null) {
                $responseData = [
                    'tranId' => $transaction->id,
                    'consumerNo' => $tranService->ratepayer->consumer_no,
                    'ratepayerName' => $tranService->ratepayer->ratepayer_name,
                    'ratepayerAddress' => $tranService->ratepayer->ratepayer_address,
                    'mobileNo' => $tranService->ratepayer->mobile_no,
                    'landmark' => $tranService->ratepayer->landmark,
                    'longitude' => '',
                    'latitude' => '',
                    'tranType' => '',
                    'pmtMode' => 'WHATSAPP',
                    'pmtAmount' => $validatedData['amount'],
                    //   'remarks' => $validatedData['remarks'],
                ];
                DB::commit();
                return true;
            } else {
                DB::rollBack();
                return false;
            }
        } catch (Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    function truncateString($string, $length = 45) {
      return strlen($string) > $length ? substr($string, 0, $length) . '...' : $string;
    }
   
   

}