<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_cup_matches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('match_number')->nullable()->unique();
            $table->string('competition')->default('FIFA World Cup 2026');
            $table->string('stage')->default('Group Stage');
            $table->string('group_name')->nullable()->index();
            $table->string('home_team', 120);
            $table->string('away_team', 120);
            $table->string('home_team_code', 12)->nullable();
            $table->string('away_team_code', 12)->nullable();
            $table->text('home_flag')->nullable();
            $table->text('away_flag')->nullable();
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->timestamp('kickoff_at')->nullable()->index();
            $table->dateTime('morocco_kickoff_at')->nullable()->index();
            $table->dateTime('local_kickoff_at')->nullable();
            $table->string('local_timezone')->nullable();
            $table->string('commentator', 120)->nullable();
            $table->foreignId('selected_channel_id')->nullable()->constrained('channels')->nullOnDelete();
            $table->string('channel_name_manual', 120)->nullable();
            $table->string('broadcaster', 120)->nullable();
            $table->text('live_url_manual')->nullable();
            $table->boolean('use_manual_live_url')->default(false);
            $table->boolean('is_live_link_enabled')->default(false);
            $table->string('broadcast_status', 24)->default('to_confirm')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->text('admin_notes')->nullable();
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_cup_matches');
    }
};
