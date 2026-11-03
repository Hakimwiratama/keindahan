<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table): void {
            $table->foreignId('album_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['user_id', 'album_id', 'created_at'], 'memories_album_created_at_index');
        });

        DB::table('memories')
            ->select('user_id')
            ->distinct()
            ->orderBy('user_id')
            ->get()
            ->each(function (object $row): void {
                $hasUnassignedMemories = DB::table('memories')
                    ->where('user_id', $row->user_id)
                    ->whereNull('album_id')
                    ->exists();

                if (! $hasUnassignedMemories) {
                    return;
                }

                $now = now();
                $albumId = DB::table('albums')->insertGetId([
                    'user_id' => $row->user_id,
                    'name' => 'Kenangan lama',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('memories')
                    ->where('user_id', $row->user_id)
                    ->whereNull('album_id')
                    ->update(['album_id' => $albumId]);
            });
    }

    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table): void {
            $table->dropIndex('memories_album_created_at_index');
            $table->dropConstrainedForeignId('album_id');
        });
    }
};
