<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\EcaTeacherCasStoreRequest;
use Illuminate\Http\Request;
use App\Models\EcaAssignment;
use App\Models\EcaCas;
use App\Models\EcaCasType;
use App\Models\Term;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcaAssignmentController extends Controller
{
    public function view(int $assignmentId)
    {
        try {
            $teacherId = auth()->id();

            $assignment = EcaAssignment::where("id", $assignmentId)->whereHas("subjectTeacher.teacher", function ($query) use ($teacherId) {
                return $query->where('id', $teacherId);
            })->firstOrFail();


            $cas = EcaCas::with('student')->where("ecaAssignment_id", $assignment->id)->whereHas('student',function ($query){
                return $query->where("status","ACTIVE");
            })->get()->sortBy("student.roll_number");

            return view("teacher.ecaAssignment.view", compact("assignment", "cas"));

        } catch (Exception $e) {
            return redirect()->back()->withErrors(["Error", "Assignment not found"]);
        }
    }

    public function edit(int $id)
    {
        try {
            $teacherId = auth()->id();

            // Verify if the user is authenticated to edit this assignment
            $assignment = EcaAssignment::where('id', $id)->whereHas('subjectTeacher.teacher', function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->firstOrFail();


            if ($assignment->submitted == '1') {
                return redirect()->back()->withErrors(["Error", "Cannot edit assignment that is submitted"]);
            }


            $subjectTeacher = $assignment->subjectTeacher;

            // Retrieve the castypes from the given grade request
            $casTypes = EcaCasType::whereHas("school.grades.sections.subjectTeachers", function ($query) use ($teacherId) {
                return $query->where("teacher_id", $teacherId);
            })->where("school_id", $subjectTeacher->section->grade->school_id)->get()->sortBy("name");

            $cas = EcaCas::with('student')->where("ecaAssignment_id", $assignment->id)->whereHas('student',function ($query){
                return $query->where("status","ACTIVE");
            })->get()->sortBy("student.roll_no");

            return view("teacher.ecaAssignment.edit", compact('assignment', 'casTypes', 'cas'));


        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->back()->withErrors(["Error", "Assignment not found"]);
        }
    }



    public function updateAndSave(int $assignmentId, EcaTeacherCasStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();


        try {
            $teacherId = auth()->id();

            $assignment = EcaAssignment::where("id", $assignmentId)->whereHas("subjectTeacher.teacher", function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->firstOrFail();



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
                $cas = EcaCas::where("student_id", $studentId)->where("ecaAssignment_id", $assignment->id)->whereHas('student',function ($query){
                    return $query->where("status","ACTIVE");
                })->firstOrFail();

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

            return redirect(route('teacherAssignments.index'))->with("success", "Edited CAS Marks Successfully");

        } catch (Exception $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to edit CAS Marks " . $e->getMessage()]);
        }
    }


    public function updateAndStore(int $assignmentId, EcaTeacherCasStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();


        try {
            $teacherId = auth()->id();

            $assignment = EcaAssignment::where("id", $assignmentId)->whereHas("subjectTeacher.teacher", function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->firstOrFail();


            $date = $data["date_assigned"];


            $termFromDate = Term::whereHas("grade", function ($query) use ($assignment) {
                $query->where("id", $assignment->subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();



            $assignment->update([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "eca_cas_type_id" => $data["cas_type"],
                "term_id" => $termFromDate->id,
                "submitted" => '1',
            ]);

            $casType = EcaCasType::findOrFail($data["cas_type"]);



            foreach ($data['students'] as $id => $studentId) {
                $cas = EcaCas::where("student_id", $studentId)->where("ecaAssignment_id", $assignment->id)->whereHas('student',function ($query){
                    return $query->where("status","ACTIVE");
                })->firstOrFail();

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

            return redirect(route('teacherAssignments.index'))->with("success", "Stored CAS Marks Successfully");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to submit CAS Marks"]);
        }
    }

    public function destroy(int $assignmentId)
    {
        DB::beginTransaction();
        try {

            $teacherId = auth()->id();

            $assignment = EcaAssignment::where("id", $assignmentId)->whereHas("subjectTeacher.teacher", function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->firstOrFail();

            $cas = $assignment->ecaCas;

            foreach ($cas as $casOne) {
                $casOne->delete();
            }

            $assignment->delete();
            DB::commit();


            return redirect(route('teacherAssignments.index'))->with("success", "Deleted CAS Marks Successfully");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());


            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to delete CAS Marks"]);
        }
    }

}