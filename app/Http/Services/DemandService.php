<?php

namespace App\Http\Services;

use App\Models\CurrentDemand;
use App\Models\Demand;
use App\Models\RateList;
use App\Models\Ratepayer;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Service class for managing demand generation and payment processing
 */
class DemandService
{
    /**
     * Current ULB ID being processed
     */
    protected ?int $ulbId = null;

    /**
     * Statistics for demand generation process
     */
    protected array $stats = [
        'total_ratepayers' => 0,
        'demands_generated' => 0,
        'skipped_ratepayers' => 0,
        'errors' => [],
    ];

    /**
     * Generate yearly demands for all active ratepayers
     *
     * @param  int  $year  Year for demand generation
     * @param  int|null  $ulbId  Optional ULB ID filter
     * @return array Statistics of the generation process
     */
    public function generateYearlyDemands(int $year, ?int $ulbId = null): array
    {
        $this->ulbId = $ulbId;

        try {
            DB::transaction(function () use ($year) {
                $this->executeYearlyDemandProcess($year);
            });
        } catch (Exception $e) {
            Log::error('Yearly demand generation failed', [
                'error' => $e->getMessage(),
                'ulb_id' => $this->ulbId,
                'year' => $year,
            ]);
            $this->stats['errors'][] = $e->getMessage();
        }

        return $this->stats;
    }

    /**
     * Execute the yearly demand generation process
     */
    protected function executeYearlyDemandProcess(int $year): void
    {
        // Step 1: Reset and prepare
        $this->resetOpeningDemand();

        // Step 2-3: Update existing demands
        $this->updateExistingDemands();

        // Step 4-5: Process current demands
        $this->processCurrentDemands();

        // Step 6: Generate new demands
        $this->generateCurrentDemands($year);

        // Step 7: Final adjustments
        $this->adjustOpeningDemand();
    }

    /**
     * Reset opening demand for active ratepayers
     */
    protected function resetOpeningDemand(): void
    {
        DB::table('ratepayers')
            ->where('is_active', 1)
            ->where('ulb_id', $this->ulbId)
            ->update(['opening_demand' => 0]);

        Log::info('Opening demands reset', ['ulb_id' => $this->ulbId]);
    }

    /**
     * Update existing demands for both entity and cluster ratepayers
     */
    protected function updateExistingDemands(): void
    {
        // Update entity ratepayers
        $this->updateEntityRatepayerDemands();

        // Update cluster ratepayers
        $this->updateClusterRatepayerDemands();
    }

    /**
     * Update demands for entity ratepayers
     */
    protected function updateEntityRatepayerDemands(): void
    {
        $query = '
            UPDATE ratepayers AS p
            JOIN (
                SELECT 
                    d.ratepayer_id,
                    SUM(COALESCE(d.opening_demand, 0) + COALESCE(d.demand, 0) - COALESCE(d.payment, 0)) AS balance
                FROM current_demands d
                INNER JOIN ratepayers r ON d.ratepayer_id = r.id
                WHERE r.cluster_id IS NULL 
                    AND r.is_active = 1 
                    AND r.ulb_id = ?
                GROUP BY d.ratepayer_id
            ) AS a ON p.id = a.ratepayer_id
            SET p.opening_demand = a.balance
        ';

        DB::statement($query, [$this->ulbId]);
        Log::info('Entity ratepayer demands updated', ['ulb_id' => $this->ulbId]);
    }

    /**
     * Update demands for cluster ratepayers
     */
    protected function updateClusterRatepayerDemands(): void
    {
        $query = '
            UPDATE ratepayers AS p
            JOIN (
                SELECT 
                    e.cluster_id,
                    SUM(COALESCE(d.opening_demand, 0) + COALESCE(d.demand, 0) - COALESCE(d.payment, 0)) AS balance
                FROM demands d
                INNER JOIN ratepayers r ON d.ratepayer_id = r.id
                INNER JOIN entities e ON r.entity_id = e.id
                WHERE e.cluster_id IS NOT NULL 
                    AND r.is_active = 1 
                    AND r.ulb_id = ?
                GROUP BY e.cluster_id
            ) AS a ON p.cluster_id = a.cluster_id
            SET p.opening_demand = a.balance
        ';

        DB::statement($query, [$this->ulbId]);
        Log::info('Cluster ratepayer demands updated', ['ulb_id' => $this->ulbId]);
    }

    /**
     * Process current demands (merge and clean)
     */
    protected function processCurrentDemands(): void
    {
        $this->mergeDemands();
        $this->cleanCurrentDemands();
    }

