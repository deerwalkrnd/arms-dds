<?php

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Models\EcaAssignment;
use App\Models\EcaCas;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class EcaAssignmentController extends Controller
{
    public function view(int $assignmentId)
    {
        try {

            $hodId = auth()->id();

            $assignment = EcaAssignment::where("id", $assignmentId)->whereHas("subjectTeacher.subject.department", function ($query) use ($hodId) {
                return $query->where('head_of_department_id', $hodId);
            })->firstOrFail();

            $cas = EcaCas::with('student')->where("ecaAssignment_id", $assignment->id)->get()->sortBy("student.roll_no");

            return view("hod.ecaAssignment.view", compact("assignment", "cas"));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(["Error", "Assignment not found"]);
        }
    }
}