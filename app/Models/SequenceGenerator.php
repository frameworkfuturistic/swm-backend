<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SequenceGenerator extends Model
{
   use HasFactory;

    protected $fillable = [
        'type', 
        'prefix',
        'last_number',
        'increment_by',
        'padding',
        'suffix'
    ];

    /**
     * Generate a new sequence number based on type
     *
     * @param string $type
     * @return string
     */
    public static function generateNumber(string $type): string
    {
         $generatedNumber = DB::transaction(function () use ($type): string {
            $sequence = self::where('type', $type)->lockForUpdate()->first();
   
            if (!$sequence) {
               throw new \Exception("Sequence type '{$type}' not found.");
            }
   
            $sequence->last_number = (int) $sequence->last_number + (int) $sequence->increment_by;
            $sequence->save();
   
            return (string) ($sequence->prefix ?? '') .
                  str_pad((string) $sequence->last_number, (int) $sequence->padding, '0', STR_PAD_LEFT) .
                  (string) ($sequence->suffix ?? '');
      }, 3); // 3 retries in case of deadlock
   
      return $generatedNumber??'';
   }
    
    /**
     * Reset a sequence back to 0 or a specific number
     *
     * @param string $type
     * @param int $resetTo
     * @return bool
     */
    public static function resetSequence(string $type, int $resetTo = 0): bool
    {
        $sequence = self::where('type', $type)->first();
        
        if (!$sequence) {
            throw new \Exception("Sequence type '{$type}' not found.");
        }
        
        $sequence->last_number = $resetTo;
        return $sequence->save();
    }
    
    /**
     * Create a new sequence type
     *
     * @param string $type
     * @param string|null $prefix
     * @param int $startNumber
     * @param int $incrementBy
     * @param int $padding
     * @param string|null $suffix
     * @return SequenceGenerator
     */
    public static function createSequence(
        string $type,
        ?string $prefix = null,
        int $startNumber = 0,
        int $incrementBy = 1,
        int $padding = 0,
        ?string $suffix = null
    ): self {
        return self::create([
            'type' => $type,
            'prefix' => $prefix,
            'last_number' => $startNumber,
            'increment_by' => $incrementBy,
            'padding' => $padding,
            'suffix' => $suffix
        ]);
    }
}
