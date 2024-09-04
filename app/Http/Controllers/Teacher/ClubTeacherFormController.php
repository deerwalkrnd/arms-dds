<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClubTeacherCasStoreRequest;
use App\Models\ClubAssignment;
use App\Models\ClubCas;
use App\Models\EcaAssignment;
use App\Models\EcaCas;
use App\Models\EcaCasType;
use App\Models\Exam;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\SubjectTeacher;
use App\Models\Term;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ClubTeacherFormController extends Controller
{
    public function clubFormIndex(int $subjectTeacherId)
    {
        try {
            $teacherId = auth()->id();
            $subjectTeacher = SubjectTeacher::where("id", $subjectTeacherId)->where("teacher_id", $teacherId)->firstOrFail();

            $terms = Term::where("grade_id", $subjectTeacher->section->grade->id)->get();

            $casTypes = EcaCasType::whereHas('school.grades', function ($query) use ($subjectTeacher) {
                return $query->where("id", $subjectTeacher->section->grade->id);
            })->get();

            $subject = $subjectTeacher->subject;

            $examFullMarks = (int) $subjectTeacher->subject->grade->school->theory_weightage;

            $students = $subjectTeacher->section->students->where("status", "ACTIVE")->sortBy("roll_number");

            $studentExams = Student::whereHas("section.grade", function ($query) use ($subjectTeacher) {
                return $query->where("id", $subjectTeacher->section->grade->id);
            })->get()->where("status", "ACTIVE")->sortBy('roll_number');


            return view('teacher.dashboard.clubForm', compact('subjectTeacher', 'terms', "casTypes", "students", "studentExams", "examFullMarks", "subject"));

        } catch (Exception $e) {
            return redirect()->back()->withInput()->withErrors(["Error", "Failed to add exam/cas marks"]);

        }
    }


    // Invoked when clicked Save and Submit Exam : Stores Exam marks permanently

    public function storeExam(int $subjectTeacherId, ClubTeacherCasStoreRequest $request)
    {

        $data = $request->validated();



        try {
            $subjectTeacher = SubjectTeacher::findOrFail($subjectTeacherId);


            $termId = $data['term_id'];

            $fullMarksOfExam = $subjectTeacher->subject->grade->school->theory_weightage;

            $term = Term::where("id", $termId)->whereHas("grade", function ($query) use ($subjectTeacher) {
                $query->where("id", $subjectTeacher->subject->grade_id);
            })->firstOrFail();

            $examExists = Exam::where("subject_teacher_id", $subjectTeacher->id)->where("term_id", $term->id)->exists();


            if ($examExists) {
                throw new Exception("Exam already has marks");
            }

            foreach ($data["studentExams"] as $index => $studentId) {



                $studentExam = StudentExam::create([
                    "student_id" => $studentId,
                    "symbol_no" => Student::findOrFail($studentId)->roll_number
                ]);

                $examMark = $data['examMarks'][$index];

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
            return redirect(route('teacherExams.index'))->with("success", "Stored Exam Marks Successfully");

        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to store exam marks: Exam Marks already exists / Marks exceed full marks"]);
        }

    }


    // Invoked when clicked Save and Submit CAS, stores CAS marks and assignment permanently

    public function storeCas(int $subjectTeacherId, ClubTeacherCasStoreRequest $request)
    {


        $data = $request->validated();
        DB::beginTransaction();
        try {
            $date = $data["date_assigned"];

            $subjectTeacher = SubjectTeacher::findOrFail($subjectTeacherId);

            $term = Term::whereHas("grade", function ($query) use ($subjectTeacher) {
                $query->where("id", $subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();

            $assignment = ClubAssignment::create([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "subject_teacher_id" => $subjectTeacher->id,
                "eca_cas_type_id" => $data["cas_type"],
                "term_id" => $term->id,
                "submitted" => '1'
            ]);


            $fullMarks = $assignment->ecaCasType->full_marks;


            $section = Section::whereHas("subjectTeachers", function ($query) use ($subjectTeacher) {
                $query->where("id", $subjectTeacher->id);
            })->firstOrFail();

            foreach ($data["students"] as $id => $student) {
                $student = Student::where("id", $student)->where("section_id", $section->id)->where("status", "ACTIVE")->first();

                if ($data["marks"][$id] > $fullMarks) {
                    throw new Exception("CAS Marks cannot exceed " . $fullMarks);
                }

                if ($data["marks"][$id] == null) {
                    throw new Exception("CAS Marks cannot be null");
                }

                ClubCas::create([
                    "student_id" => $student->id,
                    "clubAssignment_id" => $assignment->id,
                    "mark" => $data["marks"][$id],
                    "remarks" => ""
                ]);
            }
            DB::commit();

            return redirect(route('teacherAssignments.index'))->with("success", "Stored CAS Marks Successfully");

        } catch (Exception $e) {
            Log::error($e);
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to create CAS Marks"]);
        }

    }

    public function saveCas(int $subjectTeacherId, ClubTeacherCasStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();


        try {
            $date = $data["date_assigned"];

            $subjectTeacher = SubjectTeacher::findOrFail($subjectTeacherId);

            $term = Term::whereHas("grade", function ($query) use ($subjectTeacher) {
                $query->where("id", $subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();
            $casType = EcaCasType::findOrFail($data['cas_type']);
           
            $assignment = ClubAssignment::create([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "subject_teacher_id" => $subjectTeacher->id,
                "eca_cas_type_id" => $data["cas_type"],
                "term_id" => $term->id,
                "submitted" => '0',
            ]);
            $fullMarks = $assignment->ecaCasType->full_marks;


            $section = Section::whereHas("subjectTeachers", function ($query) use ($subjectTeacher) {
                $query->where("id", $subjectTeacher->id);
            })->firstOrFail();
            $casType = EcaCasType::findOrFail($data['cas_type']);
            foreach ($data["students"] as $id => $student) {
                $student = Student::where("id", $student)->where("section_id", $section->id)->where("status", "ACTIVE")->first();

                if ($data["marks"][$id] > $fullMarks) {
                    throw new Exception("CAS Marks cannot exceed " . $fullMarks);
                }


                // Set the marks to -1 to show empty when the marks is not given initially ( helps remove confusion between numbers that are actually zero)
                if ($data["marks"][$id] == null) {
                    $data["marks"][$id] = 0;
                }

                ClubCas::create([
                    "student_id" => $student->id,
                    "clubAssignment_id" => $assignment->id,
                    "mark" => $data["marks"][$id],
                    "remarks" => ""
                ]);
            }
            DB::commit();

            return redirect(route('teacherAssignments.index'))->with("success", "Saved CAS Marks Successfully");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to save cas. ". $e);
            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to save CAS Marks"]);
        }
    }

}