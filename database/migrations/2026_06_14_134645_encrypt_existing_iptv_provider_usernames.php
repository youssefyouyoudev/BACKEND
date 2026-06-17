<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('playlists')
            ->whereNotNull('username')
            ->orderBy('id')
            ->each(function (object $playlist): void {
                try {
                    Crypt::decryptString($playlist->username);

                    return;
                } catch (DecryptException) {
                    DB::table('playlists')
                        ->where('id', $playlist->id)
                        ->update(['username' => Crypt::encryptString($playlist->username)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('playlists')
            ->whereNotNull('username')
            ->orderBy('id')
            ->each(function (object $playlist): void {
                try {
                    $username = Crypt::decryptString($playlist->username);
                } catch (DecryptException) {
                    return;
                }

                DB::table('playlists')
                    ->where('id', $playlist->id)
                    ->update(['username' => $username]);
            });
    }
};
