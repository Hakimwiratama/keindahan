<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                $now = now();

                foreach (['Sayang', 'Kuliah'] as $name) {
                    DB::table('albums')->insertOrIgnore([
                        'user_id' => $user->id,
                        'name' => $name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
