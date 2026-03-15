<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('user_id')
                ->constrained('comments')
                ->cascadeOnDelete();
            $table->foreignId('root_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('comments')
                ->cascadeOnDelete();
        });

        DB::table('comments')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $comment): void {
                DB::table('comments')
                    ->where('id', $comment->id)
                    ->update(['root_id' => $comment->id]);
            });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('root_id');
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
