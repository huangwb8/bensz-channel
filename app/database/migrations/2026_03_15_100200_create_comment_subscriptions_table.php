<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['comment_id', 'user_id']);
        });

        $now = now();

        $subscriptions = DB::table('comments')
            ->select('id as comment_id', 'user_id')
            ->orderBy('id')
            ->get()
            ->map(fn (object $comment): array => [
                'comment_id' => $comment->comment_id,
                'user_id' => $comment->user_id,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($subscriptions !== []) {
            DB::table('comment_subscriptions')->insert($subscriptions);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_subscriptions');
    }
};
