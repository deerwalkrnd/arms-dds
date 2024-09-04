<?php

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Models\ReadingAssignment;
use App\Models\ReadingCas;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class ReadingAssignmentController extends Controller
{
    public function view(int $assignmentId)
    {
        try {

            $hodId = auth()->id();

            $assignment = ReadingAssignment::where("id", $assignmentId)->whereHas("subjectTeacher.subject.department", function ($query) use ($hodId) {
                return $query->where('head_of_department_id', $hodId);
            })->firstOrFail();

            $cas = ReadingCas::with('student')->where("readingAssignment_id", $assignment->id)->get()->sortBy("student.roll_no");

            return view("hod.readingAssignment.view", compact("assignment", "cas"));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(["Error", "Assignment not found"]);
        }
    }
}