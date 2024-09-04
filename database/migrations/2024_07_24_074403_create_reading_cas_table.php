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
        Schema::create('reading_cas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("student_id");
            $table->unsignedBigInteger("readingAssignment_id");
            $table->float("mark");
            $table->text("remarks");

            $table->timestamps();

            // Foreign Key

            $table->foreign("readingAssignment_id")->references("id")->on("reading_assignments")->onDelete("restrict")->onUpdate("cascade");
            $table->foreign("student_id")->references("id")->on("students")->onDelete("restrict")->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_cas');
    }
};
