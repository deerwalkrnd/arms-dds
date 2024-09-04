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
        Schema::create('club_assignments', function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable();
            $table->text("description")->nullable();
            $table->date("date_assigned");
            $table->unsignedBigInteger("subject_teacher_id");
            $table->unsignedBigInteger("eca_cas_type_id");
            $table->unsignedBigInteger("term_id");
            $table->enum("submitted", ['0', '1'])->default("0");
            $table->timestamps();

            // Foreign Key
            $table->foreign("eca_cas_type_id")->references("id")->on("eca_cas_types")->onDelete("restrict")->onUpdate("cascade");
            $table->foreign("subject_teacher_id")->references("id")->on("subject_teachers")->onDelete("restrict")->onUpdate("cascade");
            $table->foreign("term_id")->references("id")->on("terms")->onDelete("restrict")->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_assignments');
    }
};
