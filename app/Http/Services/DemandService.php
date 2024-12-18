<?php

namespace App\Http\Services;

use App\Models\CurrentDemand;
use App\Models\Demand;
use App\Models\RateList;
use App\Models\Ratepayer;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DemandService
{
    protected $ulbId;

    protected $stats = [
        'total_ratepayers' => 0,
        'demands_generated' => 0,
        'skipped_ratepayers' => 0,
        'errors' => [],
    ];

    /**
     * Generate demands for all active ratepayers for a specific year
     *
     * @param  int  $year  Year for which demands are to be generated
     * @param  int|null  $ulbId  Optional ULB ID to filter demands
     * @return array Generation statistics
     */
    public function generateYearlyDemands(int $year, ?int $ulbId = null): array
    {
        $ulbId = $ulbId;
        try {
            // Start a database transaction for data integrity
            DB::transaction(function () use (&$stats, $year) {
                $this->resetOpeningDemand();
                $this->updateEntityRatepayerDemand();
                $this->updateClusterRatepayerDemand();
                $this->mergeDemand();
                $this->cleanCurrentDemands();
                $this->generateCurrentDemands($year);
            });
        } catch (Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());
        }

        return $this->stats;
    }

    /**
     * Step 1: Reset opening demand for all active ratepayers
     */
    private function resetOpeningDemand()
    {
        DB::table('ratepayers')
            ->where('is_active', 1)
            ->where('ulb_id', $this->ulbId)
            ->update(['opening_demand' => 0]);

        Log::info('Opening demands reset successfully.');
    }

    /**
     * Step 2: Update opening demand for non-cluster (entity) ratepayers
     */
    private function updateEntityRatepayerDemand()
    {
        DB::table('ratepayers AS p')
            ->join(DB::raw('(
                SELECT d.ratepayer_id, SUM((IFNULL(d.opening_demand, 0) + IFNULL(d.demand, 0)) - IFNULL(d.payment, 0)) AS balance
                FROM current_demands d
                INNER JOIN ratepayers r ON d.ratepayer_id = r.id
                WHERE r.cluster_id IS NULL AND r.is_active = 1
                GROUP BY d.ratepayer_id
            ) AS a'), 'p.id', '=', 'a.ratepayer_id')
            ->update(['p.opening_demand' => DB::raw('a.balance')]);

        Log::info('Opening demands updated for entity ratepayers.');
    }

    /**
     * Step 3: Update opening demand for cluster-based ratepayers
     */
    private function updateClusterRatepayerDemand()
    {
        DB::table('ratepayers AS p')
            ->join(DB::raw('(
                SELECT e.cluster_id, SUM((IFNULL(d.opening_demand, 0) + IFNULL(d.demand, 0)) - IFNULL(d.payment, 0)) AS balance
                FROM demands d
                INNER JOIN ratepayers r ON d.ratepayer_id = r.id
                INNER JOIN entities e ON r.entity_id = e.id
                WHERE e.cluster_id IS NOT NULL AND r.is_active = 1
                GROUP BY e.cluster_id
            ) AS a'), 'p.cluster_id', '=', 'a.cluster_id')
            ->update(['p.opening_demand' => DB::raw('a.balance')]);

        Log::info('Opening demands updated for cluster ratepayers.');
    }

    /**
     * Step 4: Clean current demands for the given ULB ID
     */
    private function cleanCurrentDemands()
    {
        DB::table('current_demands')
            ->where('ulb_id', $this->ulbId)
            ->delete();

        Log::info('Current demands table cleaned for ULB ID: '.$this->ulbId);
    }

    /**
     * Step 5 Generate Current Demand
     */
    private function generateCurrentDemands($year)
    {
        // Query active ratepayers, optionally filtered by ULB
        $ratepayersQuery = Ratepayer::where('is_active', true);
        if ($this->ulbId) {
            $ratepayersQuery->where('ulb_id', $this->ulbId);
        }

        //  DB::enableQueryLog();
        // Iterate through active ratepayers
        $ratepayersQuery->chunk(100, function ($ratepayers) use ($year, &$stats) {
            foreach ($ratepayers as $ratepayer) {
                try {
                    $demands = $this->generateRatepayerDemands($ratepayer, $year);
                    $this->stats['demands_generated'] += count($demands);
                } catch (\Exception $e) {
                    $this->$stats['skipped_ratepayers']++;
                    $this->$stats['errors'][] = [
                        'ratepayer_id' => $ratepayer->id,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Demand Generation Failed', [
                        'ratepayer_id' => $ratepayer->id,
                        'year' => $year,
                        'error' => $e->getMessage(),
                    ]);
                }
                $this->stats['total_ratepayers']++;
            }
        });
    }

    /**
     * Step 5 Alternate: Generate current demands for ratepayers
     */
    private function generateCurrentDemandsAlternate()
    {
        $billMonth = Carbon::now()->month;
        $billYear = Carbon::now()->year;

        // Insert demands for non-clustered ratepayers
        DB::table('current_demands')->insertUsing([
            'ulb_id', 'ratepayer_id', 'bill_month', 'bill_year', 'demand', 'payment', 'vrno', 'created_at', 'updated_at',
        ], DB::table('ratepayers AS p')
            ->select([
                'p.ulb_id', 'p.id', DB::raw($billMonth), DB::raw($billYear), 'p.monthly_demand', DB::raw(0), 'p.vrno', DB::raw('NOW()'), DB::raw('NOW()'),
            ])
            ->whereNull('p.cluster_id')
            ->where('p.is_active', 1)
        );

        // Insert demands for clustered ratepayers
        DB::table('current_demands')->insertUsing([
            'ulb_id', 'ratepayer_id', 'bill_month', 'bill_year', 'demand', 'payment', 'vrno', 'created_at', 'updated_at',
        ], DB::table('ratepayers AS p')
            ->select([
                'p.ulb_id', 'p.id', DB::raw($billMonth), DB::raw($billYear), DB::raw('SUM(p.monthly_demand)'), DB::raw(0), 'p.vrno', DB::raw('NOW()'), DB::raw('NOW()'),
            ])
            ->whereNotNull('p.cluster_id')
            ->where('p.is_active', 1)
            ->groupBy('p.cluster_id', 'p.id')
        );

        Log::info('Current demands generated successfully for ULB ID: '.$this->ulbId);
    }

    /**
     * Step 5.1: Generate demands for a specific ratepayer for an entire year
     *
     * @param  Ratepayer  $ratepayer  Ratepayer model instance
     * @param  int  $year  Year for demand generation
     * @return array Demands generated for the ratepayer
     */
    public function generateRatepayerDemands(Ratepayer $ratepayer, int $year): array
    {
        // Determine the monthly demand amount
        $monthlyDemand = $this->calculateMonthlyDemand($ratepayer);

        // Store generated demands
        $generatedDemands = [];

        // Generate demands for all 12 months
        for ($month = 1; $month <= 12; $month++) {
            $demand = CurrentDemand::updateOrCreate(
                [
                    'ulb_id' => $ratepayer->ulb_id,
                    'ratepayer_id' => $ratepayer->id,
                    'bill_month' => $month,
                    'bill_year' => $year,
                ],
                [
                    'vrno' => 1,
                    'demand' => $monthlyDemand,
                    'payment' => null,  // Reset payment
                ]
            );

            $generatedDemands[] = $demand;
        }

        return $generatedDemands;
    }

    /**
     * Step 5.1.1 Calculate monthly demand for a ratepayer
     *
     * @return float|int Monthly demand amount
     */
    protected function calculateMonthlyDemand(Ratepayer $ratepayer): float|int
    {
        // Priority for demand calculation:
        // 1. Ratepayer's monthly demand
        // 2. Rate list amount
        // 3. Default to 0
        if ($ratepayer->monthly_demand) {
            return $ratepayer->monthly_demand;
        }

        if ($ratepayer->rate_id) {
            $rateList = RateList::find($ratepayer->rate_id);

            return $rateList?->amount ?? 0;
        }

        return 0;
    }

    /**
     * Record payment against a demand
     *
     * @param  int  $demandId  Demand ID
     * @param  float  $paymentAmount  Payment amount
     * @param  array  $additionalData  Additional transaction details
     */
    public function recordPayment(int $demandId, float $paymentAmount, array $additionalData = []): ?Transaction
    {
        try {
            return DB::transaction(function () use ($demandId, $paymentAmount, $additionalData) {
                // Fetch the demand
                $demand = Demand::findOrFail($demandId);

                // Protect against overpayment
                $remainingDemand = max(0, $demand->demand - ($demand->payment ?? 0));
                $actualPayment = min($paymentAmount, $remainingDemand);

                // Create transaction
                $transaction = Transaction::create([
                    'ratepayer_id' => $demand->ratepayer_id,
                    'demand_id' => $demandId,
                    'amount' => $actualPayment,
                    ...$additionalData,
                ]);

                // Update demand payment
                $demand->payment = ($demand->payment ?? 0) + $actualPayment;
                $demand->save();

                // Update ratepayer's last payment and transaction
                $ratepayer = $demand->ratepayer;
                $ratepayer->last_payment_id = $transaction->id;
                $ratepayer->last_transaction_id = $transaction->id;
                $ratepayer->save();

                return $transaction;
            });
        } catch (\Exception $e) {
            Log::error('Payment Recording Failed', [
                'demand_id' => $demandId,
                'payment_amount' => $paymentAmount,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get outstanding demands for a ratepayer
     *
     * @param  bool  $includePartiallyPaid  Include demands with partial payments
     * @return \Illuminate\Support\Collection
     */
    public function getOutstandingDemands(int $ratepayerId, bool $includePartiallyPaid = false)
    {
        $query = Demand::where('ratepayer_id', $ratepayerId)
            ->whereRaw('COALESCE(demand, 0) > COALESCE(payment, 0)');

        if (! $includePartiallyPaid) {
            $query->whereRaw('COALESCE(payment, 0) = 0');
        }

        return $query->get();
    }

    public function cleanCurrentDemand(int $ulb_id): bool
    {
        try {
            DB::table('current_demands')->where('ulb_id', $ulb_id)->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return false;
        }
    }

    public function mergeDemand(): bool
    {
        try {

            $columns = Schema::getColumnListing('current_demand');

            DB::table('demands')->insertUsing(
                $columns,
                DB::table('current_demands')->select('*')->where('ulb_id', $this->ulbId)
            );

            // // Start a transaction for safety
            // DB::transaction(function () use ($ulb_id) {
            //     // Step 1: Append current demands to demands table
            //     DB::table('demands')->insert(
            //         DB::table('current_demands')->where('ulb_id', $ulb_id)->select(
            //             'ulb_id',
            //             'ratepayer_id',
            //             'bill_month',
            //             'bill_year',
            //             'demand',
            //             'payment',
            //             'created_at',
            //             'updated_at'
            //         )->get()->toArray()
            //     );
            // });

            return true;
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return false;
        }
    }
}
