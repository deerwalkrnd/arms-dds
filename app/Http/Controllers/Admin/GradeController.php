<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Cas;
use App\Models\ClubAssignment;
use App\Models\ClubCas;
use App\Models\EcaAssignment;
use App\Models\EcaCas;
use App\Models\Exam;
use App\Models\OldAssignment;
use App\Models\OldCasesMarks;
use App\Models\OldCasMark;
use App\Models\OldExamMarks;
use App\Models\OldTable;
use App\Models\ReadingAssignment;
use App\Models\ReadingCas;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Term;
use Illuminate\Http\Request;
use App\Http\Requests\GradeRequest;
use App\Models\Grade;
use App\Models\School;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
    public function index()
    {
        try {

            $grades = Grade::get()->sortBy("name");

            return view("admin.grades.index", compact("grades"));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to retrieve grades.']);
        }
    }

    public function create()
    {
        try {
            $schools = School::all()->sortBy("name");
            return view("admin.grades.create", compact("schools"));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to create grade. ']);
        }
    }

    public function store(GradeRequest $request)
    {
        $name = $request->validated();
        try {

            Grade::create($name);

            return redirect(route('grades.index'))->with('success', 'Grade Created Successfully');
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->back()->withInput()->withErrors(['error' => 'Failed to create grade. ']);
        }
    }

    public function edit($id)
    {
        try {
            $grade = Grade::findOrFail($id);
            $schools = School::all()->sortBy("name");

            return view('admin.grades.edit', compact("grade", "schools"));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(["Grade not found"]);
        }
    }

    public function update(GradeRequest $request, int $id)
    {
        try {
            $grade = Grade::findOrFail($id);

            $data = $request->validated();
            $grade->update($data);

            return redirect(route('grades.index'))->with('success', 'Grade Edited Successfully');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'Failed to create grade. ']);
        }
    }


    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $grade = Grade::findOrFail($id);
            $this->backupAndDeleteExams($grade);
            $this->backupAndDeleteAssignmentCases($grade);
            $this->backupAndDeleteEcaCases($grade);
            $this->backupAndDeleteClubCases($grade);
            $this->backupAndDeleteReadingCases($grade);
            $this->deleteSubjectTeacher($grade);
            $this->detachStudentsFromSectionAndGrade($grade);
            $this->deleteSectionsAndTerms($grade);
            Log::info("Total time taken: " . (microtime(true) - LARAVEL_START) . " seconds");
            DB::commit();

            return redirect(route('grades.index'))->with('success', 'Data associated to grade deleted successfully!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to delete grade data']);
        }
    }

    public function backupAndDeleteExams($grade)
    {
        Exam::with([
            'studentExam.student.section.grade',
            'subjectTeacher.subject.grade.school',
            'subjectTeacher.subject.department',
            'subjectTeacher.teacher',
            'term'
        ])
            ->whereHas('term.grade', fn($q) => $q->where('grade_id', $grade->id))
            ->chunk(1000, function ($exams) use ($grade) {
                foreach ($exams as $exam) {
                    $student = $exam->studentExam->student;
                    $subject = $exam->subjectTeacher->subject;
                    $gradeModel = $subject->grade;
                    $school = $gradeModel->school;

                    OldExamMarks::create([
                        'roll_no' => $exam->studentExam->symbol_no,
                        'emis_no' => $student->emis_no,
                        'exam_school_name' => $school->name,
                        'student_name' => $student->name,
                        'student_father_name' => $student->father_name,
                        'student_father_profession' => $student->fathers_profession,
                        'student_mother_name' => $student->mother_name,
                        'student_mother_profession' => $student->mothers_profession,
                        'student_guardian_name' => $student->guardian_name,
                        'student_guardian_profession' => $student->guardians_profession,
                        'section_name' => $student->section->name ?? '',
                        'grade_name' => $gradeModel->name,
                        'subject_name' => $subject->name,
                        'subject_code' => $subject->subject_code,
                        'subject_teacher_name' => $exam->subjectTeacher->teacher->name ?? '',
                        'term_name' => $exam->term->name,
                        'term_marks' => $exam->mark,
                        'term_full_marks' => $school->theory_weightage,
                        'department_name' => $subject->department->name ?? '',
                        'school_name' => $school->name,
                    ]);
                    $exam->delete();
                }

                // Delete associated student exams
                $studentExamIds = StudentExam::whereHas(
                    'student.section.grade',
                    fn($q) =>
                    $q->where('id', $grade->id)
                )->pluck('id');

                if ($studentExamIds->isNotEmpty()) {
                    Exam::whereIn('student_exam_id', $studentExamIds)->delete();
                    StudentExam::whereIn('id', $studentExamIds)->delete();
                }
            });
    }


    public function backupAndDeleteAssignmentCases($grade)
    {
        $cas = Cas::whereHas("student.section.grade", function ($query) use ($grade) {
            return $query->where("id", $grade->id);
        })
            ->with([
                'student.section.grade.school',
                'assignment.casType',
                'assignment.subjectTeacher.subject.grade.school',
                'assignment.subjectTeacher.teacher',
                'assignment.term',
                'assignment.subjectTeacher.subject.department',
            ])
            ->chunk(1000, function ($casItems) {
                foreach ($casItems as $cas) {
                    $student = $cas->student;
                    $section = $student->section;
                    $grade = $section->grade;
                    $assignment = $cas->assignment;
                    $subjectTeacher = $assignment->subjectTeacher;
                    $subject = $subjectTeacher->subject;
                    $cas_type_name = $assignment->casType->name;
                    $cas_full_marks = $assignment->casType->full_marks;
                    OldCasesMarks::create([
                        "cas_school_name" => $grade->school->name,
                        "cas_type" => "Assignment",
                        "cas_type_name" => $cas_type_name,
                        "cas_marks" => $cas->mark,
                        "cas_full_marks" => $cas_full_marks,
                        "assignment_name" => $assignment->name,
                        "subject_name" => $subject->name,
                        "roll_no" => $student->roll_number,
                        "emis_no" => $student->emis_no,
                        "student_name" => $student->name,
                        "student_father_name" => $student->father_name,
                        "student_father_profession" => $student->fathers_profession,
                        "student_mother_name" => $student->mother_name,
                        "student_mother_profession" => $student->mothers_profession,
                        "student_guardian_name" => $student->guardian_name,
                        "student_guardian_profession" => $student->guardians_profession,
                        "section_name" => $section->name,
                        "grade_name" => $grade->name,
                        "subject_teacher_name" => $subjectTeacher->teacher->name,
                        "term_name" => $assignment->term->name,
                        "department_name" => $subject->department->name,
                    ]);
                }
                // delete all cas
                $cas = Cas::whereHas('student.section.grade', function ($query) use ($grade) {
                    return $query->where('id', $grade->id);
                })->delete();
                Log::info('Deleted cas for grade: ' . $grade->name);
            });
        $assignments = Assignment::whereHas('subjectTeacher.subject.grade', function ($query) use ($grade) {
            return $query->where('id', $grade->id);
        })->delete();
        Log::info('Deleted assignments for grade: ' . $grade->name);
    }

    public function backupAndDeleteEcaCases($grade)
    {
        $ecaCas = EcaCas::whereHas("student.section.grade", function ($query) use ($grade) {
            return $query->where("id", $grade->id);
        })
            ->with([
                'student.section.grade.school',
                'ecaAssignment.ecaCasType',
                'ecaAssignment.subjectTeacher.subject.grade.school',
                'ecaAssignment.subjectTeacher.teacher',
                'ecaAssignment.term',
                'ecaAssignment.subjectTeacher.subject.department',
            ])
            ->chunk(1000, function ($ecaCases) {
                foreach ($ecaCases as $cas) {
                    $student = $cas->student;
                    $section = $student->section;
                    $grade = $section->grade;
                    $assignment = $cas->ecaAssignment;
                    $subjectTeacher = $assignment->subjectTeacher;
                    $subject = $subjectTeacher->subject;
                    $cas_type_name = $assignment->ecaCasType->name;
                    $cas_full_marks = $assignment->ecaCasType->full_marks;

                    OldCasesMarks::create([
                        "cas_school_name" => $grade->school->name,
                        "cas_type" => "ECA Assignment",
                        "cas_type_name" => $cas_type_name,
                        "cas_marks" => $cas->mark,
                        "cas_full_marks" => $cas_full_marks,
                        "assignment_name" => $assignment->name,
                        "subject_name" => $subject->name,
                        "roll_no" => $student->roll_number,
                        "emis_no" => $student->emis_no,
                        "student_name" => $student->name,
                        "student_father_name" => $student->father_name,
                        "student_father_profession" => $student->fathers_profession,
                        "student_mother_name" => $student->mother_name,
                        "student_mother_profession" => $student->mothers_profession,
                        "student_guardian_name" => $student->guardian_name,
                        "student_guardian_profession" => $student->guardians_profession,
                        "section_name" => $section->name,
                        "grade_name" => $grade->name,
                        "subject_teacher_name" => $subjectTeacher->teacher->name,
                        "term_name" => $assignment->term->name,
                        "department_name" => $subject->department->name,
                    ]);
                    $cas->delete();
                }
            });
        $ecaAssignments = EcaAssignment::whereHas('term.grade', function ($query) use ($grade) {
            return $query->where('id', $grade->id);
        })->get();

        if ($ecaAssignments->count() > 0) {

            foreach ($ecaAssignments as $assignment) {
                $assignment->ecaCas()->delete();
                $assignment->delete();
            }
        }

        Log::info('Deleted ECA assignments for grade: ' . $grade->name);
        Log::info('Deleted ECA cas for grade: ' . $grade->name);
    }

    public function backupAndDeleteClubCases($grade)
    {
        Log::info('Backing up and deleting club cases for grade: ' . $grade->name);
        $clubCas = ClubCas::whereHas("student.section.grade", function ($query) use ($grade) {
            return $query->where("id", $grade->id);
        })
            ->with([
                'student.section.grade.school',
                'clubAssignment.ecaCasType',
                'clubAssignment.subjectTeacher.subject.grade.school',
                'clubAssignment.subjectTeacher.teacher',
                'clubAssignment.term',
                'clubAssignment.subjectTeacher.subject.department',
            ])
            ->chunk(1000, function ($clubCases) {
                foreach ($clubCases as $cas) {
                    $student = $cas->student;
                    $section = $student->section;
                    $grade = $section->grade;
                    $assignment = $cas->clubAssignment;
                    $subjectTeacher = $assignment->subjectTeacher;
                    $subject = $subjectTeacher->subject;
                    $cas_type_name = $assignment->ecaCasType->name;
                    $cas_full_marks = $assignment->ecaCasType->full_marks;

                    OldCasesMarks::create([
                        "cas_school_name" => $grade->school->name,
                        "cas_type" => "Club Assignment",
                        "cas_type_name" => $cas_type_name,
                        "cas_marks" => $cas->mark,
                        "cas_full_marks" => $cas_full_marks,
                        "assignment_name" => $assignment->name,
                        "subject_name" => $subject->name,
                        "roll_no" => $student->roll_number,
                        "emis_no" => $student->emis_no,
                        "student_name" => $student->name,
                        "student_father_name" => $student->father_name,
                        "student_father_profession" => $student->fathers_profession,
                        "student_mother_name" => $student->mother_name,
                        "student_mother_profession" => $student->mothers_profession,
                        "student_guardian_name" => $student->guardian_name,
                        "student_guardian_profession" => $student->guardians_profession,
                        "section_name" => $section->name,
                        "grade_name" => $grade->name,
                        "subject_teacher_name" => $subjectTeacher->teacher->name,
                        "term_name" => $assignment->term->name,
                        "department_name" => $subject->department->name,
                    ]);
                    $cas->delete();
                }
            });
        $clubAssignments = ClubAssignment::whereHas('term.grade', function ($query) use ($grade) {
            return $query->where('id', $grade->id);
        })->get();
        if ($clubAssignments->count() > 0) {
            foreach ($clubAssignments as $assignment) {
                $assignment->clubCas()->delete();
                $assignment->delete();
            }
        }
        Log::info('Finished deleting club cases for grade: ' . $grade->name);
    }
    public function backupAndDeleteReadingCases($grade)
    {
        $readingCas = ReadingCas::whereHas("student.section.grade", function ($query) use ($grade) {
            return $query->where("id", $grade->id);
        })
            ->with([
                'student.section.grade.school',
                'readingAssignment.readingCasType',
                'readingAssignment.subjectTeacher.subject.grade.school',
                'readingAssignment.subjectTeacher.teacher',
                'readingAssignment.term',
                'readingAssignment.subjectTeacher.subject.department',
            ])
            ->chunk(1000, function ($readingCases) {
                foreach ($readingCases as $cas) {
                    $student = $cas->student;
                    $section = $student->section;
                    $grade = $section->grade;
                    $assignment = $cas->readingAssignment;
                    $subjectTeacher = $assignment->subjectTeacher;
                    $subject = $subjectTeacher->subject;
                    $cas_type_name = $assignment->readingCasType->name;
                    $cas_full_marks = $assignment->readingCasType->full_marks;

                    OldCasesMarks::create([
                        "cas_school_name" => $grade->school->name,
                        "cas_type" => "Reading Assignment",
                        "cas_type_name" => $cas_type_name,
                        "cas_marks" => $cas->mark,
                        "cas_full_marks" => $cas_full_marks,
                        "assignment_name" => $assignment->name,
                        "subject_name" => $subject->name,
                        "roll_no" => $student->roll_number,
                        "emis_no" => $student->emis_no,
                        "student_name" => $student->name,
                        "student_father_name" => $student->father_name,
                        "student_father_profession" => $student->fathers_profession,
                        "student_mother_name" => $student->mother_name,
                        "student_mother_profession" => $student->mothers_profession,
                        "student_guardian_name" => $student->guardian_name,
                        "student_guardian_profession" => $student->guardians_profession,
                        "section_name" => $section->name,
                        "grade_name" => $grade->name,
                        "subject_teacher_name" => $subjectTeacher->teacher->name,
                        "term_name" => $assignment->term->name,
                        "department_name" => $subject->department->name,
                    ]);
                    $cas->delete();
                }
            });
        $readingAssignments = ReadingAssignment::whereHas('term.grade', function ($query) use ($grade) {
            return $query->where('id', $grade->id);
        })->get();
        if ($readingAssignments->count() > 0) {
            foreach ($readingAssignments as $assignment) {
                $assignment->readingCas()->delete();
                $assignment->delete();
            }
        }

        Log::info('Deleted reading assignments for grade: ' . $grade->name);
        Log::info('Deleted reading cas for grade: ' . $grade->name);
        Log::info('Finished deleting reading cases for grade: ' . $grade->name);
    }

    public function deleteSubjectTeacher($grade)
    {
        $studentTeachers = SubjectTeacher::whereHas('subject.grade', function ($query) use ($grade) {
            return $query->where('id', $grade->id);
        })->get();
        if ($studentTeachers->count() > 0) {
            foreach ($studentTeachers as $studentTeacher) {
                $studentTeacher->delete();
            }
        }
        Log::info('Deleting subject teachers for grade: ' . $grade->name);
    }

    public function detachStudentsFromSectionAndGrade($grade)
    {
        $students = Student::whereHas('section.grade', fn($q) => $q->where('id', $grade->id))->get();

        foreach ($students as $student) {
            $student->update(['section_id' => null]);
            $student->subject()->detach();
        }
        $sections = Section::whereHas('grade', fn($q) => $q->where('id', $grade->id))->get();
        foreach ($sections as $section) {
            Log::info('Detaching section: ' . $section->name);
            $section->students()->delete();
        }
    }

    public function deleteSectionsAndTerms($grade)
    {
        $sections = Section::whereHas('grade', fn($q) => $q->where('id', $grade->id))->get();

        foreach ($sections as $section) {
            Log::info('Deleting section: ' . $section->name);
            $section->delete();
        }

        $terms = Term::whereHas('grade', fn($q) => $q->where('id', $grade->id))->get();

        foreach ($terms as $term) {
            Log::info('Deleting term: ' . $term->name);
            $term->delete();
        }
    }
}
