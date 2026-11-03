<?php

namespace App\Policies;

use App\Models\Memory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MemoryPolicy
{
    public function view(User $user, Memory $memory): Response
    {
        return $user->id === $memory->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Memory $memory): Response
    {
        return $user->id === $memory->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
