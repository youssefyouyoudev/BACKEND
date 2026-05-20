<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('channels', 'normalized_name')) {
            Schema::table('channels', function (Blueprint $table): void {
                $table->string('normalized_name')->nullable()->after('name')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('channels', 'normalized_name')) {
            Schema::table('channels', function (Blueprint $table): void {
                $table->dropIndex(['normalized_name']);
                $table->dropColumn('normalized_name');
            });
        }
    }
};
