<?php

namespace App\Http\Services;

use App\Models\ClusterCurrentDemand;
use App\Models\ClusterDemand;
use App\Models\CurrentTransaction;
use App\Models\Demand;
use App\Models\Payment;
use App\Models\Ratepayer;
use App\Services\NumberGeneratorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClusterPaymentService
{

    public ?Payment $payment = null;

    /**
     * Current ratepayer instance
     */
    public ?Ratepayer $ratepayer = null;

    public int $demandTillDate = 0;

   function truncateString($string, $length = 45) {
      return strlen($string) > $length ? substr($string, 0, $length) . '...' : $string;
   }

   // Functino Code A
   //ratepayer_id, tc_id,payment_mode,amount,tc_name, cheque_no, cheque_date, bank_name, remarks, utr_no, upi_id
   public function makePayment(array $validatedData): bool
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

        $this->ratepayer = Ratepayer::find($validatedData['ratepayer_id']);
        $validatedData['ulbId'] = $this->ratepayer->ulb_id;
        $validatedData['tcId'] = $validatedData['tc_id'];
        $validatedData['entityId'] = $this->ratepayer->entity_id;
        $validatedData['clusterId'] = $this->ratepayer->cluster_id;
        $validatedData['eventType'] = 'PAYMENT';
        $validatedData['paymentMode'] =$validatedData['payment_mode'];

        $validatedData['rec_ward'] = $data->ward_name ?? '';
        $validatedData['rec_consumerno'] = $data->consumer_no ?? '';
        $validatedData['rec_name'] = $this->truncateString($data->ratepayer_name ?? '',40);
        $validatedData['rec_address'] = $this->truncateString($data->ratepayer_address ?? '',40);
        $validatedData['rec_category'] = $data->category ?? '';
        $validatedData['rec_subcategory'] = $data->sub_category ?? '';
        $validatedData['rec_monthlycharge'] = $data->monthly_demand ?? '';
        $validatedData['rec_amount'] = $validatedData['amount'];
        $validatedData['rec_paymentmode'] = $validatedData['payment_mode'];
        $validatedData['rec_tcname'] = $validatedData['tc_name'];
        $validatedData['rec_tcmobile'] ='';
        $validatedData['rec_chequeno'] = $validatedData['cheque_no'];
        $validatedData['rec_chequedate'] = $validatedData['cheque_date'];
        $validatedData['rec_bankname'] = $validatedData['bank_name'];
        $validatedData['remarks'] = $validatedData['remarks'];
        $validatedData['utrNo'] = $validatedData['utr_no'];
        $validatedData['upiId'] = $validatedData['upi_id'];

        // Start a transaction to ensure data integrity

        DB::beginTransaction();
        try {

            // $this->extractRatepayerDetails($validatedData['ratepayerId']);
            $transaction = $this->createNewTransaction($validatedData);
            // $payment = $this->createNewPayment($validatedData, $transaction->id);
            $payment = $this->createPaymentRecord($validatedData, $transaction->id);
            $period = $this->processDemand($this->ratepayer->id, $validatedData['amount'], $payment->id, $validatedData['tc_id']);

            $transaction->payment_id = $payment->id;
            $transaction->rec_receiptno =$payment->receipt_no;
            $transaction->rec_period = $period['from'] . ' to '.$period['to'];// $payment->payment_from.' to '.$payment->payment_to;
            $transaction->rec_nooftenants = "1";

            $transaction->save();

            if ($transaction != null) {
                $responseData = [
                    'tranId' => $transaction->id,
                    'consumerNo' => $this->ratepayer->consumer_no,
                    'ratepayerName' => $this->ratepayer->ratepayer_name,
                    'ratepayerAddress' => $this->ratepayer->ratepayer_address,
                    'mobileNo' => $this->ratepayer->mobile_no,
                    'landmark' => $this->ratepayer->landmark,
                    'longitude' => $validatedData['longitude'],
                    'latitude' => $validatedData['latitude'],
                    'tranType' => $transaction->event_type,
                    'pmtMode' => $validatedData['paymentMode'],
                    'pmtAmount' => $validatedData['amount'],
                    //   'remarks' => $validatedData['remarks'],
                ];
                DB::commit();

                //Broadcast transaction to Dashboard
               //  broadcast(new SiteVisitedEvent(
               //      $responseData,
               //  ))->toOthers();

                return false;
            } else {
                DB::rollBack();
                return false;
            }
        } catch (Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    //Function Code A.2
    private function createNewTransaction(array $validatedData): CurrentTransaction
    {
        $transactionNo = app(NumberGeneratorService::class)->generate('transaction_no');
      //   $ratepayer = Ratepayer::find($validatedData['ratepayerId']);
        $data = [
            'ulb_id' => $validatedData['ulbId'],
            'tc_id' => $validatedData['tcId'],
            'transaction_no' => $transactionNo,
            'ratepayer_id' => $validatedData['ratepayerId'],
            'entity_id' => $validatedData['entityId'],
            'cluster_id' => $validatedData['clusterId'],
            'event_time' => now(),
            'event_type' => $validatedData['eventType'],
            'remarks' => $validatedData['remarks'],
            'longitude' => $validatedData['longitude'],
            'latitude' => $validatedData['latitude'],
            'is_verified' => 0,
            'is_cancelled' => 0,

            // 'rec_receiptno'
            // 'rec_period' => $validatedData['rec_period'],
            'vrno' => 0,
        ];

        if (isset($validatedData['rec_ward'])) {
         $data['rec_ward'] = $validatedData['rec_ward'];
        }
        if (isset($validatedData['rec_consumerno'])) {
         $data['rec_consumerno'] = $validatedData['rec_consumerno'];
        }
        if (isset($validatedData['rec_name'])) {
         $data['rec_name'] = $validatedData['rec_name'];
        }
        if (isset($validatedData['rec_address'])) {
         $data['rec_address'] = $validatedData['rec_address'];
        }
        if (isset($validatedData['rec_category'])) {
         $data['rec_category'] = $validatedData['rec_category'];
        }
        if (isset($validatedData['rec_subcategory'])) {
         $data['rec_subcategory'] = $validatedData['rec_subcategory'];
        }
        if (isset($validatedData['rec_monthlycharge'])) {
         $data['rec_monthlycharge'] = $validatedData['rec_monthlycharge'];
        }
        if (isset($validatedData['rec_amount'])) {
         $data['rec_amount'] = $validatedData['rec_amount'];
        }
        if (isset($validatedData['rec_paymentmode'])) {
         $data['rec_paymentmode'] = $validatedData['rec_paymentmode'];
        }
        if (isset($validatedData['rec_tcname'])) {
         $data['rec_tcname'] = $validatedData['rec_tcname'];
        }
        if (isset($validatedData['rec_tcmobile'])) {
         $data['rec_tcmobile'] = $validatedData['rec_tcmobile'];
        }
        if (isset($validatedData['rec_chequeno'])) {
         $data['rec_chequeno'] = $validatedData['rec_chequeno'];
        }
        if (isset($validatedData['utrNo'])) {
         $data['rec_chequeno'] = $validatedData['utrNo'];
        }
        if (isset($validatedData['rec_chequedate'])) {
         $data['rec_chequedate'] = $validatedData['rec_chequedate'];
        }
        if (isset($validatedData['rec_bankname'])) {
         $data['rec_bankname'] = $validatedData['rec_bankname'];
        }

        // Payment Denial
        if (isset($validatedData['denialReasonId'])) {
            $data['denial_reason_id'] = $validatedData['denialReasonId'];
        }

        // Cancellation
        if (isset($validatedData['isCancelled'])) {
            $data['is_cancelled'] = $validatedData['isCancelled'];
            $data['cancelledby_id'] = $validatedData['tcId'];
            $data['cancellation_date'] = now();
        }

        // Scheduling
        if (isset($validatedData['schedule_date'])) {
            $data['schedule_date'] = $validatedData['schedule_date'];
        }

        if (isset($validatedData['image'])) {
            $file = $validatedData['image'];
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs(
                'uploads/images',
                $fileName,
                'public'
            );
            $data['image_path'] = $fileName;
        }

        // Create the record
        $currentTransaction = CurrentTransaction::create($data);

        $this->ratepayer->update([
            'last_transaction_id' => $currentTransaction->id,
        ]);

        return $currentTransaction;
    }

    // Function Code A.3
    protected function createPaymentRecord(array $validatedData, int $tranId): Payment
    {
        $receiptNo = app(NumberGeneratorService::class)->generate('receipt_no');

        $data = [
         'ulb_id' => $validatedData['ulbId'],
         'ratepayer_id' => $validatedData['ratepayerId'],
         'entity_id' => $validatedData['entityId'],
         'cluster_id' => $validatedData['clusterId'],
         'tc_id' => $validatedData['tcId'],
         'tran_id' => $tranId,
         'receipt_no' => $receiptNo,
         'payment_date' => now(),
         'payment_mode' => $validatedData['paymentMode'],
         'payment_status' => 'PENDING',
         'amount' => $validatedData['amount'],
         'payment_verified' => false,
         'refund_initiated' => false,
         'refund_verified' => false,
         'vrno' => 0,
         // 'payment_from'
         // 'payment_to'
     ];
     

     if (isset($validatedData['rec_chequeno'])) {
      $data['cheque_number'] = $validatedData['rec_chequeno'];
     }
     if (isset($validatedData['rec_upiId'])) {
      $data['upi_id'] = $validatedData['rec_upiId'];
     }

     if (isset($validatedData['rec_bankname'])) {
      $data['bank_name'] = $validatedData['rec_bankname'];
     }
     if (isset($validatedData['rec_utrNo'])) {
      $data['neft_id'] = $validatedData['rec_utrNo'];
      if (isset($validatedData['rec_chequedate'])) {
         $data['neft_date'] = $validatedData['rec_chequedate'];
        }
     }

     if (isset($validatedData['rec_chequedate'])) {
      $data['neft_date'] = $validatedData['rec_chequedate'];
     }

     // Conditionally include vendor_receipt if it exists
     if (array_key_exists('vendorReceipt', $validatedData)) {
         $data['vendor_receipt'] = $validatedData['vendorReceipt'];
     }
     
     return Payment::create($data);
    }

   
    //Function Code A.4
    function processDemand(int $ratepayerId, int $amount, int $paymentId, int $tcId)
    {
      $clusterDemands = $this->getAdjustableDemands($ratepayerId, $amount);

       usort($clusterDemands, function ($a, $b) {
         $aDate = $a->bill_year * 100 + $a->bill_month;
         $bDate = $b->bill_year * 100 + $b->bill_month;
         return $aDate <=> $bDate;
       });

      foreach ($clusterDemands as $demand)
      {
         $ratepayer = Ratepayer::find($demand->ratepayer_id);
         $entityDemands = $this->getCurrentDemands($ratepayer->cluster_id, $demand->bill_year, $demand->bill_month);
         foreach ($entityDemands as $entityDemand)
         {
            $entityDemand->tc_id=$tcId;
            $entityDemand->payment = $entityDemand->total_demand;
            $entityDemand->payment_id=$paymentId;
            $this->transferToDemandTable($entityDemand);
            // Now move to demands table
         }
         $demand->tc_id=$tcId;
         $demand->payment = $entityDemand->total_demand;
         $demand->payment_id=$paymentId;
         $this->transferToClusterDemandTable($demand);
         // Now move to cluster_demands
      }
      $from = Carbon::createFromDate($clusterDemands[0]->bill_year, $clusterDemands[0]->bill_month)->format('M-Y');
      $to   = Carbon::createFromDate(end($clusterDemands)->bill_year, end($clusterDemands)->bill_month)->format('M-Y');
      return ['from' => $from, 'to' => $to];
    }

    // Function Code A.4.1
    function getAdjustableDemands($ratepayerId, $amount)
    {
         $demands = ClusterCurrentDemand::where('ratepayer_id', $ratepayerId)
            ->where('is_active', 1)
            ->whereNull('payment_id')
            ->orderBy('bill_year')
            ->orderBy('bill_month')
            ->get();

         $result = [];
         $runningTotal = 0;

         foreach ($demands as $demand) {
            $rowAmount = (int) $demand->total_demand;

            if ($runningTotal + $rowAmount <= $amount) {
                  $result[] = $demand;
                  $runningTotal += $rowAmount;

                  if ($runningTotal === $amount) {
                     break;
                  }
            } else {
                  // Skip this one if it would exceed the total
                  continue;
            }
         }
         return ($runningTotal === $amount) ? $result : [];
    }


    // Function Code A.4.2
    public function getCurrentDemands(int $clusterId, int $billYear, int $billMonth)
    {
      return DB::table('entities as e')
        ->join('ratepayers as r', 'e.ratepayer_id', '=', 'r.id')
        ->join('current_demands as d', 'e.ratepayer_id', '=', 'd.ratepayer_id')
        ->where('r.cluster_id', $clusterId)
        ->where('d.bill_year', $billYear)
        ->where('d.bill_month', $billMonth)
        ->select('d.*')
        ->get();
    }

   //  // Function Code A.4.3
   //  protected function transferToDemandTableDiscarded($demand): void
   //  {
   //     Demand::create($demand->toArray());
   //     // Delete the record from `current_demand` table
   //     $demand->delete();
   //  }

    protected function transferToDemandTable($demand): void
      {
         try {
            Demand::create((array) $demand);
            // Delete the record from `current_demand` table
            DB::table('current_demands')->where('id', $demand->id)->delete();
         } catch (Exception $e) {
            Log::error($e->getMessage());
         }
      }


    // Function Code A.4.4
    protected function transferToClusterDemandTable($demand): void
    {
        // Insert the record into `demand` table
        ClusterDemand::create($demand->toArray());

        // Delete the record from `current_demand` table
        $demand->delete();
    }




   //  //Discarded
   //  public function createNewPayment(array $validatedData, int $tranId): Payment
   //  {
   //      // Create payment record
   //      $payment = $this->createPaymentRecord($validatedData, $tranId);
   //      // dd(collect($payment)->toArray());

   //      // Process and adjust demands
   //      $billPeriod = $this->processPendingDemands($validatedData['ratepayerId'], $validatedData['amount'], $payment, $validatedData['tcId']);

   //      $payment['payment_from'] = $billPeriod[0];
   //      $payment['payment_to'] = $billPeriod[1];

   //      $payment->payment_from =$billPeriod[0];
   //      $payment->payment_to =$billPeriod[1];
   //      $payment->save();

   //      return $payment;
   //  }
   

   //  // Discarded
   //  protected function processPendingDemands(int $ratepayerId, float $amount, Payment $payment, int $tcId): array
   //  {
   //      $pendingDemands = $this->getPendingDemands($ratepayerId);
   //      $this->demandTillDate = $pendingDemands->sum('total_demand');

   //      $remainingAmount = $amount;

   //      $mFlag = true;
   //      $startMonth = "";
   //      $endMonth = "";

   //      foreach ($pendingDemands as $demand) {
   //          $outstandingAmount = $demand->demand - $demand->payment;

   //          if ($remainingAmount >= $outstandingAmount) {
   //              $this->adjustDemand($demand, $outstandingAmount, $payment->id, $tcId);
   //              $remainingAmount -= $outstandingAmount;
   //              // Transfer record to `demand` table
   //              $this->transferToDemandTable($demand);
   //          } else {
   //              break; // Partial payments not allowed
   //          }
   //          if($mFlag){
   //             $startMonth = \Carbon\Carbon::createFromDate($demand->bill_year, $demand->bill_month, 1)->format('M-Y');
   //             $endMonth = \Carbon\Carbon::createFromDate($demand->bill_year, $demand->bill_month, 1)->format('M-Y');
   //             $mFlag = false;
   //          } else {
   //             $endMonth = \Carbon\Carbon::createFromDate($demand->bill_year, $demand->bill_month, 1)->format('M-Y');
   //          }
   //      }
   //      // dd($remainingAmount);
   //      if ($remainingAmount > 0) {
   //          throw new Exception('Payment amount must fully cover one or more pending demands.');
   //      }
   //      $ratepayer = Ratepayer::find($ratepayerId);
   //      $ratepayer->lastpayment_amt = $amount;
   //      $ratepayer->lastpayment_date = now();
   //      $ratepayer->lastpayment_mode = $payment->payment_mode;
   //      $ratepayer->save();
   //      return [$startMonth, $endMonth];
   //  }

   //  //Discarded
   //  protected function getPendingDemands(int $ratepayerId): Collection
   //  {
   //      return CurrentDemand::where('ratepayer_id', $ratepayerId)
   //          ->where('is_active', true)
   //          ->whereRaw('ifnull(demand,0) > ifnull(payment,0)')
   //          ->orderBy('bill_year')
   //          ->orderBy('bill_month')
   //          ->get();
   //  }

   //  //Discarded
   //  protected function adjustDemand(CurrentDemand $demand, float $amount, int $paymentId, int $tcId): void
   //  {
   //      $demand->payment += $amount;
   //      $demand->payment_id = $paymentId;
   //      $demand->tc_id = $tcId;
   //      //   $demand->last_payment_date = now();
   //      $demand->save();
   //  }

}