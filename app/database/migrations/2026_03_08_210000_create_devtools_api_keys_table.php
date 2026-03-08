<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devtools_api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80)->default('default');
            $table->string('key_hash', 64); // SHA-256 hex
            $table->string('key_prefix', 16);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique('key_hash');
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devtools_api_keys');
    }
};
