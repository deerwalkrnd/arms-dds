<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignmentEditRequest;
use App\Http\Requests\AssignmentRequest;
use App\Models\ClubAssignment;
use App\Models\ClubCas;
use App\Models\EcaAssignment;
use App\Models\EcaCas;
use App\Models\EcaCasType;
use App\Models\ReadingAssignment;
use App\Models\ReadingCas;
use App\Models\ReadingCasType;
use App\Models\Cas;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectTeacher;

use App\Http\Requests\TeacherCasStoreRequest;

use Illuminate\Http\Request;
use App\Models\Assignment;

use App\Models\CasType;
use App\Models\Section;
use App\Models\Student;

use App\Models\Term;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $teacher_id = auth()->id();

            $assignments = Assignment::whereHas("subjectTeacher", function ($query) use ($teacher_id) {
                $query->where("teacher_id", $teacher_id);
            })->with(['subjectTeacher', 'casType'])->get();
            $ecaAssignments = EcaAssignment::whereHas("subjectTeacher", function ($query) use ($teacher_id) {
                $query->where("teacher_id", $teacher_id);
            })->with(['subjectTeacher', 'ecaCasType'])->get();
            $clubAssignments = ClubAssignment::whereHas("subjectTeacher", function ($query) use ($teacher_id) {
                $query->where("teacher_id", $teacher_id);
            })->with(['subjectTeacher', 'ecaCasType'])->get();
            $readingAssignments = ReadingAssignment::whereHas("subjectTeacher", function ($query) use ($teacher_id) {
                $query->where("teacher_id", $teacher_id);
            })->with(['subjectTeacher', 'readingCasType'])->get();
             // Merge all assignments into a single collection
             $allAssignments = collect()
             ->merge($assignments)
             ->merge($ecaAssignments)
             ->merge($clubAssignments)
             ->merge($readingAssignments);

         // Sort the collection by 'created_at' in descending order
         $sortedAssignments = $allAssignments->sortByDesc('updated_at');
            return view("teacher.assignment.index", compact("assignments", "ecaAssignments", "clubAssignments", "readingAssignments", "sortedAssignments"));
        } catch (Exception $e) {
            return redirect()->back()->withErrors(["Error", "Failed to retrieve assignments"]);
        }
    }

    public function view(int $assignmentId)
    {
        try {
            $teacherId = auth()->id();

            $assignment = Assignment::where("id", $assignmentId)->whereHas("subjectTeacher.teacher", function ($query) use ($teacherId) {
                return $query->where('id', $teacherId);
            })->firstOrFail();


            $cas = Cas::with('student')->where("assignment_id", $assignment->id)->whereHas('student',function ($query){
                return $query->where("status","ACTIVE");
            })->get()->sortBy("student.roll_number");

            return view("teacher.assignment.view", compact("assignment", "cas"));

        } catch (Exception $e) {

            return redirect()->back()->withErrors(["Error", "Assignment not found"]);
        }
    }

    public function edit(int $id)
    {
        try {
            $teacherId = auth()->id();

            // Verify if the user is authenticated to edit this assignment
            $assignment = Assignment::where('id', $id)->whereHas('subjectTeacher.teacher', function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->firstOrFail();


            if ($assignment->submitted == '1') {
                return redirect()->back()->withErrors(["Error", "Cannot edit assignment that is submitted"]);
            }


            $subjectTeacher = $assignment->subjectTeacher;

            // Retrieve the castypes from the given grade request
            $casTypes = CasType::whereHas("school.grades.sections.subjectTeachers", function ($query) use ($teacherId) {
                return $query->where("teacher_id", $teacherId);
            })->where("school_id", $subjectTeacher->section->grade->school_id)->get()->sortBy("name");

            $cas = Cas::with('student')->where("assignment_id", $assignment->id)->whereHas('student',function ($query){
                return $query->where("status","ACTIVE");
            })->get()->sortBy("student.roll_no");

            return view("teacher.assignment.edit", compact('assignment', 'casTypes', 'cas'));


        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->back()->withErrors(["Error", "Assignment not found"]);
        }
    }



    public function updateAndSave(int $assignmentId, TeacherCasStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();


        try {
            $teacherId = auth()->id();

            $assignment = Assignment::where("id", $assignmentId)->whereHas("subjectTeacher.teacher", function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->firstOrFail();



            $date = $data["date_assigned"];


            $term = Term::whereHas("grade", function ($query) use ($assignment) {
                $query->where("id", $assignment->subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();

            $assignment->update([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "cas_type_id" => $data["cas_type"],
                "term_id" => $term->id,
                "submitted" => '0',
            ]);

            $casType = CasType::findOrFail($data["cas_type"]);


            foreach ($data['students'] as $id => $studentId) {
                $cas = Cas::where("student_id", $studentId)->where("assignment_id", $assignment->id)->whereHas('student',function ($query){
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


    public function updateAndStore(int $assignmentId, TeacherCasStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();


        try {
            $teacherId = auth()->id();

            $assignment = Assignment::where("id", $assignmentId)->whereHas("subjectTeacher.teacher", function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->firstOrFail();


            $date = $data["date_assigned"];


            $termFromDate = Term::whereHas("grade", function ($query) use ($assignment) {
                $query->where("id", $assignment->subjectTeacher->subject->grade_id);
            })->where("start_date", "<=", $date)->where("end_date", ">", $date)->firstOrFail();



            $assignment->update([
                "name" => "Week " . $data['assignment_name'],
                "date_assigned" => $data["date_assigned"],
                "cas_type_id" => $data["cas_type"],
                "term_id" => $termFromDate->id,
                "submitted" => '1',
            ]);

            $casType = CasType::findOrFail($data["cas_type"]);



            foreach ($data['students'] as $id => $studentId) {
                $cas = Cas::where("student_id", $studentId)->where("assignment_id", $assignment->id)->whereHas('student',function ($query){
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



    public function reportIndex()
    {
        try {
            $teacherId = auth()->id();



            $subjectTeachers = SubjectTeacher::where("teacher_id", $teacherId)->get();
            $terms = Term::whereHas("grade.sections.subjectTeachers.teacher", function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->get()->sortBy("grade.name");

            // $casLatest = Cas::whereHas("assignment.subjectTeacher.teacher", function ($query) use ($teacherId) {
            //     return $query->where("id", $teacherId);
            // })->whereHas("assignment", function ($query) {
            //     return $query->where("submitted", "1");
            // })->latest("updated_at")->firstOrFail();

            // $latestSubjectTeacher = $casLatest->assignment->subjectTeacher;

            $casTypes = CasType::whereHas("school.grades.sections.subjectTeachers.teacher", function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->get()->sortBy("name");

            $currentDate = Carbon::now();


            // $currentTerm = $casLatest->assignment->term;


            // $cas = Cas::whereHas("assignment.subjectTeacher", function ($query) use ($latestSubjectTeacher) {
            //     return $query->where("id", $latestSubjectTeacher->id);
            // })->whereHas("assignment.term", function ($query) use ($currentTerm) {
            //     return $query->where("id", $currentTerm->id);
            // })->get()->sortBy("assignment.casType")->groupBy(["assignment.casType.name", "assignment"]);


             // Students that have cas marks
            //  $studentIdsWithMarks = $cas->flatMap(function ($types) {
            //     return $types->flatMap(function ($assignments) {
            //         return $assignments->pluck('student_id');
            //     });
            // })->unique();

            // Filter students based on the collected IDs
            // $students = $latestSubjectTeacher->section->students
            //     ->where("status", "ACTIVE")
            //     ->filter(function ($student) use ($studentIdsWithMarks) {
            //         return $studentIdsWithMarks->contains($student->id);
            //     })->sortBy("roll_number");



            return view("teacher.assignment.reportIndex", compact("terms", "subjectTeachers",));


        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->back()->withErrors(["Error", "No CAS found"]);
        }
    }

    public function reportSearch(Request $request)
    {
        try {
            $isNullCasType = $request->casType == "null";
            $isNullSubjectTeacher = $request->subjectTeacher == null;
            $isNullTerm = $request->term == "null";
            if ($isNullCasType && ($isNullSubjectTeacher && $isNullTerm)) {
                return redirect(route("teacherCasReport.index"))->withErrors(["Error", "Please select all filters"]);
            }

            $termId = $request->term;
            $subjectTeacherId = $request->subjectTeacher;

            $term = Term::findOrFail($termId);
            $subjectTeacher = SubjectTeacher::findOrFail($subjectTeacherId);
            $subjectType = $subjectTeacher->subject->type;

            // Checking if the grade of selected term and grade of subject teacher doesnot match
            $termExists = Term::where("grade_id", $subjectTeacher->section->grade_id)->where("id", $term->id)->exists();

            if (!$termExists) {
                return redirect(route("teacherCasReport.index"))->withErrors(["Error", "Term Doesnot Belong to Subject"]);
            }


            // Calculation for CAS Type not selected
            $teacherId = auth()->id();

            $subjectTeachers = SubjectTeacher::where("teacher_id", $teacherId)->get();

            $terms = Term::whereHas("grade.sections.subjectTeachers.teacher", function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->get()->sortBy("grade.name");

            $students = $subjectTeacher->section->students->where("status", "ACTIVE")->sortBy("roll_number");

            if ($subjectType == "MAIN" || $subjectType == "CREDIT") {
                $casTypes = CasType::whereHas("school.grades.sections.subjectTeachers.teacher", function ($query) use ($teacherId) {
                    return $query->where("id", $teacherId);
                })->get()->sortBy("school.name");

            } elseif ($subjectType == "Reading_Book") {
                $casTypes = ReadingCasType::whereHas("school.grades.sections.subjectTeachers.teacher", function ($query) use ($teacherId) {
                    return $query->where("id", $teacherId);
                })->get()->sortBy("school.name");

            } else {
                $casTypes = EcaCasType::whereHas("school.grades.sections.subjectTeachers.teacher", function ($query) use ($teacherId) {
                    return $query->where("id", $teacherId);
                })->get()->sortBy("school.name");

            }

            if ($isNullCasType) {

                // CAS Type not selected
                if ($isNullTerm || $isNullSubjectTeacher) {
                    return redirect(route("teacherCasReport.index"))->withErrors(["Error", "Term or Subject not selected"]);
                }

                if ($subjectType == "MAIN" || $subjectType == "CREDIT") {
                    $cas = Cas::whereHas(
                        "assignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas(
                            "assignment.term",
                            function ($query) use ($term) {
                                return $query->where("id", $term->id);
                            }
                        )->get()->groupBy(["assignment.casType.name", "assignment"]);
                }elseif($subjectType == "Reading_Book"){
                    $cas = ReadingCas::whereHas(
                        "readingAssignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        })->whereHas(
                            "readingAssignment.term",
                            function ($query) use ($term) {
                                return $query->where("id", $term->id);
                            })->get()->groupBy(["readingAssignment.readingCasType.name", "readingAssignment"]);
                }elseif($subjectType == "ECA"){
                    $cas = EcaCas::whereHas(
                        "ecaAssignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        })->whereHas(
                            "ecaAssignment.term",
                            function ($query) use ($term) {
                                return $query->where("id", $term->id);
                            })->get()->groupBy(["ecaAssignment.ecaCasType.name", "ecaAssignment"]);
                }else{
                    $cas = ClubCas::whereHas(
                        "clubAssignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        })->whereHas(
                            "clubAssignment.term",
                            function ($query) use ($term) {
                                return $query->where("id", $term->id);
                            })->get()->groupBy(["clubAssignment.ecaCasType.name", "clubAssignment"]);
                }
                



                if ($cas->count() == 0) {
                    return redirect(route("teacherCasReport.index"))->withErrors(["Error", "No CAS Marks present"]);
                }

            } else {

                if ($subjectType == "MAIN" || $subjectType == "CREDIT") {
                    $casType = CasType::where("id", $request->casType)->whereHas(
                        "school.grades.sections.subjectTeachers",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas("school.grades", function ($query) use ($term) {
                        return $query->where("id", $term->grade->id);
                    })->firstOrFail();
                    $cas = Cas::whereHas(
                        "assignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas("assignment.term", function ($query) use ($term) {
                        return $query->where("id", $term->id);
                    })->whereHas("assignment.casType", function ($query) use ($casType) {
                        return $query->where("id", $casType->id);
                    })->get()->groupBy(["assignment.casType.name", "assignment"]);
                } elseif ($subjectType == "Reading_Book") {
                    $casType = ReadingCasType::where("id", $request->casType)->whereHas(
                        "school.grades.sections.subjectTeachers",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas("school.grades", function ($query) use ($term) {
                        return $query->where("id", $term->grade->id);
                    })->firstOrFail();

                    $cas = ReadingCas::whereHas(
                        "readingAssignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas("readingAssignment.term", function ($query) use ($term) {
                        return $query->where("id", $term->id);
                    })->whereHas("readingAssignment.readingCasType", function ($query) use ($casType) {
                        return $query->where("id", $casType->id);
                    })->get()->groupBy(["readingAssignment.readingCasType.name", "readingAssignment"]);
                } elseif ($subjectType == "ECA") {
                    $casType = EcaCasType::where("id", $request->casType)->whereHas(
                        "school.grades.sections.subjectTeachers",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas("school.grades", function ($query) use ($term) {
                        return $query->where("id", $term->grade->id);
                    })->firstOrFail();
                    $cas = EcaCas::whereHas(
                        "ecaAssignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas("ecaAssignment.term", function ($query) use ($term) {
                        return $query->where("id", $term->id);
                    })->whereHas("ecaAssignment.ecaCasType", function ($query) use ($casType) {
                        return $query->where("id", $casType->id);
                    })->get()->groupBy(["ecaAssignment.ecaCasType.name", "ecaAssignment"]);
                } else {
                    $casType = EcaCasType::where("id", $request->casType)->whereHas(
                        "school.grades.sections.subjectTeachers",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas("school.grades", function ($query) use ($term) {
                        return $query->where("id", $term->grade->id);
                    })->firstOrFail();
                    $cas = ClubCas::whereHas(
                        "clubAssignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas("clubAssignment.term", function ($query) use ($term) {
                        return $query->where("id", $term->id);
                    })->whereHas("clubAssignment.clubCasType", function ($query) use ($casType) {
                        return $query->where("id", $casType->id);
                    })->get()->groupBy(["clubAssignment.clubCasType.name", "clubAssignment"]);
                }

                if ($cas->count() == 0) {
                    return redirect(route("teacherCasReport.index"))->withErrors(["Error", "No CAS Marks present"]);
                }

            }
            $studentIdsWithMarks = $cas->flatMap(function ($types) {
                return $types->flatMap(function ($assignments) {
                    return $assignments->pluck('student_id');
                });
            })->unique();

            // Filter students based on the collected IDs
            $students = $students->filter(function ($student) use ($studentIdsWithMarks) {
                return $studentIdsWithMarks->contains($student->id);
            });

            return view("teacher.assignment.reportSearch", compact("terms", "subjectTeachers", "cas", "students", "casTypes", "subjectTeacher", "term"));

        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect(route("teacherCasReport.index"))->withErrors(["Error", "Search Filter not valid"]);
        }
    }

    public function populateTerm(Request $request)
    {
        $subjectTeacherId = $request->subjectTeacherId;
        $subjectTeacher = SubjectTeacher::findOrFail($subjectTeacherId);
        $terms = Term::where("grade_id", $subjectTeacher->section->grade_id)->get();
        $termsArray[] = [];
        foreach ($terms as $index => $term) {
            $termsArray[$index]['id'] = $term->id;
            $termsArray[$index]['name'] = $term->name;
            $termsArray[$index]['grade'] = $term->grade->name;
        }
        return json_encode($termsArray);
    }
    public function populateCasType(Request $request)
    {
        $subjectTeacherId = $request->subjectTeacherId;
        $subjectTeacher = SubjectTeacher::findOrFail($subjectTeacherId);
        $subjectType = $subjectTeacher->subject->type;
        $teacherId = $subjectTeacher->section->grade->school->head_of_school_id;
        // $teacherId = auth()->id();
        if ($subjectType == "MAIN" || $subjectType == "CREDIT") {
            $casTypes = CasType::whereHas("school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->get()->sortBy("school.name");
        } elseif ($subjectType == "Reading_Book") {
            $casTypes = ReadingCasType::whereHas("school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->get()->sortBy("school.name");
        } else {
            $casTypes = EcaCasType::whereHas("school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->get()->sortBy("school.name");
        }
        $casTypesArray[] = [];
        foreach ($casTypes as $index => $casType) {
            $casTypesArray[$index]['id'] = $casType->id;
            $casTypesArray[$index]['name'] = $casType->name;
            $casTypesArray[$index]['subjecttype'] = $subjectType;
            $casTypesArray[$index]['subjectteacher'] = $subjectTeacher->teacher->name;
            $casTypesArray[$index]['schoolName'] = $casType->school->name;
        }
        return json_encode($casTypesArray);
    }
    
    public function destroy(int $assignmentId)
    {
        DB::beginTransaction();
        try {

            $teacherId = auth()->id();

            $assignment = Assignment::where("id", $assignmentId)->whereHas("subjectTeacher.teacher", function ($query) use ($teacherId) {
                return $query->where("id", $teacherId);
            })->firstOrFail();

            $cas = $assignment->cas;

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


