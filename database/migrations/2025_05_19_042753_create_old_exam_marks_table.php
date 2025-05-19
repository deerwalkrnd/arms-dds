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
        Schema::create('old_exam_marks', function (Blueprint $table) {
            $table->id();
            $table->string('roll_no')->nullable();
            $table->string('emis_no')->nullable();
            $table->string('student_name')->nullable();
            $table->string('student_father_name')->nullable();
            $table->string('student_father_profession')->nullable();
            $table->string('student_mother_name')->nullable();
            $table->string('student_mother_profession')->nullable();
            $table->string('student_guardian_name')->nullable();
            $table->string('student_guardian_profession')->nullable();
            $table->string('section_name')->nullable();
            $table->string('grade_name')->nullable();
            $table->string('subject_name')->nullable();
            $table->string('subject_code')->nullable();
            $table->string('subject_teacher_name')->nullable();
            $table->string('term_name')->nullable();
            $table->string('term_marks')->nullable();
            $table->string('term_full_marks')->nullable();
            $table->string('department_name')->nullable();
            $table->string('school_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('old_exam_marks');
    }
};
