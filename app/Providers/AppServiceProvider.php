<?php

namespace App\Providers;

use App\Models\Album;
use App\Models\Memory;
use App\Policies\AlbumPolicy;
use App\Policies\MemoryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Album::class, AlbumPolicy::class);
        Gate::policy(Memory::class, MemoryPolicy::class);
    }
}
