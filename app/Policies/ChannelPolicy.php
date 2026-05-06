<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\User;

class ChannelPolicy
{
    public function view(User $user, Channel $channel): bool
    {
        return $user->isAdmin()
            || $channel->playlist->user_id === $user->id
            || ($channel->playlist->is_public && $channel->playlist->approved_at !== null);
    }
}
