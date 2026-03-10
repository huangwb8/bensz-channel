<?php

use App\Support\PublicIdGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->char('public_id', 16)->nullable()->after('id');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->char('public_id', 16)->nullable()->after('id');
        });

        $this->backfillTable('channels');
        $this->backfillTable('articles');

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE channels ALTER COLUMN public_id SET NOT NULL');
            DB::statement('ALTER TABLE articles ALTER COLUMN public_id SET NOT NULL');
        }

        Schema::table('channels', function (Blueprint $table) {
            $table->unique('public_id');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });

        Schema::table('channels', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }

    private function backfillTable(string $table): void
    {
        DB::table($table)
            ->select('id')
            ->orderBy('id')
            ->each(function (object $row) use ($table): void {
                DB::table($table)
                    ->where('id', $row->id)
                    ->update([
                        'public_id' => $this->generateUniquePublicId($table),
                    ]);
            });
    }

    private function generateUniquePublicId(string $table): string
    {
        do {
            $publicId = PublicIdGenerator::make();
        } while (DB::table($table)->where('public_id', $publicId)->exists());

        return $publicId;
    }
};
