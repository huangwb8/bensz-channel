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
        Schema::create('qr_login_requests', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->index();
            $table->uuid('token')->unique();
            $table->string('status', 32)->default('pending')->index();
            $table->foreignId('approved_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_login_requests');
    }
};
