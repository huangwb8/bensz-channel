<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cdn_sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('trigger', 50);
            $table->string('status', 20);
            $table->string('mode', 20);
            $table->string('provider', 50)->nullable();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('uploaded_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('deleted_count')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cdn_sync_logs');
    }
};
