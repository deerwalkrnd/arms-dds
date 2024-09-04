<?php

namespace App\Http\Controllers\Hos;


use App\Http\Controllers\Controller;
use App\Http\Requests\ReadingTeacherCasStoreRequest;
use App\Http\Requests\TeacherExamStoreRequest;
use App\Models\ReadingAssignment;
use App\Models\ReadingCas;
use App\Models\ReadingCasType;
use App\Models\Exam;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\SubjectTeacher;
use App\Models\Term;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReadingAssignmentController extends Controller
{
    public function edit(int $assignmentId)
    {
        try {
            $teacherId = auth()->id();

            // Verify if the user is authenticated to edit this assignment
            $assignment = ReadingAssignment::where('id', $assignmentId)->whereHas('subjectTeacher.subject.grade.school', function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();


            $subjectTeacher = $assignment->subjectTeacher;

            // Retrieve the castypes from the given grade request
            $casTypes = ReadingCasType::whereHas("school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->where("school_id", $subjectTeacher->section->grade->school_id)->get();

            $cas = ReadingCas::with('student')->where("readingAssignment_id", $assignment->id)->whereHas('student',function ($query){
                return $query->where("status","ACTIVE");
            })->get()->sortBy("student.roll_no");
            
      
            return view("hos.readingAssignments.edit", compact('assignment', 'casTypes', 'cas'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(["Error", "Assignment not found"]);
        }
    }

    public function updateAndSave(int $assignmentId, ReadingTeacherCasStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();


        try {
            $teacherId = auth()->id();

            $assignment = ReadingAssignment::where("id", $assignmentId)->whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();



            $date = $data["date_assigned"];


            $term = Term::whereHas("grade", function ($query) use ($assignment) {
                $query->where("id", $assignment->subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();

            $assignment->update([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "reading_cas_type_id" => $data["cas_type"],
                "term_id" => $term->id,
                "submitted" => '0',
            ]);

            $casType = ReadingCasType::findOrFail($data["cas_type"]);


            foreach ($data['students'] as $id => $studentId) {

                $cas = ReadingCas::where("student_id", $studentId)->whereHas('student',function ($query){
                    return $query->where("status","ACTIVE");
                })->where("readingAssignment_id", $assignment->id)->firstOrFail();

                $formMark = $data["marks"][$id];

                if ($formMark > $casType->full_marks) {
                    throw new Exception("CAS Marks cannot exceed full marks");
                }

                if ($formMark == null) {
                    $formMark = 0;
                }

                $cas->update([
                    "mark" => $formMark
                ]);
            }

            DB::commit();

            return redirect(route('hosAssignments.index'))->with("success", "Edited CAS Marks Successfully");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to edit CAS Marks"]);
        }
    }
    public function updateAndStore(int $assignmentId, ReadingTeacherCasStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();


        try {
            $teacherId = auth()->id();

            $assignment = ReadingAssignment::where("id", $assignmentId)->whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();



            $date = $data["date_assigned"];


            $term = Term::whereHas("grade", function ($query) use ($assignment) {
                $query->where("id", $assignment->subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();

            $assignment->update([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "reading_cas_type_id" => $data["cas_type"],
                "term_id" => $term->id,
                "submitted" => '1',
            ]);

            $casType = ReadingCasType::findOrFail($data["cas_type"]);


            foreach ($data['students'] as $id => $studentId) {

                $cas = ReadingCas::where("student_id", $studentId)->whereHas('student',function ($query){
                    return $query->where("status","ACTIVE");
                })->where("readingAssignment_id", $assignment->id)->firstOrFail();

                $formMark = $data["marks"][$id];

                if ($formMark > $casType->full_marks) {
                    throw new Exception("CAS Marks cannot exceed full marks");
                }

                if ($formMark == null) {
                    throw new Exception("CAS Marks cannot be null");
                }

                $cas->update([
                    "mark" => $formMark
                ]);
            }

            DB::commit();

            return redirect(route('hosAssignments.index'))->with("success", "Edited CAS Marks Successfully");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to edit CAS Marks"]);
        }
    }
    public function destroy(int $assignmentId)
    {
        DB::beginTransaction();
        try {

            $teacherId = auth()->id();

            $assignment = ReadingAssignment::where("id", $assignmentId)->whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();

            $cas = $assignment->readingCas;
            foreach ($cas as $casOne) {
                $casOne->delete();
            }

            $assignment->delete();
            DB::commit();


            return redirect(route('hosAssignments.index'))->with("success", "Deleted CAS Marks Successfully");

        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to delete CAS Marks"]);
        }
    }
}