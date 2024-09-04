<?php

namespace App\Http\Controllers\Hos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\ClubAssignment;
use App\Models\ClubCas;
use App\Models\EcaAssignment;
use App\Models\EcaCas;
use App\Models\EcaCasType;
use App\Models\ReadingAssignment;
use App\Models\ReadingCas;
use App\Models\ReadingCasType;
use Exception;
use App\Models\Cas;
use App\Models\CasType;
use App\Http\Requests\TeacherCasStoreRequest;
use App\Models\SubjectTeacher;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignmentController extends Controller
{
    //

    public function index()
    {
        try {
            $teacher_id = auth()->id();

            $assignments = Assignment::whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacher_id) {
                $query->where("head_of_school_id", $teacher_id);
            })->with(['subjectTeacher', 'casType'])->get();
            $ecaAssignments = EcaAssignment::whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacher_id) {
                $query->where("head_of_school_id", $teacher_id);
            })->with(['subjectTeacher', 'ecaCasType'])->get();
            $clubAssignments = ClubAssignment::whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacher_id) {
                $query->where("head_of_school_id", $teacher_id);
            })->with(['subjectTeacher', 'ecaCasType'])->get();
            $readingAssignments = ReadingAssignment::whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacher_id) {
                $query->where("head_of_school_id", $teacher_id);
            })->with(['subjectTeacher', 'readingCasType'])->get();
            // Merge all assignments into a single collection
            $allAssignments = collect()
                ->merge($assignments)
                ->merge($ecaAssignments)
                ->merge($clubAssignments)
                ->merge($readingAssignments);

            // Sort the collection by 'created_at' in descending order
            $sortedAssignments = $allAssignments->sortByDesc('updated_at');
            return view("hos.assignments.index", compact("assignments", "ecaAssignments", "clubAssignments", "readingAssignments",'sortedAssignments'));
        } catch (Exception $e) {
            return redirect()->back()->withErrors(["Error", "Failed to retrieve assignments"]);
        }
    }

    public function view(int $assignmentId)
    {
        try {
            $teacherId = auth()->id();

            $assignment = Assignment::where("id", $assignmentId)->whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacherId) {
                return $query->where('head_of_school_id', $teacherId);
            })->firstOrFail();


            $cas = Cas::with('student')->where("assignment_id", $assignment->id)->whereHas('student', function ($query) {
                return $query->where("status", "ACTIVE");
            })->get()->sortBy("student.roll_no");

            return view("hos.assignments.view", compact("assignment", "cas"));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(["Error", "Assignment not found"]);
        }
    }

    public function edit(int $assignmentId)
    {
        try {
            $teacherId = auth()->id();

            // Verify if the user is authenticated to edit this assignment
            $assignment = Assignment::where('id', $assignmentId)->whereHas('subjectTeacher.subject.grade.school', function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();


            $subjectTeacher = $assignment->subjectTeacher;

            // Retrieve the castypes from the given grade request
            $casTypes = CasType::whereHas("school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->where("school_id", $subjectTeacher->section->grade->school_id)->get();

            $cas = Cas::with('student')->where("assignment_id", $assignment->id)->whereHas('student', function ($query) {
                return $query->where("status", "ACTIVE");
            })->get()->sortBy("student.roll_no");


            return view("hos.assignments.edit", compact('assignment', 'casTypes', 'cas'));
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

            $assignment = Assignment::where("id", $assignmentId)->whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
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

                $cas = Cas::where("student_id", $studentId)->whereHas('student', function ($query) {
                    return $query->where("status", "ACTIVE");
                })->where("assignment_id", $assignment->id)->firstOrFail();

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
    public function updateAndStore(int $assignmentId, TeacherCasStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();


        try {
            $teacherId = auth()->id();

            $assignment = Assignment::where("id", $assignmentId)->whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
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
                "submitted" => '1',
            ]);

            $casType = CasType::findOrFail($data["cas_type"]);


            foreach ($data['students'] as $id => $studentId) {

                $cas = Cas::where("student_id", $studentId)->whereHas('student', function ($query) {
                    return $query->where("status", "ACTIVE");
                })->where("assignment_id", $assignment->id)->firstOrFail();

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

    public function reportIndex()
    {
        try {
            $teacherId = auth()->id();



            $subjectTeachers = SubjectTeacher::whereHas("subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->get()->sortBy("subject.name");

            $terms = Term::whereHas("grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->get()->sortBy("grade.name");

            $casLatest = Cas::whereHas("assignment.subjectTeacher.subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->whereHas("assignment", function ($query) {
                return $query->where("submitted", "1");
            })->latest("updated_at")->firstOrFail();

            $latestSubjectTeacher = $casLatest->assignment->subjectTeacher;

            $casTypes = CasType::whereHas("school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->get()->sortBy("school.name");

            $currentDate = Carbon::now();


            $currentTerm = $casLatest->assignment->term;


            $cas = Cas::whereHas("assignment.subjectTeacher", function ($query) use ($latestSubjectTeacher) {
                return $query->where("id", $latestSubjectTeacher->id);
            })->whereHas("assignment.term", function ($query) use ($currentTerm) {
                return $query->where("id", $currentTerm->id);
            })->get()->groupBy(["assignment.casType.name", "assignment"]);


            $students = $latestSubjectTeacher->section->students->where("status", "ACTIVE")->sortBy("roll_number");





            return view("hos.assignments.reportIndex", compact("terms", "subjectTeachers", "casLatest", "latestSubjectTeacher", "cas", "students", "casTypes"));

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
                return redirect(route("hosCasReport.index"))->withErrors(["Error", "Please select at least first two filters"]);
            }

            $termId = $request->term;
            $subjectTeacherId = $request->subjectTeacher;

            $term = Term::findOrFail($termId);
            $subjectTeacher = SubjectTeacher::findOrFail($subjectTeacherId);
            $subjectType = $subjectTeacher->subject->type;

            // Checking if the grade of selected term and grade of subject teacher doesnot match
            $termExists = Term::where("grade_id", $subjectTeacher->section->grade_id)->where("id", $term->id)->exists();

            if (!$termExists) {
                return redirect(route("hosCasReport.index"))->withErrors(["Error", "Term Doesnot Belong to Subject"]);
            }


            // Calculation for CAS Type not selected
            $teacherId = auth()->id();

            $subjectTeachers = SubjectTeacher::whereHas("subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->get()->sortBy("subject.name");

            $terms = Term::whereHas("grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->get()->sortBy("grade.name");

            $students = $subjectTeacher->section->students->where("status", "ACTIVE")->sortBy("roll_number");


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

            if ($isNullCasType) {

                // CAS Type not selected
                if ($isNullTerm || $isNullSubjectTeacher) {
                    return redirect(route("hosCasReport.index"))->withErrors(["Error", "Term or Subject not selected"]);
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
                } elseif ($subjectType == "Reading_Book") {
                    $cas = ReadingCas::whereHas(
                        "readingAssignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas(
                            "readingAssignment.term",
                            function ($query) use ($term) {
                                return $query->where("id", $term->id);
                            }
                        )->get()->groupBy(["readingAssignment.readingCasType.name", "readingAssignment"]);
                } elseif ($subjectType == "ECA") {
                    $cas = EcaCas::whereHas(
                        "ecaAssignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas(
                            "ecaAssignment.term",
                            function ($query) use ($term) {
                                return $query->where("id", $term->id);
                            }
                        )->get()->groupBy(["ecaAssignment.ecaCasType.name", "ecaAssignment"]);
                } else {
                    $cas = ClubCas::whereHas(
                        "clubAssignment.subjectTeacher",
                        function ($query) use ($subjectTeacher) {
                            return $query->where("id", $subjectTeacher->id);
                        }
                    )->whereHas(
                            "clubAssignment.term",
                            function ($query) use ($term) {
                                return $query->where("id", $term->id);
                            }
                        )->get()->groupBy(["clubAssignment.ecaCasType.name", "clubAssignment"]);
                }
                if ($cas->count() == 0) {
                    return redirect(route("hosCasReport.index"))->withErrors(["Error", "No CAS Marks present"]);
                }
            } else {

                //put filter here
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
                    return redirect(route("hosCasReport.index"))->withErrors(["Error", "No CAS Marks present"]);
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
            return view("hos.assignments.reportSearch", compact("terms", "subjectTeachers", "cas", "students", "casTypes", "subjectTeacher", "term"));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect(route("hosCasReport.index"))->withErrors(["Error", "Search Filter not valid: "]);
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
        $teacherId = auth()->id();
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

            $assignment = Assignment::where("id", $assignmentId)->whereHas("subjectTeacher.subject.grade.school", function ($query) use ($teacherId) {
                return $query->where("head_of_school_id", $teacherId);
            })->firstOrFail();

            $cas = $assignment->cas;
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
