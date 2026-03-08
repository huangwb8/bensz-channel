<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('email_all_articles')->default(true);
            $table->boolean('email_mentions')->default(true);
            $table->timestamps();
        });

        $now = now();

        $userIds = DB::table('users')->pluck('id');

        if ($userIds->isNotEmpty()) {
            DB::table('user_notification_preferences')->insert(
                $userIds->map(fn (int $userId) => [
                    'user_id' => $userId,
                    'email_all_articles' => true,
                    'email_mentions' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
