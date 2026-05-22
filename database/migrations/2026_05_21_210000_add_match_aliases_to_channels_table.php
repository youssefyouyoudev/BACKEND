<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table): void {
            if (! Schema::hasColumn('channels', 'country')) {
                $table->string('country')->nullable()->after('group_title')->index();
            }

            if (! Schema::hasColumn('channels', 'aliases')) {
                $table->json('aliases')->nullable()->after('metadata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table): void {
            if (Schema::hasColumn('channels', 'country')) {
                $table->dropIndex(['country']);
                $table->dropColumn('country');
            }

            if (Schema::hasColumn('channels', 'aliases')) {
                $table->dropColumn('aliases');
            }
        });
    }
};
