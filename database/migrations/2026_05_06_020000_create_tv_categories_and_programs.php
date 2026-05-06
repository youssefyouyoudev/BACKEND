<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color', 24)->default('#76db3a');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('channels', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->foreignId('category_id')->nullable()->after('group_title')->constrained('categories')->nullOnDelete();
            $table->boolean('is_live')->default(true)->after('stream_type')->index();
            $table->index('slug');
            $table->index('category_id');
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('start_time')->index();
            $table->dateTime('end_time')->index();
            $table->text('description')->nullable();
            $table->string('rating', 16)->nullable();
            $table->string('language', 10)->default('en');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'start_time', 'end_time']);
        });

        $groups = DB::table('channels')
            ->whereNotNull('group_title')
            ->select('group_title')
            ->distinct()
            ->pluck('group_title')
            ->filter()
            ->values();

        foreach ($groups as $index => $group) {
            $slug = Str::slug((string) $group) ?: 'category-'.$index;

            DB::table('categories')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $group,
                    'color' => $this->colorForIndex($index),
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('channels')
            ->orderBy('id')
            ->select(['id', 'name', 'group_title'])
            ->get()
            ->each(function (object $channel): void {
                $categoryId = null;

                if ($channel->group_title) {
                    $categoryId = DB::table('categories')
                        ->where('slug', Str::slug((string) $channel->group_title))
                        ->value('id');
                }

                DB::table('channels')
                    ->where('id', $channel->id)
                    ->update([
                        'slug' => Str::slug($channel->name).'-'.$channel->id,
                        'category_id' => $categoryId,
                        'is_live' => true,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');

        Schema::table('channels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropIndex(['slug']);
            $table->dropColumn(['slug', 'is_live']);
        });

        Schema::dropIfExists('categories');
    }

    private function colorForIndex(int $index): string
    {
        return ['#76db3a', '#38bdf8', '#f59e0b', '#ef4444', '#a78bfa', '#14b8a6'][$index % 6];
    }
};
