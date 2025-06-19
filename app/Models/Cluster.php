<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Cluster extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ulb_id',
        'verifiedby_id',
        'appliedtc_id',
        'ward_id',
        'cluster_name',
        'cluster_address',
        'landmark',
        'pincode',
        'cluster_type',
        'mobile_no',
        'whatsapp_no',
        'longitude',
        'latitude',
        'inclusion_date',
        'verification_date',
        'vrno',
    ];

    protected $hidden = [
        'ulb_id',
        //   'ward_id',
        'verifiedby_id',
        'appliedtc_id',
        'verification_date',
        //   'is_verified',
        'vrno',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relationships
     */
    public function ulb()
    {
        return $this->belongsTo(Ulb::class);
    }

    public function zone()
    {
        return $this->belongsTo(PaymentZone::class, 'zone_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verifiedby_id');
    }

    public function tc()
    {
        return $this->belongsTo(User::class, 'tc_id');
    }

    public function currentTransactions()
    {
        return $this->hasMany(CurrentTransaction::class, 'cluster_id');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'cluster_id');
    }

    public function ratepayers()
    {
        return $this->hasMany(Ratepayer::class, 'cluster_id');
    }

    public function calculateUpdateClusterDemand(int $clusterId): bool
    {
      try {
         $clusterRatepayer = Ratepayer::where('cluster_id', $clusterId)
               ->whereNull('entity_id')
               ->first();

         if (!$clusterRatepayer) {
               Log::warning("No cluster-level ratepayer found for cluster_id = $clusterId");
               return false;
         }

         $summaryRows = ClusterCurrentDemand::where('ratepayer_id', $clusterRatepayer->id)
               ->where('is_active', 1)
               ->get();

         foreach ($summaryRows as $summary) {
               $totalDemand = CurrentDemand::join('ratepayers', 'current_demands.ratepayer_id', '=', 'ratepayers.id')
                  ->where('ratepayers.cluster_id', $clusterId)
                  ->where('current_demands.bill_month', $summary->bill_month)
                  ->where('current_demands.bill_year', $summary->bill_year)
                  ->sum('current_demands.demand');

               if ($totalDemand == 0) {
                  DB::transaction(function () use ($summary) {
                     $archived = $summary->replicate();
                     $archived->is_active = false;
                     $archived->deactivation_reason = 'all demands are deactivated';
                     $archived->demand = 0;
                     $archived->total_demand = 0;
                     $archived->save();

                     ClusterDemand::create($archived->toArray());
                     $summary->delete();
                  });
               } else {
                  $summary->update([
                     'demand' => $totalDemand,
                     'total_demand' => $totalDemand,
                  ]);
               }
         }

         return true;
      } catch (\Throwable $e) {
         Log::error("Failed to update cluster demand for cluster_id = $clusterId", [
               'error' => $e->getMessage(),
               'trace' => $e->getTraceAsString(),
         ]);
         return false;
      }
   }

}
