<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AlbumPolicy
{
    public function view(User $user, Album $album): Response
    {
        return $user->id === $album->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Album $album): Response
    {
        return $user->id === $album->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
