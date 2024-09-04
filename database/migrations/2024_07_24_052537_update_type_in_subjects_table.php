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
        Schema::table('subjects', function (Blueprint $table) {
            DB::statement("ALTER TABLE `subjects` MODIFY `type` ENUM('MAIN', 'ECA', 'CREDIT', 'Club_ES', 'Club_1_MS', 'Club_2_MS', 'Club_HS','Reading_Book')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            DB::statement("ALTER TABLE `subjects` MODIFY `type` ENUM('MAIN', 'ECA', 'CREDIT')");
        });
    }
};
