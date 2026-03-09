<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });

        Schema::create('user_id_sequences', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedBigInteger('next_value');
            $table->timestamps();
        });

        DB::table('user_id_sequences')->insert([
            'key' => 'stable_user_id',
            'next_value' => 101,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminEmail = strtolower((string) config('community.admin.email'));
        $systemAdminId = DB::table('users')
            ->when($adminEmail !== '', function ($query) use ($adminEmail): void {
                $query->whereRaw('lower(email) = ?', [$adminEmail]);
            }, function ($query): void {
                $query->whereRaw('1 = 0');
            })
            ->value('id');

        if ($systemAdminId === null) {
            $systemAdminId = DB::table('users')
                ->where('role', 'admin')
                ->orderBy('id')
                ->value('id');
        }

        if ($systemAdminId !== null) {
            DB::table('users')
                ->where('id', $systemAdminId)
                ->update(['user_id' => 0]);
        }

        $nextUserId = 101;

        DB::table('users')
            ->when($systemAdminId !== null, fn ($query) => $query->where('id', '!=', $systemAdminId))
            ->orderBy('id')
            ->select(['id'])
            ->get()
            ->each(function (object $user) use (&$nextUserId): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['user_id' => $nextUserId++]);
            });

        DB::table('user_id_sequences')
            ->where('key', 'stable_user_id')
            ->update(['next_value' => $nextUserId, 'updated_at' => now()]);

        Schema::table('users', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::dropIfExists('user_id_sequences');
    }
};
