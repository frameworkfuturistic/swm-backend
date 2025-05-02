<?php

namespace App\Console\Commands;

use App\Models\SequenceGenerator;
use App\Services\NumberGeneratorService;
use Illuminate\Console\Command;

class FrameCreateCustomSequence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'frame-sequence:create {type} {--prefix=} {--start=0} {--increment=1} {--padding=0} {--suffix=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a custom sequence generator';

    /**
     * Execute the console command.
     */
    public function handle(NumberGeneratorService $numberGenerator)
    {
        $type = $this->argument('type');
        
        $options = [
            'prefix' => $this->option('prefix'),
            'startNumber' => (int) $this->option('start'),
            'incrementBy' => (int) $this->option('increment'),
            'padding' => (int) $this->option('padding'),
            'suffix' => $this->option('suffix'),
        ];
        
        $sequence = $numberGenerator->createSequenceType($type, $options);
        
        $this->info("Sequence type '{$type}' created successfully.");
        
        // Test the sequence
        $generatedNumber = SequenceGenerator::generateNumber($type);
        $this->info("Test number generated: {$generatedNumber}");
        
        return Command::SUCCESS;
    }
}


/**
 * php artisan frame:frame-create-custom-sequence invoice \
 * --prefix=INV \
 *  --start=1000 \
 *  --increment=1 \
 *  --padding=5 \
 *  --suffix=2025
 * 
 * php artisan frame:frame-create-custom-sequence invoice --prefix=SWM --start=1000 --increment=1 --padding=6 --suffix=RMC
 */