    /**
     * Merge current demands into demands table
     */
    protected function mergeDemands(): void
    {
        $columns = Schema::getColumnListing('current_demands');

        DB::table('demands')->insertUsing(
            $columns,
            DB::table('current_demands')
                ->select('*')
                ->where('ulb_id', $this->ulbId)
        );

        Log::info('Demands merged successfully', ['ulb_id' => $this->ulbId]);
    }

    /**
     * Clean current demands table
     */
    protected function cleanCurrentDemands(): void
    {
        DB::table('current_demands')
            ->where('ulb_id', $this->ulbId)
            ->delete();

        Log::info('Current demands cleaned', ['ulb_id' => $this->ulbId]);
    }

    /**
     * Generate current demands for active ratepayers
     */
    protected function generateCurrentDemands(int $year): void
    {
        Ratepayer::where('is_active', true)
            ->where('ulb_id', $this->ulbId)
            ->chunk(100, function ($ratepayers) use ($year) {
                foreach ($ratepayers as $ratepayer) {
                    $this->processRatepayerDemand($ratepayer, $year);
                }
            });
    }

    /**
     * Process individual ratepayer demand
     */
    protected function processRatepayerDemand(Ratepayer $ratepayer, int $year): void
    {
        try {
            $monthlyDemand = $this->calculateMonthlyDemand($ratepayer);
            $this->createMonthlyDemands($ratepayer, $year, $monthlyDemand);
            $this->stats['demands_generated'] += 12;
        } catch (Exception $e) {
            $this->handleDemandGenerationError($ratepayer, $e);
        }
        $this->stats['total_ratepayers']++;
    }

    /**
     * Calculate monthly demand for a ratepayer
     */
    protected function calculateMonthlyDemand(Ratepayer $ratepayer): float
    {
        if ($ratepayer->monthly_demand) {
            return $ratepayer->monthly_demand;
        }

        if ($ratepayer->rate_id) {
            return RateList::find($ratepayer->rate_id)?->amount ?? 0;
        }

        return 0;
    }

    /**
     * Create monthly demands for a ratepayer
     */
    protected function createMonthlyDemands(Ratepayer $ratepayer, int $year, float $monthlyDemand): void
    {
        for ($month = 1; $month <= 12; $month++) {
            CurrentDemand::updateOrCreate(
                [
                    'ulb_id' => $ratepayer->ulb_id,
                    'ratepayer_id' => $ratepayer->id,
                    'bill_month' => $month,
                    'bill_year' => $year,
                ],
                [
                    'vrno' => 1,
                    'demand' => $monthlyDemand,
                    'total_demand' => $monthlyDemand,
                    'payment' => 0,
                ]
            );
        }
    }

    /**
     * Handle errors during demand generation
     */
    protected function handleDemandGenerationError(Ratepayer $ratepayer, Exception $e): void
    {
        $this->stats['skipped_ratepayers']++;
        $this->stats['errors'][] = [
            'ratepayer_id' => $ratepayer->id,
            'error' => $e->getMessage(),
        ];

        Log::error('Demand generation failed', [
            'ratepayer_id' => $ratepayer->id,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Adjust opening demands for current demands
     */
    protected function adjustOpeningDemand(): void
    {
        $currentDemands = $this->getFirstDemandsByRatepayer();

        foreach ($currentDemands as $demand) {
            $this->updateDemandOpeningBalance($demand);
        }
    }

    /**
     * Get first demand record for each ratepayer
     */
    protected function getFirstDemandsByRatepayer(): Collection
    {
        return CurrentDemand::query()
            ->join('ratepayers', 'ratepayers.id', '=', 'current_demands.ratepayer_id')
            ->select(
                'current_demands.id',
                'current_demands.ratepayer_id',
                'ratepayers.opening_demand',
                'current_demands.demand'
            )
            ->where('current_demands.ulb_id', $this->ulbId)
            ->orderBy('current_demands.ratepayer_id')
            ->orderBy('current_demands.bill_year')
            ->orderBy('current_demands.bill_month')
            ->get()
            ->groupBy('ratepayer_id')
            ->map->first();
    }

    /**
     * Update opening balance for a demand
     */
    protected function updateDemandOpeningBalance(CurrentDemand $demand): void
    {
        $newTotalDemand = $demand->opening_demand + $demand->demand;

        CurrentDemand::where('ratepayer_id', $demand->ratepayer_id)
            ->where('id', $demand->id)
            ->update([
                'opening_demand' => $demand->opening_demand,
                'total_demand' => $newTotalDemand,
            ]);
    }
}
