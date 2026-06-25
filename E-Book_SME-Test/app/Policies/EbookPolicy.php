<?php

namespace App\Policies;

use App\Models\Ebook;
use App\Models\User;

class EbookPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ebook $ebook): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    // public function view(User $user, Ebook $ebook): bool
    // {
    //     return $user->id === $ebook->user_id;
    // }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ebook $ebook): bool
    {
        return $user->id === $ebook->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ebook $ebook): bool
    {
        return $user->id === $ebook->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ebook $ebook): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ebook $ebook): bool
    {
        return false;
    }
}
