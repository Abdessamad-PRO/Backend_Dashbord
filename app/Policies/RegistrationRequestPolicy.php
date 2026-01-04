<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RegistrationRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegistrationRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    { 
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user)
    {
        return $user->role === 'admin';
    }
}
