<?php

namespace App\Http\Services;

use App\Models\CurrentDemand;
use App\Models\CurrentTransaction;
use App\Models\Demand;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\Ratepayer;
use App\Models\RatepayerCheque;
use App\Models\RatepayerSchedule;
use App\Services\NumberGeneratorService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Service class for handling transaction-related operations
 * Manages transactions, payments, and demand adjustments
 */
class TransactionService
{
    /**
     * Current transaction instance
     */
    public ?CurrentTransaction $transaction = null;

    /**
     * Current payment instance
     */
    public ?Payment $payment = null;

    /**
     * Current ratepayer instance
     */
    public ?Ratepayer $ratepayer = null;

    public int $demandTillDate = 0;

    public function extractRatepayerDetails(int $id)
    {
        $this->ratepayer = Ratepayer::find($id);
    }

    /**
     * Create a new transaction record
     *
     * @param  array  $validatedData  Validated input data
     */
    public function createNewTransaction(array $validatedData): CurrentTransaction
    {
        $transactionNo = app(NumberGeneratorService::class)->generate('transaction_no');
        $ratepayer = Ratepayer::find($validatedData['ratepayerId']);
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
            'vrno' => 1,
        ];

        // Payment Mode
        if (isset($validatedData['payment_mode'])) {
            $data['payment_mode'] = $validatedData['paymentMode'];
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

        $ratepayer->update([
            'last_transaction_id' => $currentTransaction->id,
        ]);

        return $currentTransaction;
    }

    /**
     * Create a new payment and adjust demands
     *
     * @param  array  $validatedData  Validated input data
     * @param  int  $tranId  Transaction ID
     *
     * @throws Exception
     */
    public function createNewPayment(array $validatedData, int $tranId): Payment
    {
        // Create payment record
        $payment = $this->createPaymentRecord($validatedData, $tranId);
        // dd(collect($payment)->toArray());

        // Process and adjust demands
        $billPeriod = $this->processPendingDemands($validatedData['ratepayerId'], $validatedData['amount'], $payment, $validatedData['tcId']);

        $payment['payment_from'] = $billPeriod[0];
        $payment['payment_to'] = $billPeriod[1];

        $payment->save();

        return $payment;
    }

    /**
     * Process pending demands and adjust payments
     *
     * @throws Exception
     */
    protected function processPendingDemands(int $ratepayerId, float $amount, Payment $payment, int $tcId): array
    {
        $pendingDemands = $this->getPendingDemands($ratepayerId);
        $this->demandTillDate = $pendingDemands->sum('total_demand');

        $firstPeriod = '';
        $lastPeriod = '';

        if ($pendingDemands->isNotEmpty()) {
            $sorted = $pendingDemands->sortBy([
                ['bill_year', 'asc'],
                ['bill_month', 'asc'],
            ]);

            $first = $sorted->first();
            $last = $sorted->last();

            $firstPeriod = \Carbon\Carbon::createFromDate($first->bill_year, $first->bill_month)->format('M-Y');
            $lastPeriod = \Carbon\Carbon::createFromDate($last->bill_year, $last->bill_month)->format('M-Y');
        }

        $remainingAmount = $amount;

        foreach ($pendingDemands as $demand) {
            $outstandingAmount = $demand->demand - $demand->payment;

            if ($remainingAmount >= $outstandingAmount) {
                $this->adjustDemand($demand, $outstandingAmount, $payment->id, $tcId);
                $remainingAmount -= $outstandingAmount;
                // Transfer record to `demand` table
                $this->transferToDemandTable($demand);
            } else {

                break; // Partial payments not allowed
            }
        }
        // dd($remainingAmount);
        if ($remainingAmount > 0) {
            throw new Exception('Payment amount must fully cover one or more pending demands.');
        }
        $ratepayer = Ratepayer::find($ratepayerId);
        $ratepayer->lastpayment_amt = $amount;
        $ratepayer->lastpayment_date = now();
        $ratepayer->lastpayment_mode = $payment->payment_mode;
        $ratepayer->save();
        return [$firstPeriod, $lastPeriod];
    }

    /**
     * Get pending demands for a ratepayer
     */
    protected function getPendingDemands(int $ratepayerId): Collection
    {
        return CurrentDemand::where('ratepayer_id', $ratepayerId)
            ->where('is_active', true)
            ->whereRaw('ifnull(demand,0) > ifnull(payment,0)')
            ->orderBy('bill_year')
            ->orderBy('bill_month')
            ->get();
    }

    /**
     * Adjust an individual demand record
     */
    protected function adjustDemand(CurrentDemand $demand, float $amount, int $paymentId, int $tcId): void
    {
        $demand->payment += $amount;
        $demand->payment_id = $paymentId;
        $demand->tc_id = $tcId;
        //   $demand->last_payment_date = now();
        $demand->save();
    }

