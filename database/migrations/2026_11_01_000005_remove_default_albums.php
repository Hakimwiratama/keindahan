<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('albums')
            ->whereIn('name', ['Sayang', 'Kuliah'])
            ->orderBy('id')
            ->get()
            ->each(function (object $album): void {
                if (DB::table('memories')->where('album_id', $album->id)->exists()) {
                    $archive = DB::table('albums')
                        ->where('user_id', $album->user_id)
                        ->where('name', 'Kenangan lama')
                        ->first();

                    $archiveId = $archive?->id ?? DB::table('albums')->insertGetId([
                        'user_id' => $album->user_id,
                        'name' => 'Kenangan lama',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('memories')
                        ->where('album_id', $album->id)
                        ->update(['album_id' => $archiveId]);
                }

                DB::table('albums')->where('id', $album->id)->delete();
            });
    }

    public function down(): void
    {
        //
    }
};
