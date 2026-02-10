<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;

final class UserObserver
{
    /**
     * Listen to the User created event.
     */
    public function creating(User $user)
    {
        // If We Didnt Passed Any  Id On user Creation then We Generate One
        if (is_null($user->username)) {
            $user->username = User::generateUsername($user);
        }
    }
}
