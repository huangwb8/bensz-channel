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
        Schema::create('login_codes', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 32)->index();
            $table->string('target')->index();
            $table->string('code', 32);
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['channel', 'target']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_codes');
    }
};
