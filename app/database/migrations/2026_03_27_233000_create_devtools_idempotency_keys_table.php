<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devtools_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('key_id')->constrained('devtools_api_keys')->cascadeOnDelete();
            $table->string('scope', 80);
            $table->char('token_hash', 64);
            $table->char('request_fingerprint', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->string('resource_type', 40)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['key_id', 'scope', 'token_hash'], 'devtools_idempotency_unique');
            $table->index(['scope', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devtools_idempotency_keys');
    }
};
