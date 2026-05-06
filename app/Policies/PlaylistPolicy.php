<?php

namespace App\Policies;

use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy
{
    public function view(User $user, Playlist $playlist): bool
    {
        return $user->isAdmin()
            || $playlist->user_id === $user->id
            || ($playlist->is_public && $playlist->approved_at !== null);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Playlist $playlist): bool
    {
        return $user->isAdmin() || $playlist->user_id === $user->id;
    }

    public function delete(User $user, Playlist $playlist): bool
    {
        return $this->update($user, $playlist);
    }

    public function refresh(User $user, Playlist $playlist): bool
    {
        return $this->update($user, $playlist);
    }

    public function approve(User $user, Playlist $playlist): bool
    {
        return $user->isAdmin();
    }
}
