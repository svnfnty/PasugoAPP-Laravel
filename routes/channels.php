<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

// For public channels used in the app, we don't strictly need these but it's good practice.
Broadcast::channel('rider.{id}', function () {
    return true; // Public for now to ensure connection
});

Broadcast::channel('riders', function () {
    return true; // Public for now to ensure connection
});
