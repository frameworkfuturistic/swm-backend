<?php

namespace App\Http\Services;

use App\Models\CurrentDemand;
use App\Models\Ratepayer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Usage
 * $validationService = new DemandValidationService();
 *
 * // Complete validation for a year
 * $yearlyValidation = $validationService->validateDemandGeneration(
 *     ulbId: 1,
 *     year: 2024
 * );
 *
 * // Validate specific month
 * $monthlyValidation = $validationService->validateDemandGeneration(
 *     ulbId: 1,
 *     year: 2024,
 *     month: 3
 * );
 *
 * // Quick check
 * $hasDemandsForMarch = $validationService->hasDemandsForPeriod(
 *     ulbId: 1,
 *     year: 2024,
 *     month: 3
 * );
 *
 * // Get statistics
 * $stats = $validationService->getDemandGenerationStats(
 *     ulbId: 1,
 *     year: 2024
 * );
 */
class DemandValidationService
{
    /**
     * Validate if demands have been generated for a specific period
     *
     * @return array Validation results
     */
    public function validateDemandGeneration(int $ulbId, int $year, ?int $month = null): array
    {
        $result = [
            'isValid' => true,
            'totalRatepayers' => 0,
            'rateplayersWithDemands' => 0,
            'missingDemands' => [],
            'anomalies' => [],
            'details' => [],
        ];

        try {
            // Get all active ratepayers
            $ratepayers = $this->getActiveRatepayers($ulbId);
            $result['totalRatepayers'] = $ratepayers->count();

            // Check demands for each ratepayer
            foreach ($ratepayers as $ratepayer) {
                $validationDetails = $this->validateRatepayerDemands($ratepayer, $year, $month);

                if ($validationDetails['hasAllDemands']) {
                    $result['rateplayersWithDemands']++;
                } else {
                    $result['isValid'] = false;
                    $result['missingDemands'][] = [
                        'ratepayerId' => $ratepayer->id,
                        'missingMonths' => $validationDetails['missingMonths'],
                    ];
                }

                if (! empty($validationDetails['anomalies'])) {
                    $result['anomalies'][] = [
                        'ratepayerId' => $ratepayer->id,
                        'anomalies' => $validationDetails['anomalies'],
                    ];
                }

                $result['details'][] = $validationDetails;
            }

        } catch (\Exception $e) {
            Log::error('Demand validation failed', [
                'ulb_id' => $ulbId,
                'year' => $year,
                'month' => $month,
                'error' => $e->getMessage(),
            ]);

            return [
                'isValid' => false,
                'error' => $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * Get active ratepayers for a ULB
     */
    protected function getActiveRatepayers(int $ulbId): Collection
    {
        return Ratepayer::where('ulb_id', $ulbId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Validate demands for a specific ratepayer
     */
    protected function validateRatepayerDemands(Ratepayer $ratepayer, int $year, ?int $month = null): array
    {
        $query = CurrentDemand::where('ratepayer_id', $ratepayer->id)
            ->where('bill_year', $year);

        if ($month) {
            $query->where('bill_month', $month);
            $expectedCount = 1;
        } else {
            $query->whereIn('bill_month', range(1, 12));
            $expectedCount = 12;
        }

        $demands = $query->get();
        $missingMonths = $this->findMissingMonths($demands, $year, $month);
        $anomalies = $this->checkForAnomalies($demands, $ratepayer);

        return [
            'ratepayerId' => $ratepayer->id,
            'hasAllDemands' => $demands->count() === $expectedCount,
            'expectedCount' => $expectedCount,
            'actualCount' => $demands->count(),
            'missingMonths' => $missingMonths,
            'anomalies' => $anomalies,
            'demands' => $demands->map(fn ($demand) => [
                'month' => $demand->bill_month,
                'amount' => $demand->demand,
                'total_demand' => $demand->total_demand,
            ]),
        ];
    }

    /**
     * Find missing months in demands
     */
    protected function findMissingMonths(Collection $demands, int $year, ?int $month): array
    {
        $existingMonths = $demands->pluck('bill_month')->toArray();

        if ($month) {
            return in_array($month, $existingMonths) ? [] : [$month];
        }

        $allMonths = range(1, 12);

        return array_values(array_diff($allMonths, $existingMonths));
    }

    /**
     * Check for anomalies in demands
     */
    protected function checkForAnomalies(Collection $demands, Ratepayer $ratepayer): array
    {
        $anomalies = [];

        foreach ($demands as $demand) {
            // Check for zero demands when ratepayer has monthly_demand
            if ($ratepayer->monthly_demand > 0 && $demand->demand == 0) {
                $anomalies[] = [
                    'type' => 'zero_demand',
                    'month' => $demand->bill_month,
                    'expected' => $ratepayer->monthly_demand,
                    'actual' => 0,
                ];
            }

            // Check for incorrect total_demand calculations
            $expectedTotal = $demand->opening_demand + $demand->demand;
            if ($demand->total_demand != $expectedTotal) {
                $anomalies[] = [
                    'type' => 'incorrect_total',
                    'month' => $demand->bill_month,
                    'expected' => $expectedTotal,
                    'actual' => $demand->total_demand,
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Quick check if any demands exist for a period
     */
    public function hasDemandsForPeriod(int $ulbId, int $year, ?int $month = null): bool
    {
        $query = CurrentDemand::where('ulb_id', $ulbId)
            ->where('bill_year', $year);

        if ($month) {
            $query->where('bill_month', $month);
        }

        return $query->exists();
    }

    /**
     * Get demand generation statistics for a period
     */
    public function getDemandGenerationStats(int $ulbId, int $year, ?int $month = null): array
    {
        $query = CurrentDemand::where('ulb_id', $ulbId)
            ->where('bill_year', $year);

        if ($month) {
            $query->where('bill_month', $month);
        }

        return [
            'totalDemands' => $query->count(),
            'totalAmount' => $query->sum('demand'),
            'uniqueRatepayers' => $query->distinct('ratepayer_id')->count(),
            'averageDemand' => $query->avg('demand'),
            'generatedAt' => $query->max('created_at'),
        ];
    }
}
