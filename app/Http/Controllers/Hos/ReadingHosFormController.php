<?php

namespace App\Http\Controllers\Hos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReadingTeacherCasStoreRequest;
use App\Http\Requests\TeacherExamStoreRequest;
use App\Models\Exam;
use App\Models\ReadingAssignment;
use App\Models\ReadingCas;
use App\Models\ReadingCasType;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\SubjectTeacher;
use App\Models\Term;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReadingHosFormController extends Controller
{
    public function formIndex(int $subjectTeacherId)
    {
        try {
            $teacherId = auth()->id();

            $subjectTeacher = SubjectTeacher::where("id", $subjectTeacherId)->whereHas("subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();

            $terms = Term::where("grade_id", $subjectTeacher->section->grade->id)->get();

            $examFullMarks = (int) $subjectTeacher->subject->grade->school->theory_weightage;

            $casTypes = ReadingCasType::whereHas('school.grades', function ($query) use ($subjectTeacher) {
                return $query->where("id", $subjectTeacher->section->grade->id);
            })->get();

            $students = $subjectTeacher->section->students->where("status", "ACTIVE")->sortBy("roll_number");
            $subject = $subjectTeacher->subject;
            $studentExams = Student::whereHas("section.grade", function ($query) use ($subjectTeacher) {
                return $query->where("id", $subjectTeacher->section->grade->id);
            })->get()->where("status", "ACTIVE")->sortBy('roll_number');


            return view('hos.dashboard.readingForm', compact('subjectTeacher', 'terms', "casTypes", "students", "studentExams", "examFullMarks","subject"));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withInput()->withErrors(["Error", "Failed to add exam/cas marks"]);

        }
    }


    // Invoked when clicked Save and Submit Exam : Stores Exam marks permanently

    public function storeExam(int $subjectTeacherId, TeacherExamStoreRequest $request)
    {

        $data = $request->validated();

        DB::beginTransaction();

        try {

            $teacherId = auth()->id();

            $subjectTeacher = SubjectTeacher::where("id", $subjectTeacherId)->whereHas("subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();

            $termId = $data["term_id"];

            $fullMarksOfExam = $subjectTeacher->subject->grade->school->theory_weightage;





            $term = Term::where("id", $termId)->whereHas("grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();

            $examExists = Exam::where("subject_teacher_id", $subjectTeacher->id)->where("term_id", $term->id)->exists();


            if ($examExists) {
                throw new Exception("Exam already has marks");
            }


            foreach ($data["studentExams"] as $index => $studentId) {
                $studentExam = StudentExam::create([
                    "student_id" => $studentId,
                    "symbol_no" => $studentId + 10,
                ]);

                $examMark = $data["examMarks"][$index];

                if ($examMark > $fullMarksOfExam) {
                    throw new Exception("Marks cannot exceed " . $fullMarksOfExam);
                }

                Exam::create([
                    "student_exam_id" => $studentExam->id,
                    "term_id" => $term->id,
                    "subject_teacher_id" => $subjectTeacher->id,
                    "mark" => $examMark,
                ]);
            }

            DB::commit();
            return redirect(route('hosExams.index'))->with("success", "Stored Exam Marks Successfully");

        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to store exam marks: "]);
        }

    }

    // Invoked when clicked Save and Submit CAS, stores CAS marks and assignment permanently

    public function storeCas(int $subjectTeacherId, ReadingTeacherCasStoreRequest $request)
    {

        $data = $request->validated();
        DB::beginTransaction();

        try {
            $teacherId = auth()->id();

            $date = $data["date_assigned"];

            $subjectTeacher = SubjectTeacher::where("id", $subjectTeacherId)->whereHas("subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();


            $term = Term::whereHas("grade", function ($query) use ($subjectTeacher) {
                $query->where("id", $subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();

            $assignment = ReadingAssignment::create([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "subject_teacher_id" => $subjectTeacher->id,
                "reading_cas_type_id" => $data["cas_type"],
                "term_id" => $term->id,
                "submitted" => '1'
            ]);


            $fullMarks = $assignment->readingCasType->full_marks;

            $section = Section::whereHas("subjectTeachers", function ($query) use ($subjectTeacher) {
                $query->where("id", $subjectTeacher->id);
            })->firstOrFail();

            foreach ($data["students"] as $id => $student) {
                $student = Student::where("id", $student)->where("section_id", $section->id)->first();

                if ($data["marks"][$id] > $fullMarks) {
                    throw new Exception("CAS Marks cannot exceed " . $fullMarks);
                }

                if ($data["marks"][$id] == null) {
                    throw new Exception("CAS Marks cannot be null");
                }

                ReadingCas::create([
                    "student_id" => $student->id,
                    "readingAssignment_id" => $assignment->id,
                    "mark" => $data["marks"][$id],
                    "remarks" => ""
                ]);
            }
            DB::commit();

            return redirect(route('hosAssignments.index'))->with("success", "Stored CAS Marks Successfully");

        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to create CAS Marks"]);
        }
    }


    // Invoked when clicked Save CAS, stores CAS marks and assignment temporarily

    public function saveCas(int $subjectTeacherId, ReadingTeacherCasStoreRequest $request)
    {

        $data = $request->validated();
        DB::beginTransaction();

        try {
            $teacherId = auth()->id();

            $date = $data["date_assigned"];

            $subjectTeacher = SubjectTeacher::where("id", $subjectTeacherId)->whereHas("subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();


            $term = Term::whereHas("grade", function ($query) use ($subjectTeacher) {
                $query->where("id", $subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();

            $assignment = ReadingAssignment::create([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "subject_teacher_id" => $subjectTeacher->id,
                "reading_cas_type_id" => $data["cas_type"],
                "term_id" => $term->id,
                "submitted" => '0'
            ]);


            $fullMarks = $assignment->readingCasType->full_marks;

            $section = Section::whereHas("subjectTeachers", function ($query) use ($subjectTeacher) {
                $query->where("id", $subjectTeacher->id);
            })->firstOrFail();

            foreach ($data["students"] as $id => $student) {
                $student = Student::where("id", $student)->where("status", "ACTIVE")->where("section_id", $section->id)->first();

                if ($data["marks"][$id] > $fullMarks) {
                    throw new Exception("CAS Marks cannot exceed " . $fullMarks);
                }

                // Set the marks to -1 to show empty when the marks is not given initially ( helps remove confusion between numbers that are actually zero)
                if ($data["marks"][$id] == null) {
                    $data["marks"][$id] = 0;
                }

                ReadingCas::create([
                    "student_id" => $student->id,
                    "readingAssignment_id" => $assignment->id,
                    "mark" => $data["marks"][$id],
                    "remarks" => ""
                ]);
            }
            DB::commit();

            return redirect(route('hosAssignments.index'))->with("success", "Stored CAS Marks Successfully");

        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to create CAS Marks. "]);
        }
    }
}