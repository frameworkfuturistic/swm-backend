<?php

namespace App\Listeners;

use App\Events\SiteVisited;

class NotifyDashboard
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SiteVisited $event): void
    {
        //
    }
}
