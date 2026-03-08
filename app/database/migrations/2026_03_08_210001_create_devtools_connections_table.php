<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devtools_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('key_id');
            $table->foreign('key_id')->references('id')->on('devtools_api_keys')->cascadeOnDelete();
            $table->string('client_name', 120)->nullable();
            $table->string('client_version', 40)->nullable();
            $table->string('machine', 120)->nullable();
            $table->string('workdir', 512)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('terminate_requested_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamps();

            $table->index('key_id');
            $table->index('terminated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devtools_connections');
    }
};
