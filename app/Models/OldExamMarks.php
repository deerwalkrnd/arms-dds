<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldExamMarks extends Model
{
    use HasFactory;

    protected $table = "old_exam_marks";
    protected $fillable = [
        "roll_no",
        "emis_no",
        "student_name",
        "student_father_name",
        "student_father_profession",
        "student_mother_name",
        "student_mother_profession",
        "student_guardian_name",
        "student_guardian_profession",
        "section_name",
        "grade_name",
        "subject_name",
        "subject_code",
        "subject_teacher_name",
        "term_name",
        "term_marks",
        "term_full_marks",
        "department_name",
        "school_name",
    ];
}