    /**
     * Create payment record
     */
    protected function createPaymentRecord(array $validatedData, int $tranId): Payment
    {
        $receiptNo = app(NumberGeneratorService::class)->generate('receipt_no');
        // dd($receiptNo);
        return Payment::create([
            'ulb_id' => $validatedData['ulbId'],
            'receipt_no' => $receiptNo,
            'vendor_receipt' => $validatedData['vendorReceipt'],
            'ratepayer_id' => $validatedData['ratepayerId'],
            'tc_id' => $validatedData['tcId'],
            'entity_id' => $validatedData['entityId'],
            'cluster_id' => $validatedData['clusterId'],
            'tran_id' => $tranId,
            'payment_status' => 'COMPLETED',
            'payment_verified' => false,
            'refund_initiated' => false,
            'refund_verified' => false,
            'payment_date' => now(),
            'payment_mode' => $validatedData['paymentMode'],
            'vrno' => 1,
            'amount' => $validatedData['amount'],
        ]);
    }


    /**
     * Transfer a record from current_demand to demand table
     */
    protected function transferToDemandTable($demand): void
    {
        // Insert the record into `demand` table
        Demand::create($demand->toArray());

        // Delete the record from `current_demand` table
        $demand->delete();
    }

    /**
     * Create a new payment order for gateway transactions
     */
    public function createPaymentOrder(array $validatedData): PaymentOrder
    {
        // Implement payment order creation logic
        return new PaymentOrder;
    }

    /**
     * Record a complete transaction with optional payment
     */
    public function recordTransaction(array $validatedData): bool
    {
        try {
            $this->transaction = $this->createNewTransaction($validatedData);

            if ($validatedData['eventType'] === 'PAYMENT') {
                $this->payment = $this->createNewPayment($validatedData, $this->transaction->id);
            }

            return true;
        } catch (Exception $e) {
            // Log the error here
            return false;
        }
    }

    public function updateScheduleDate($validatedData)
    {
        // Find the record for the given ratepayer
        $ratepayer = Ratepayer::findOrFail($validatedData['ratepayerId']);

        // Update the schedule date
        $ratepayer->schedule_date = $validatedData['scheduleDate'];
        $ratepayer->save();

        $ratepayerSchedule = RatepayerSchedule::create([
            'ulb_id' => $validatedData['ulbId'],
            'tc_id' => $validatedData['tcId'],
            'ratepayer_id' => $validatedData['ratepayerId'],
            'schedule_date' => $validatedData['scheduleDate'],
        ]);
    }

    public function tcMonthTransactionSummary()
    {
        $userId = Auth::user()->id;
        $user = Auth::user();

        $response = [
            'tcName' => $user->name,
            'profilePicture' => $user->profile_picture,
            'role' => $user->role,
            'currentMonthCollection' => DB::table('payments')
                ->select('payment_mode', DB::raw('SUM(amount) as collection'))
                ->whereYear('payment_date', now()->year)
                ->whereMonth('payment_date', now()->month)
                ->groupBy('payment_mode')
                ->get(),

            'totalRatepayers' => DB::table('ratepayers as r')
                ->selectRaw('COUNT(r.id) as totalRatepayers')
                ->whereIn('r.paymentzone_id', function ($query) use ($userId) {
                    $query->select('paymentzone_id')
                        ->from('tc_has_zones')
                        ->where('tc_id', $userId)
                        ->where('is_active', true);
                })
                ->value('totalRatepayers'),

            'monthSettledDemand' => DB::table('ratepayers as r')
                ->join('demands as c', 'c.ratepayer_id', '=', 'r.id')
                ->selectRaw('SUM(c.total_demand) as totalDemand')
                ->whereYear('c.bill_year', '=', DB::raw('YEAR(CURDATE())'))
                ->whereMonth('c.bill_month', '=', DB::raw('MONTH(CURDATE())'))
                ->whereIn('r.paymentzone_id', function ($query) use ($userId) {
                    $query->select('paymentzone_id')
                        ->from('tc_has_zones')
                        ->where('tc_id', $userId)
                        ->where('is_active', true);
                })
                ->value('totalDemand'),

            'monthDueDemand' => DB::table('ratepayers as r')
                ->join('current_demands as c', 'c.ratepayer_id', '=', 'r.id')
                ->selectRaw('SUM(c.total_demand) as totalDemand')
                ->whereColumn('c.bill_year', '<=', DB::raw('YEAR(CURDATE())'))
                ->whereColumn('c.bill_month', '<=', DB::raw('MONTH(CURDATE())'))
                ->whereIn('r.paymentzone_id', function ($query) use ($userId) {
                    $query->select('paymentzone_id')
                        ->from('tc_has_zones')
                        ->where('tc_id', $userId)
                        ->where('is_active', true);
                })
                ->value('totalDemand'),

            'lastTransactions' => DB::table('current_transactions as c')
                ->select([
                    'c.id as tranId',
                    DB::raw("DATE_FORMAT(c.event_time, '%d/%m/%Y %h:%i %p') as `timestamp`"),
                    'c.event_type as type',
                    'r.ratepayer_name as name',
                    'r.ratepayer_address as address',
                    'r.Consumer_no as uniqueId',
                    'r.holding_no as holdingNo',
                    'r.usage_type as usageType',
                    'r.reputation',
                    'r.lastpayment_date as lastPayment',
                    'c.remarks',
                    'r.lastpayment_amt as payment',
                    'r.lastpayment_mode as paymentMode',
                ])
                ->join('ratepayers as r', 'c.ratepayer_id', '=', 'r.id')
                ->where('c.tc_id', $userId)
                ->orderByDesc('c.event_time')
                ->limit(100)
                ->get(),
        ];

        return $response;
    }

