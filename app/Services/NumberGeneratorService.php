<?php

namespace App\Services;

use App\Models\SequenceGenerator;
use Carbon\Carbon;

class NumberGeneratorService
{
    /**
     * Generate a basic sequence number
     *
     * @param string $type
     * @return string
     */
    public function generate(string $type): string
    {
        return SequenceGenerator::generateNumber($type);
    }
    
    /**
     * Generate a transaction number with optional date embedding
     *
     * @param bool $includeDatePrefix
     * @param string $dateFormat
     * @return string
     */
    public function generateTransactionNumber(bool $includeDatePrefix = true, string $dateFormat = 'Ymd'): string
    {
        $base = SequenceGenerator::generateNumber('transaction_no');
        
        if ($includeDatePrefix) {
            $date = Carbon::now()->format($dateFormat);
            return $date . '-' . $base;
        }
        
        return $base;
    }
    
    /**
     * Generate a consumer number
     *
     * @return string
     */
    public function generateConsumerNumber(): string
    {
        return SequenceGenerator::generateNumber('consumer_no');
    }
    
    /**
     * Generate a custom number with additional processing
     *
     * @param string $type
     * @param array $options
     * @return string
     */
    public function generateCustomNumber(string $type, array $options = []): string
    {
        $number = SequenceGenerator::generateNumber($type);
        
        // Apply any custom formatting options
        if (!empty($options['addDate'])) {
            $dateFormat = $options['dateFormat'] ?? 'Ymd';
            $dateSeparator = $options['dateSeparator'] ?? '-';
            $date = Carbon::now()->format($dateFormat);
            
            if ($options['datePosition'] === 'prefix') {
                $number = $date . $dateSeparator . $number;
            } else {
                $number = $number . $dateSeparator . $date;
            }
        }
        
        // Add branch code if specified
        if (!empty($options['branchCode'])) {
            $number = $options['branchCode'] . '-' . $number;
        }
        
        return $number;
    }
    
    /**
     * Create a new sequence type
     * 
     * @param string $type
     * @param array $options
     * @return SequenceGenerator
     */
    public function createSequenceType(string $type, array $options = []): SequenceGenerator
    {
        return SequenceGenerator::createSequence(
            $type,
            $options['prefix'] ?? null,
            $options['startNumber'] ?? 0,
            $options['incrementBy'] ?? 1,
            $options['padding'] ?? 0,
            $options['suffix'] ?? null
        );
    }
}