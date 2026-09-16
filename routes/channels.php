<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|──────────────────────────────────────────────────────────────────
| Broadcasting Channels — Crystalline Dental PMS
|──────────────────────────────────────────────────────────────────
|
| Private channel per user: App.Models.User.{id}
| Only the authenticated user may subscribe to their own channel.
| All events (appointments, AI results, recalls, stock) broadcast here.
|
*/

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});
