<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('household-transactions', function ($user) {
    return ! is_null($user);

    return true; // Allow all authenticated users to listen
    // Or add authorization logic if needed:
    // return auth()->check();
});
