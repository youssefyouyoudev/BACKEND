<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40)->default('monetag')->index();
            $table->string('placement_key', 80)->index();
            $table->longText('script_code')->nullable();
            $table->text('direct_link_url')->nullable();
            $table->boolean('enabled')->default(false)->index();
            $table->string('device', 20)->default('all')->index();
            $table->unsignedInteger('frequency_seconds')->default(1800);
            $table->unsignedSmallInteger('max_per_session')->default(1);
            $table->boolean('test_mode')->default(false);
            $table->timestamps();

            $table->unique(['provider', 'placement_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_settings');
    }
};
