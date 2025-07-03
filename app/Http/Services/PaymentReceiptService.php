<?php

namespace App\Http\Services;

use App\Models\Category;
use App\Models\ClusterCurrentDemand;
use App\Models\ClusterDemand;
use App\Models\CurrentDemand;
use App\Models\CurrentTransaction;
use App\Models\Demand;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\Ratepayer;
use App\Models\RatepayerCheque;
use App\Models\RatepayerSchedule;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Ward;
use App\Services\NumberGeneratorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service class for handling transaction-related operations
 * Manages transactions, payments, and demand adjustments
 */
class PaymentReceiptService
{
    public ?CurrentTransaction $transaction = null;
    public ?Payment $payment = null;
    public ?Ratepayer $ratepayer = null;
    public int $demandTillDate = 0;
    public $validatedData = [];
    public $paymentData = [];
    public $paymentFrom = '';
    public $paymentTo = '';

    //Common routine [OK]
    // FL-A
    public function extractRatepayerDetails(array $data):bool
    {
      try {
        $this->ratepayer = Ratepayer::find($data['ratepayer_id']);
        if ($this->ratepayer->entity_id !==null && $this->ratepayer->cluster_id !== null) {
         return false;
        }
        $tc =  User::find($data['tc_id']);
        $ward = Ward::find($this->ratepayer['ward_id']);
        $subCategory = SubCategory::find($this->ratepayer->subcategory_id);
        $category = Category::find($subCategory->category_id);


        $this->validatedData['ulb_id'] = $this->ratepayer->ulb_id;
        $this->validatedData['vendor_receipt'] =$data['transaction_id'];
        $this->validatedData['amount'] = $data['amount'];
        $this->validatedData['tc_id'] = $data['tc_id'];
        $this->validatedData['tc_name'] = $tc->name;
        $this->validatedData['event_type'] = 'PAYMENT';
        $this->validatedData['remarks'] = $data['remarks'];
        $this->validatedData['longitude'] = $data['longitude'];
        $this->validatedData['latitude'] = $data['latitude'];
        $this->validatedData['payment_mode'] = $data['payment_mode'];

         $this->validatedData['rec_ward'] = $ward->ward_name;
         $this->validatedData['rec_consumerno'] = $this->ratepayer->consumer_no;
         $this->validatedData['rec_name'] = $this->ratepayer->ratepayer_name;
         $this->validatedData['rec_address'] = $this->ratepayer->ratepayer_address;
         $this->validatedData['rec_category']  = $category->category;
         $this->validatedData['rec_subcategory'] = $subCategory->sub_category;
         $this->validatedData['rec_monthlycharge'] =$subCategory->rate;
         $this->validatedData['rec_amount'] = $data['amount'];
         $this->validatedData['rec_paymentmode'] = $data['payment_mode'];
         $this->validatedData['rec_tcname'] = $tc->name;
         $this->validatedData['rec_tcmobile'] = '';

         $this->paymentData = [
            'name' => $this->ratepayer->ratepayer_name ?? '',
            'mobile' => $this->ratepayer->mobile_no ?? '',
            'address' => $this->ratepayer->ratepayer_address ?? '',
            // 'transaction_no' => $request->transaction_id ?? '',receipt_no
            'consumer_no' => $tranService->ratepayer->consumer_no ?? '',
            'category' => $category->category,
            'ward_no' => $ward->ward_name ?? '',
            'holding_no' => $this->ratepayer->holding_no ?? '',
            //'type' => $paymentData['type'] ?? '',
            'payment_from' => $this->paymentFrom ?? '',
            'payment_to' => $this->paymentTo ?? '',
            // 'from_date' => now(),
            // 'to_date' => now(),
            'rate_per_month' => $subCategory->rate,
            'amount' => $data['amount'] ?? 0,
            'total' => $data['amount'] ?? 0,
            'payment_mode' => $data['payment_mode'],
            'gst_no' =>  '',
            'pan_no' =>  '',
            'customer_remarks' => '',
            'mobile' => $this->ratepayer->mobile_no ?? '',
         ];

        return true;
      } catch (\Exception $e) {
         return false;
      }
    }

    //Entity Validation [OK]
    // FL-B
   public function getPendingDemand()
   {
      try {
         $pendingDemands = 0;

         if ($this->ratepayer->entity_id !== null) {
               $pendingDemands = DB::table('current_demands')
                  ->where('ratepayer_id', $this->ratepayer->id)
                  ->whereRaw('(bill_month + (bill_year * 12)) <= (MONTH(CURRENT_DATE) + (YEAR(CURRENT_DATE) * 12))')
                  ->where('is_active', 1)
                  ->whereNull('payment_id')
                  ->sum('total_demand');
         } else {
               $pendingDemands = DB::table('cluster_current_demands')
                  ->where('ratepayer_id', $this->ratepayer->id)
                  ->whereRaw('(bill_month + (bill_year * 12)) <= (MONTH(CURRENT_DATE) + (YEAR(CURRENT_DATE) * 12))')
                  ->where('is_active', 1)
                  ->whereNull('payment_id')
                  ->sum('total_demand');
         }

         return [
               'success' => true,
               'message' => 'Pending demand fetched successfully.',
               'pendingDemand' => $pendingDemands,
         ];

      } catch (\Exception $e) {
         return [
               'success' => false,
               'message' => 'Failed to fetch pending demand: ' . $e->getMessage(),
               'pendingDemand' => 0,
         ];
      }
   }

