<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClubTeacherCasStoreRequest;
use App\Http\Requests\TeacherExamStoreRequest;
use App\Models\ClubAssignment;
use App\Models\ClubCas;
use App\Models\EcaCas;
use App\Models\EcaCasType;
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

class ClubAssignmentController extends Controller
{
    public function edit(int $assignmentId)
    {
        try {
            $teacherId = auth()->id();

            // Verify if the user is authenticated to edit this assignment
            $assignment = ClubAssignment::where('id', $assignmentId)->firstOrFail();


            $subjectTeacher = $assignment->subjectTeacher;

            // Retrieve the castypes from the given grade request
            $casTypes = EcaCasType::where("school_id", $subjectTeacher->section->grade->school_id)->get();

            $cas = ClubCas::with('student')->where("clubAssignment_id", $assignment->id)->whereHas('student',function ($query){
                return $query->where("status","ACTIVE");
            })->get()->sortBy("student.roll_no");
            
      
            return view("admin.clubAssignments.edit", compact('assignment', 'casTypes', 'cas'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(["Error", "Assignment not found"]);
        }
    }

    public function updateAndSave(int $assignmentId, ClubTeacherCasStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();


        try {
            $teacherId = auth()->id();

            $assignment = ClubAssignment::where("id", $assignmentId)->firstOrFail();
            $schoolId=$assignment->ecaCasType->school->id;


            $date = $data["date_assigned"];


            $term = Term::whereHas("grade", function ($query) use ($assignment) {
                $query->where("id", $assignment->subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();

            $assignment->update([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "eca_cas_type_id" => $data["cas_type"],
                "term_id" => $term->id,
                "submitted" => '0',
            ]);

            $casType = EcaCasType::findOrFail($data["cas_type"]);


            foreach ($data['students'] as $id => $studentId) {

                $cas = ClubCas::where("student_id", $studentId)->whereHas('student',function ($query){
                    return $query->where("status","ACTIVE");
                })->where("clubAssignment_id", $assignment->id)->firstOrFail();

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

            return redirect()->route('adminAssignments.index',['id'=>$schoolId])->with("success", "Edited CAS Marks Successfully");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to edit CAS Marks"]);
        }
    }
    public function updateAndStore(int $assignmentId, ClubTeacherCasStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();


        try {
            $teacherId = auth()->id();

            $assignment = ClubAssignment::where("id", $assignmentId)->firstOrFail();

            $schoolId=$assignment->ecaCasType->school->id;

            $date = $data["date_assigned"];


            $term = Term::whereHas("grade", function ($query) use ($assignment) {
                $query->where("id", $assignment->subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();

            $assignment->update([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "eca_cas_type_id" => $data["cas_type"],
                "term_id" => $term->id,
                "submitted" => '1',
            ]);

            $casType = EcaCasType::findOrFail($data["cas_type"]);


            foreach ($data['students'] as $id => $studentId) {

                $cas = ClubCas::where("student_id", $studentId)->whereHas('student',function ($query){
                    return $query->where("status","ACTIVE");
                })->where("clubAssignment_id", $assignment->id)->firstOrFail();

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

            return redirect()->route('adminAssignments.index',['id'=>$schoolId])->with("success", "Edited CAS Marks Successfully");
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

            $assignment = ClubAssignment::where("id", $assignmentId)->firstOrFail();

            $cas = $assignment->clubCas;
            foreach ($cas as $casOne) {
                $casOne->delete();
            }

            $assignment->delete();
            DB::commit();


            return redirect()->back()->with("success", "Deleted CAS Marks Successfully");

        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to delete CAS Marks"]);
        }
    }
}
