<?php

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Models\ClubAssignment;
use App\Models\ClubCas;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class ClubAssignmentController extends Controller
{
    public function view(int $assignmentId)
    {
        try {

            $hodId = auth()->id();

            $assignment = ClubAssignment::where("id", $assignmentId)->whereHas("subjectTeacher.subject.department", function ($query) use ($hodId) {
                return $query->where('head_of_department_id', $hodId);
            })->firstOrFail();

            $cas = ClubCas::with('student')->where("clubAssignment_id", $assignment->id)->get()->sortBy("student.roll_no");

            return view("hod.clubAssignment.view", compact("assignment", "cas"));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(["Error", "Assignment not found"]);
        }
    }
}