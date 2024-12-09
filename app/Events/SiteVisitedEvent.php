<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Implementation method (wherever needed)
 * $transaction = Transaction::create($validated);
 * broadcast(new HouseholdTransactionEvent(
 *    $transaction,
 *    $request->latitude,
 *    $request->longitude,
 *    $request->eventType
 */
class SiteVisitedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    public function broadcastOn()
    {
        return new Channel('household-transactions');
    }

    public function broadcastAs()
    {
        return 'transaction.created';
    }

    // Optional: Control what data is broadcast
    public function broadcastWith()
    {
        return $this->transaction;
        //   return [
        //       'id' => $this->transaction->id,
        //       'latitude' => $this->latitude,
        //       'longitude' => $this->longitude,
        //       'eventType' => $this->eventType,
        //       'timestamp' => now()->toIso8601String()
        //   ];
    }
}