   // FL-C
   public function postPayment():bool
   {
      try {
         DB::beginTransaction();
         $this->createNewTransaction();
         $this->createPaymentRecord();

         if ($this->ratepayer->entity_id !== null) {
             $this->processEntityPendingDemands();
         } else {
             $this->processClusterPendingDemands();
         }
         DB::commit();
         return true;

       } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return false;
        }

   }

    //Common routine
    // FL-C-1
    public function createNewTransaction()
    {
        $transactionNo = app(NumberGeneratorService::class)->generate('transaction_no');
        $data = [
            'ulb_id' => $this->validatedData['ulb_id'],
            'tc_id' => $this->validatedData['tc_id'],
            'transaction_no' => $transactionNo,
            'ratepayer_id' => $this->ratepayer->id,
            'entity_id' => $this->ratepayer->entity_id,
            'cluster_id' => $this->ratepayer->cluster_id,
            'event_time' => now(),
            'event_type' => $this->validatedData['event_type'],
            'remarks' => $this->validatedData['remarks'],
            'longitude' => $this->validatedData['longitude'],
            'latitude' => $this->validatedData['latitude'],
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
        $this->transaction = CurrentTransaction::create($data);

        $this->ratepayer->update([
            'last_transaction_id' => $this->transaction->id,
        ]);
    }

    // FL-C-2
    protected function createPaymentRecord()
    {
        $receiptNo = app(NumberGeneratorService::class)->generate('receipt_no');

        $data = [
         'ulb_id' => $this->validatedData['ulb_id'],
         'ratepayer_id' => $this->ratepayer->id,
         'entity_id' => $this->ratepayer->entity_id,
         'cluster_id' => $this->ratepayer->cluster_id,
         'tc_id' => $this->validatedData['tc_id'],
         'tran_id' => $this->transaction->id,
         'receipt_no' => $receiptNo,
         'payment_date' => now(),
         'payment_mode' => $this->validatedData['payment_mode'],
         'payment_status' => 'PENDING',
         'amount' => $this->validatedData['amount'],
         'payment_verified' => false,
         'refund_initiated' => false,
         'refund_verified' => false,
         'vrno' => 0,
         // 'payment_from'
         // 'payment_to'
     ];
     

     if (isset($this->validatedData['rec_chequeno'])) {
      $data['cheque_number'] = $this->validatedData['rec_chequeno'];
     }
     if (isset($this->validatedData['rec_upiId'])) {
      $data['upi_id'] = $this->validatedData['rec_upiId'];
     }

     if (isset($this->validatedData['rec_bankname'])) {
      $data['bank_name'] = $this->validatedData['rec_bankname'];
     }
     if (isset($this->validatedData['rec_utrNo'])) {
      $data['neft_id'] = $this->validatedData['rec_utrNo'];
      if (isset($this->validatedData['rec_chequedate'])) {
         $data['neft_date'] = $this->validatedData['rec_chequedate'];
        }
     }

     if (isset($this->validatedData['rec_chequedate'])) {
      $data['neft_date'] = $this->validatedData['rec_chequedate'];
     }

     // Conditionally include vendor_receipt if it exists
     if (array_key_exists('transaction_id', $this->validatedData)) {
         $data['vendor_receipt'] = $this->validatedData['transaction_id'];
     }
     
     $this->paymentData['receipt_no'] = $receiptNo; 

     $this->payment = Payment::create($data);
     $this->transaction->payment_id = $this->payment->id;
    }

    // FL-C-3
    protected function processEntityPendingDemands()
    {
        $pendingDemands = $this->getEntityPendingDemands($this->ratepayer->id);
        $this->demandTillDate = $pendingDemands->sum('total_demand');

        $remainingAmount = $this->validatedData['amount'];

        $mFlag = true;
        $startMonth = "";
        $endMonth = "";

        foreach ($pendingDemands as $demand) {
            $outstandingAmount = $demand->demand - $demand->payment;

            if ($remainingAmount >= $outstandingAmount) {
                $this->adjustEntityDemand($demand, $outstandingAmount, $this->payment->id, $this->validatedData['tc_id']);
                $remainingAmount -= $outstandingAmount;
                // Transfer record to `demand` table
                $this->transferToDemandTable($demand);
            } else {
                break; // Partial payments not allowed
            }
            if($mFlag){
               $startMonth = \Carbon\Carbon::createFromDate($demand->bill_year, $demand->bill_month, 1)->format('M-Y');
               $endMonth = \Carbon\Carbon::createFromDate($demand->bill_year, $demand->bill_month, 1)->format('M-Y');
               $mFlag = false;
            } else {
               $endMonth = \Carbon\Carbon::createFromDate($demand->bill_year, $demand->bill_month, 1)->format('M-Y');
            }
        }
        // dd($remainingAmount);
        if ($remainingAmount > 0) {
            throw new Exception('Payment amount must fully cover one or more pending demands.');
        }
      //   $ratepayer = Ratepayer::find($this->ratepayer->id);
        $this->ratepayer->lastpayment_amt = $this->validatedData['amount'];
        $this->ratepayer->lastpayment_date = now();
        $this->ratepayer->lastpayment_mode = $this->payment->payment_mode;
        $this->ratepayer->save();

        $this->paymentFrom = $startMonth;
        $this->paymentTo = $endMonth;

    }

    // FL-C-3-1
    protected function getEntityPendingDemands(int $ratepayerId): Collection
    {
        return CurrentDemand::where('ratepayer_id', $ratepayerId)
            ->where('is_active', true)
            ->whereRaw('ifnull(demand,0) > ifnull(payment,0)')
            ->orderBy('bill_year')
            ->orderBy('bill_month')
            ->get();
    }

    // FL-C-3-2
    protected function adjustEntityDemand(CurrentDemand $demand, float $amount, int $paymentId, int $tcId): void
    {
        $demand->payment += $amount;
        $demand->payment_id = $paymentId;
        $demand->tc_id = $tcId;
        //   $demand->last_payment_date = now();
        $demand->save();
    }


    // FL-C-3-3 && FC-4-3
    protected function transferToDemandTable($demand): void
    {
        // Insert the record into `demand` table
        Demand::create($demand->toArray());

        // Delete the record from `current_demand` table
        $demand->delete();
    }



    /**
     * ************************* CLUSTER PAYMENTS ****************************************
     */

    // FC-C-4
    function processClusterPendingDemands()
    {
      $clusterDemands = $this->getClusterAdjustableDemands($this->ratepayer->id, $this->validatedData['amount']);

       usort($clusterDemands, function ($a, $b) {
         $aDate = $a->bill_year * 100 + $a->bill_month;
         $bDate = $b->bill_year * 100 + $b->bill_month;
         return $aDate <=> $bDate;
       });

      foreach ($clusterDemands as $demand)
      {
         $ratepayer = Ratepayer::find($demand->ratepayer_id);
         $entityDemands = $this->getClusterCurrentDemands($ratepayer->cluster_id, $demand->bill_year, $demand->bill_month);
         foreach ($entityDemands as $entityDemand)
         {
            $entityDemand->tc_id = $this->validatedData['tc_id'];
            $entityDemand->payment = $entityDemand->total_demand;
            $entityDemand->payment_id = $this->payment->id;
            $this->transferToDemandTable($entityDemand);
            // Now move to demands table
         }
         $demand->tc_id= $this->validatedData['tc_id'];
         $demand->payment = $entityDemand->total_demand;
         $demand->payment_id = $this->payment->id;
         $this->transferToClusterDemandTable($demand);
         // Now move to cluster_demands
      }
      $from = Carbon::createFromDate($clusterDemands[0]->bill_year, $clusterDemands[0]->bill_month)->format('M-Y');
      $to   = Carbon::createFromDate(end($clusterDemands)->bill_year, end($clusterDemands)->bill_month)->format('M-Y');
      return ['from' => $from, 'to' => $to];
    }

    // FC-C-4-1
    function getClusterAdjustableDemands($ratepayerId, $amount)
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
         return ($runningTotal == $amount) ? $result : [];
    }


    // FC-C-4-2
    public function getClusterCurrentDemands(int $clusterId, int $billYear, int $billMonth)
    {
      // return DB::table('entities as e')
      //   ->join('ratepayers as r', 'e.ratepayer_id', '=', 'r.id')
      //   ->join('current_demands as d', 'e.ratepayer_id', '=', 'd.ratepayer_id')
      //   ->where('r.cluster_id', $clusterId)
      //   ->where('d.bill_year', $billYear)
      //   ->where('d.bill_month', $billMonth)
      //   ->select('d.*')
      //   ->get();
      return CurrentDemand::whereHas('ratepayer', function ($query) use ($clusterId) {
           $query->where('cluster_id', $clusterId);
      })
      ->where('bill_year', $billYear)
      ->where('bill_month', $billMonth)
      ->whereHas('ratepayer.entity') // optional, if you want to ensure entity exists
      ->get();
    }


    //FC-C-4-4
    protected function transferToClusterDemandTable($demand): void
    {
        // Insert the record into `demand` table
        ClusterDemand::create($demand->toArray());

        // Delete the record from `current_demand` table
        $demand->delete();
    }


}
