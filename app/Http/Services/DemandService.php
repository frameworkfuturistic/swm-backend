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

    /** Method 1
     * ========================================================================
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
                $this->generateCurrentDemands($year);
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

    /** Method 1.1
     * ========================================================================
     * Generate current demands for active ratepayers
     */
    protected function generateCurrentDemands(int $year): void
    {
      DB::enableQueryLog();
      // $rp = Ratepayer::where('is_active', true)->get();
      $rp=Ratepayer::where('is_active', true)
      ->where('ulb_id', $this->ulbId)
      ->whereNull('cluster_id')
      ->whereNotNull('entity_id')->get();
      
        Ratepayer::where('is_active', true)
            ->where('ulb_id', $this->ulbId)
            ->whereNull('cluster_id')
            ->whereNotNull('entity_id')
            ->chunk(100, function ($ratepayers) use ($year) {
                foreach ($ratepayers as $ratepayer) {
                    $this->processRatepayerDemand($ratepayer, $year);
                }
         });
    }

    /** Method 1.1.1
     * ========================================================================
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

    /** Method 1.1.1.1
     * ========================================================================
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

    /** Method 1.1.1.2
     * ========================================================================
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

    /** Method 1.1.1.3
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
}
