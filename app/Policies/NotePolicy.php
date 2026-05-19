<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Note $note): bool
    {
        return $user->id === $note->user_id || 
               $note->sharedUsers()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Note $note): bool
    {
        if ($user->id === $note->user_id) {
            return true;
        }

        // Check if shared with 'edit' permission
        $share = $note->sharedUsers()
            ->where('user_id', $user->id)
            ->wherePivot('type', 'edit')
            ->exists();

        return $share;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Note $note): bool
    {
        // Only the owner can delete a note
        return $user->id === $note->user_id;
    }
}
