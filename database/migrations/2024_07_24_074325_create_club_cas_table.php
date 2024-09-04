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
        Schema::create('club_cas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("student_id");
            $table->unsignedBigInteger("clubAssignment_id");
            $table->float("mark");
            $table->text("remarks");

            $table->timestamps();

            // Foreign Key

            $table->foreign("clubAssignment_id")->references("id")->on("club_assignments")->onDelete("restrict")->onUpdate("cascade");
            $table->foreign("student_id")->references("id")->on("students")->onDelete("restrict")->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_cas');
    }
};
