<?php

namespace App\Policies;

use App\Models\Fee;
use App\Models\User;

class FeePolicy
{
    public function viewAny(User $user)
    {
        if ($user->can('read fee')) {
            return true;
        }
    }

    public function view(User $user, Fee $fee): bool
    {
        return $user->can('read fee') && $this->isOfTheWorkingCampus($fee);
    }

    public function create(User $user)
    {
        if ($user->can('create fee')) {
            return true;
        }
    }

    public function update(User $user, Fee $fee): bool
    {
        return $user->can('update fee') && $this->isOfTheWorkingCampus($fee);
    }

    public function delete(User $user, Fee $fee): bool
    {
        return $user->can('delete fee') && $this->isOfTheWorkingCampus($fee);
    }

    public function restore(User $user, Fee $fee)
    {
        //
    }

    public function forceDelete(User $user, Fee $fee)
    {
        //
    }

    /**
     * A fee belongs to a campus through its category, which the campus scope
     * hides when it belongs elsewhere: no category means no access.
     */
    private function isOfTheWorkingCampus(Fee $fee): bool
    {
        $category = $fee->feeCategory;

        return $category !== null && $category->school_id === current_school_id();
    }
}
