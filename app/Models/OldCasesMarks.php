<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldCasesMarks extends Model
{
    use HasFactory;
    protected $table = "old_cases_marks";
    protected $fillable = [
        "cas_school_name",
        "cas_type",
        "cas_type_name",
        "cas_marks",
        "cas_full_marks",
        "assignment_name",
        "subject_name",
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
        "subject_teacher_name",
        "term_name",
        "department_name",
    ];
}
