<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminExamEditRequest;
use App\Http\Requests\ExamUpdateRequest;
use App\Models\Exam;
use App\Http\Requests\ExamStoreRequest;

use App\Models\StudentExam;
use App\Models\Term;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectTeacher;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $teacherId = auth()->id();

            $subjects = Subject::all()->sortBy("grade.name");


            $terms = Term::all()->sortBy('grade.name');

            return view("admin.exams.index", compact("terms", "subjects"));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(["Error", "Cannot retrieve exams"]);
        }
    }

    public function edit(int $termId, int $subjectId)
    {
        try {
            $teacherId = auth()->id();

            $term = Term::with('exams')->where("id", $termId)->firstOrFail();

            $subject = Subject::where("id", $subjectId)->firstOrFail();

            $terms = Term::all()->sortBy('grade.name');

            $exams = Exam::where("term_id", $term->id)->whereHas("subjectTeacher.subject", function ($query) use ($subjectId) {
                return $query->where("id", $subjectId);
            })->whereHas('studentExam.student', function ($query){
                return $query->where("status","ACTIVE");
            })->get()->sortBy("studentExam.student.section.roll_number");

            if ($exams->count() == 0) {
                throw new Exception("No Exam Marks Present");
            }

            $fullMark = (int) $term->grade->school->theory_weightage;


            return view('admin.exams.edit', compact("term", "exams", "subject", "terms", "fullMark"));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(["Error", "No Exam Marks Found"]);
        }
    }


    public function update(int $termId, int $subjectId, AdminExamEditRequest $request)
    {
        $data = $request->validated();
        // dd($data);
        try {
            $teacherId = auth()->id();

            $subjectTeacher = SubjectTeacher::where("subject_id", $subjectId)->firstOrFail();
            // dd($subjectTeacher);
            $term = Term::where("id", $termId)->firstOrFail();
            
            $fullMarksOfExam = $subjectTeacher->subject->grade->school->theory_weightage;
            // dd($fullMarksOfExam);
            foreach ($data["exams"] as $index => $examId) {

                $exam = Exam::where("id", $examId)->firstOrFail();
                // dd($exam);
                $examMark = $data['examMarks'][$index];

                if ($examMark > $fullMarksOfExam) {
                    throw new Exception("Marks cannot exceed " . $fullMarksOfExam);
                }

                $exam->update(["mark" => $examMark]);
            }

            DB::commit();
            return redirect(route('adminExams.index'))->with("success", "Stored Exam Marks Successfully");

        } catch (Exception $e) {
            // dd($e);
            Log::error($e->getMessage());
            DB::rollback();
            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to store exam marks"]);
        }
    }


    public function destroy(int $termId, int $subjectId)
    {
        DB::beginTransaction();
        try {
            $teacherId = auth()->id();

            $exams = Exam::where("term_id", $termId)->whereHas("subjectTeacher.subject", function ($query) use ($subjectId) {
                return $query->where("id", $subjectId);
            })->get();


            if ($exams->count() == 0) {
                return redirect(route('adminExams.index'))->with("error", "No Exam Marks Found");
            }
            foreach ($exams as $exam) {
                $exam->delete();
            }

            DB::commit();
            return redirect(route('adminExams.index'))->with("success", "Deleted Exam Marks Successfully");

        } catch (Exception $e) {
            // dd($e);
            Log::error($e->getMessage());
            DB::rollback();
            return redirect()->back()->withInput()->withErrors(["Error" => "Failed to delete exam marks"]);
        }

    }
}