    public function ratepayerTransactions(int $ratepayerId)
    {
        return DB::table('current_transactions as c')
            ->selectRaw("DATE_FORMAT(c.event_time, '%d/%m/%Y %h:%i %p') as event_time")
            ->selectRaw('c.event_type')
            ->selectRaw('p.amount as paid')
            ->selectRaw("DATE_FORMAT(c.schedule_date, '%d/%m/%Y') as schedule_date")
            ->selectRaw('c.remarks')
            ->leftJoin('payments as p', 'p.tran_id', '=', 'c.id')
            ->where('c.ratepayer_id', $ratepayerId)
            ->orderByDesc('c.event_time')
            ->get();
    }

    public function createChequeRecord(array $validatedData, int $tranId)
    {
        return RatepayerCheque::create([
            'ulb_id' => $validatedData['ulbId'],
            'ratepayer_id' => $validatedData['ratepayerId'],
            'tran_id' => $tranId,
            'cheque_no' => $validatedData['chequeNo'],
            'cheque_date' => $validatedData['chequeDate'],
            'bank_name' => $validatedData['bankName'],
            'amount' => $validatedData['amount'],
        ]);
    }

    public function zoneTransactionSummary(int $zoneId)
    {
        $user = Auth::user();

        $result = DB::table('payments as p')
            ->join('ratepayers as r', 'p.ratepayer_id', '=', 'r.id')
            ->where('r.paymentzone_id', $zoneId)
            ->selectRaw('
            SUM(p.amount) as totalReceipts,
            SUM(IF(YEAR(p.created_at) = YEAR(CURRENT_DATE), p.amount, 0)) as yearReceipts,
            SUM(IF(YEAR(p.created_at) = YEAR(CURRENT_DATE) AND MONTH(p.created_at) = MONTH(CURRENT_DATE), p.amount, 0)) as monthReceipts
         ')
            ->first();

        $result1 = DB::table('ratepayers as r')
            ->where('r.is_active', true)
            ->where('r.paymentzone_id', $zoneId)
            ->selectRaw('
            COUNT(r.entity_id) as entityCount,
            COUNT(r.cluster_id) as clusterCount,
            COUNT(r.id) as totalCount,
            SUM(IF(r.usage_type = "Residential", 1, 0)) as Residential,
            SUM(IF(r.usage_type = "Commercial", 1, 0)) as Commercial,
            SUM(IF(r.usage_type = "Industrial", 1, 0)) as Industrial,
            SUM(IF(r.usage_type = "Institutional", 1, 0)) as Institutional,
            SUM(IF(r.reputation = 4, 1, 0)) as PlatinumEntity,
            SUM(IF(r.reputation = 3, 1, 0)) as GoldEntity,
            SUM(IF(r.reputation = 2, 1, 0)) as SilverEntity,
            SUM(IF(r.reputation = 1, 1, 0)) as BasicEntity
         ')
            ->first();

        $result3 = DB::table('ratepayers as r')
            ->join('sub_categories as s', 'r.subcategory_id', '=', 's.id')
            ->join('categories as c', 's.category_id', '=', 'c.id')
            ->where('r.is_active', true)
            ->where('r.paymentzone_id', $zoneId)
            ->groupBy('s.id')
            ->select([
                's.id',
                'c.category as category',
                's.sub_category as subCategory',
                DB::raw('COUNT(r.id) as totalCount'),
            ])
            ->get();

        $response = ['categorySummary' => $result3];

        $response = array_merge(
            [
                'tcName' => $user->name,
                'profilePicture' => $user->profile_picture,
                'totalReceipts' => $result->totalReceipts,
                'yearReceipts' => $result->yearReceipts,
                'monthReceipts' => $result->monthReceipts,
            ],
            [
                'entityCount' => $result1->entityCount,
                'clusterCount' => $result1->clusterCount,
                'totalCount' => $result1->totalCount,
                'Commercial' => $result1->Commercial,
                'Industrial' => $result1->Industrial,
                'Institutional' => $result1->Institutional,
                'PlatinumEntity' => $result1->PlatinumEntity,
                'GoldEntity' => $result1->GoldEntity,
                'SilverEntity' => $result1->SilverEntity,
                'BasicEntity' => $result1->BasicEntity,
            ],
            ['categorySummary' => $result3]

        );

        return $response;
    }
}
