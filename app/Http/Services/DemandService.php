<?php

namespace App\Http\Services;

use App\Models\Demand;
use App\Models\RateList;
use App\Models\Ratepayer;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemandService
{
    /**
     * Generate demands for all active ratepayers for a specific year
     *
     * @param  int  $year  Year for which demands are to be generated
     * @param  int|null  $ulbId  Optional ULB ID to filter demands
     * @return array Generation statistics
     */
    public function generateYearlyDemands(int $year, ?int $ulbId = null): array
    {
        // Prepare statistics
        $stats = [
            'total_ratepayers' => 0,
            'demands_generated' => 0,
            'skipped_ratepayers' => 0,
            'errors' => [],
        ];

        try {
            // Start a database transaction for data integrity
            DB::transaction(function () use ($year, $ulbId) {

                // Query active ratepayers, optionally filtered by ULB
                $ratepayersQuery = Ratepayer::where('is_active', true);
                if ($ulbId) {
                    $ratepayersQuery->where('ulb_id', $ulbId);
                }

                // Iterate through active ratepayers
                $ratepayersQuery->chunk(100, function ($ratepayers) use ($year, &$stats) {
                    foreach ($ratepayers as $ratepayer) {
                        try {
                            $demands = $this->generateRatepayerDemands($ratepayer, $year);
                            $stats['demands_generated'] += count($demands);
                        } catch (\Exception $e) {
                            $stats['skipped_ratepayers']++;
                            $stats['errors'][] = [
                                'ratepayer_id' => $ratepayer->id,
                                'error' => $e->getMessage(),
                            ];

                            Log::error('Demand Generation Failed', [
                                'ratepayer_id' => $ratepayer->id,
                                'year' => $year,
                                'error' => $e->getMessage(),
                            ]);
                        }
                        $stats['total_ratepayers']++;
                    }
                });

            });
        } catch (Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());
        }

        return $stats;
    }

    /**
     * Generate demands for a specific ratepayer for an entire year
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
            $demand = Demand::updateOrCreate(
                [
                    'ratepayer_id' => $ratepayer->id,
                    'bill_month' => $month,
                    'bill_year' => $year,
                ],
                [
                    'ulb_id' => $ratepayer->ulb_id,
                    'demand' => $monthlyDemand,
                    'payment' => null,  // Reset payment
                ]
            );

            $generatedDemands[] = $demand;
        }

        return $generatedDemands;
    }

    /**
     * Calculate monthly demand for a ratepayer
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

    public function mergeDemand(int $ulb_id): bool
    {
        try {
            // Start a transaction for safety
            DB::transaction(function () use ($ulb_id) {
                // Step 1: Append current demands to demands table
                DB::table('demands')->insert(
                    DB::table('current_demands')->where('ulb_id', $ulb_id)->select(
                        'ulb_id',
                        'ratepayer_id',
                        'bill_month',
                        'bill_year',
                        'demand',
                        'payment',
                        'created_at',
                        'updated_at'
                    )->get()->toArray()
                );
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return false;
        }
    }
}
