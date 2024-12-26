<?php

namespace App\Console\Commands;


use App\Helpers\GenerateMarksheetForGradeElevenToTwelve;
use App\Helpers\GenerateMarksheetForGradeFiveToEight;
use App\Helpers\GenerateMarksheetForGradeFour;
use App\Helpers\GenerateMarksheetForGradeNineAndTen;
use App\Helpers\GenerateMarksheetForGradeOneToThree;
use App\Helpers\GenerateMarksheetForGradePreSchool;x
use App\Mail\ResultMail;

use App\Models\Cas;
use App\Models\ClubCas;
use App\Models\EcaCas;
use App\Models\Exam;
use App\Models\ReadingCas;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use setasign\Fpdi\Fpdi;
use App\Helpers\MarkSortAndMerge;


class GenerateMarksheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:marksheets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Marksheets of the term based on the calendar event';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // first get all the cas marks of the
        // current term based on the currWent date
        $currentTerms = Term::whereDate('result_date', Carbon::now()->addDays(2))

            ->where('is_result_generated', 0)

            ->get();

        if ($currentTerms->isEmpty()) {
            Log::info("Searched for the term end dates in three days. Found None.");
        }
        foreach ($currentTerms as $currentTerm) {

            $subjects = Subject::where('grade_id', $currentTerm->grade_id)
                ->get();

            $students = Student::with('section.grade')->where("status", "ACTIVE")
                ->whereHas('section.grade', function (Builder $query) use ($currentTerm) {
                    $query->where('id', $currentTerm->grade_id);
                })
                ->get();

            foreach ($students as $student) {
                $casGradePointCollectionByStudent = collect([]);
                $casGradeCollectionByStudent = collect([]);
                $examGradePointCollectionByStudent = collect([]);
                $examGradeCollectionByStudent = collect([]);
                $totalGPACollectionConversion = collect([]);
                $totalMarksCollectionByStudent = collect([]);

                foreach ($subjects as $subject) {

                    // cas marks
                    //maybe we should put the filter for subjecttype here
                    if ($subject->type == "MAIN" || $subject->type == "CREDIT") {
                        $cumulativeCasMarkBySubject = Cas::calculateTotalMarksPerSubjectTeacher($subject->id, $student->id, $currentTerm->start_date, $currentTerm->end_date);
                        
                        $gradePointBySubjectForCas = Cas::gradePoint($subject, $cumulativeCasMarkBySubject);
                        $gradeConversionForCas = Cas::gradeConversion($subject, $cumulativeCasMarkBySubject);
                    } 
                    // elseif ($subject->type == "ECA") {
                    //     $cumulativeCasMarkBySubject = EcaCas::calculateTotalMarksPerSubjectTeacher($subject->id, $student->id, $currentTerm->start_date, $currentTerm->end_date);
                    //     $gradePointBySubjectForCas = EcaCas::gradePoint($subject, $cumulativeCasMarkBySubject);
                    //     $gradeConversionForCas = EcaCas::gradeConversion($subject, $cumulativeCasMarkBySubject);
                    // } elseif ($subject->type == "Reading_Book") {
                    //     $cumulativeCasMarkBySubject = ReadingCas::calculateTotalMarksPerSubjectTeacher($subject->id, $student->id, $currentTerm->start_date, $currentTerm->end_date);
                    //     $gradePointBySubjectForCas = ReadingCas::gradePoint($subject, $cumulativeCasMarkBySubject);
                    //     $gradeConversionForCas = ReadingCas::gradeConversion($subject, $cumulativeCasMarkBySubject);
                    // } else {
                    //     $clubs = $student->subject()->get();
                    //     foreach($clubs as $club){
                    //         if ($club->id == $subject->id) {
                    //             $cumulativeCasMarkBySubject = ClubCas::calculateTotalMarksPerSubjectTeacher($subject->id, $student->id, $currentTerm->start_date, $currentTerm->end_date);
                    //             $gradePointBySubjectForCas = ClubCas::gradePoint($subject, $cumulativeCasMarkBySubject);
                    //             $gradeConversionForCas = ClubCas::gradeConversion($subject, $cumulativeCasMarkBySubject);
                    //         }
                    //     }
                    // }





                    // ----------------------------------------------------------------


                    // exam marks
                    // $examMarksBySubject = Exam::with(['studentExam.student', 'subjectTeacher.subject'])
                    //     ->whereHas('studentExam.student', function (Builder $query) use ($student) {
                    //         $query->where('id', $student->id);
                    //     })
                    //     ->whereHas('subjectTeacher.subject', function (Builder $query) use ($subject) {
                    //         $query->where('id', $subject->id);
                    //     })
                    //     ->whereDate('created_at', '>=', $currentTerm->end_date)
                    //     ->where('created_at', '<=', $currentTerm->result_date)
                    //     ->first();

                   
                        $examMarksBySubject = Exam::with(['studentExam.student', 'subjectTeacher.subject'])
                            ->whereHas('studentExam.student', function (Builder $query) use ($student) {
                                $query->where('id', $student->id);
                            })
                            ->whereHas('subjectTeacher.subject', function (Builder $query) use ($subject) {
                                $query->where('id', $subject->id);
                            })
                            ->where("term_id", $currentTerm->id)
                            ->first();


                        if (!$examMarksBySubject) {

                            Log::info("Exam Marks doesnot exist for " . $student->name . " of Subject " . $subject->name . "    Automatically set to 0");

                            $examMarksBySubject = 0;
                        }



                    $totalPointMarks = $this->totalCasExamMarks($cumulativeCasMarkBySubject, $examMarksBySubject->mark ?? 0);

                    $totalMarks = $this->totalSum($cumulativeCasMarkBySubject, $examMarksBySubject->mark ?? 0);

                    $gradePointForExamMarks = Exam::gradePoint($subject, $examMarksBySubject->mark ?? 0);

                    $gradeConversionForExamMarks = Exam::gradeConversion($subject, $examMarksBySubject->mark ?? 0);


                    // put into the collection
                    $totalGPACollectionConversion->push(['average_point' => $totalPointMarks, 'type' => $subject->type, 'name' => $subject->name]);

                    $totalMarksCollectionByStudent->push(['average_marks' => $totalMarks, 'type' => $subject->type, 'name' => $subject->name]);

                    if ($subject->type == "MAIN" || $subject->type == "CREDIT") {
                    $casGradePointCollectionByStudent->push(['cas_mark' => $gradePointBySubjectForCas, 'type' => $subject->type, 'name' => $subject->name, 'credit_hour' => $subject->credit_hr]);
                    $casGradePointCollectionByStudent->push(['cas_mark' => $gradePointBySubjectForCas, 'type' => $subject->type, 'name' => $subject->name, 'credit_hour' => $subject->credit_hr]);

                        $casGradePointCollectionByStudent->push(['cas_mark' => $gradePointBySubjectForCas, 'type' => $subject->type, 'name' => $subject->name, 'credit_hour' => $subject->credit_hr]);

                        $casGradeCollectionByStudent->push(['cas_grade' => $gradeConversionForCas, 'type' => $subject->type, 'name' => $subject->name]);
                    } 
                    // elseif ($subject->type == "ECA" || $subject->type == "Reading_Book") {
                    //     $casGradePointCollectionByStudent->push(['cas_mark' => $gradePointBySubjectForCas, 'type' => "ECA", 'name' => $subject->name, 'credit_hour' => $subject->credit_hr]);
                    //     $casGradeCollectionByStudent->push(['cas_grade' => $gradeConversionForCas, 'type' => "ECA", 'name' => $subject->name]);
                    // } else {
                    //     $clubs = $student->subject()->get();
                    //     foreach($clubs as $club){
                    //         if ($club->id == $subject->id) {
                    //             $casGradePointCollectionByStudent->push(['cas_mark' => $gradePointBySubjectForCas, 'type' => "ECA", 'name' => $subject->name, 'credit_hour' => $subject->credit_hr]);
                    //             $casGradeCollectionByStudent->push(['cas_grade' => $gradeConversionForCas, 'type' => "ECA", 'name' => $subject->name]);
                    //         }
                    //     }
                    // }


                    $examGradePointCollectionByStudent->push(['exam_mark' => $gradePointForExamMarks, 'type' => $subject->type, 'name' => $subject->name]);

                    $examGradeCollectionByStudent->push(['exam_grade' => $gradeConversionForExamMarks, 'type' => $subject->type, 'name' => $subject->name]);

                }

                
                // now order the marks according to the order of result
                if ($currentTerm->grade->name == "Nursery" || $currentTerm->grade->name == "Prep I" || $currentTerm->grade->name == "Prep II") {

                    $sortOrderForMainSubjects = ['Nepali', 'English', 'Mathematics', 'Science', 'Hamro Serophero'];

                    $sortOrderForCreditSubjects = ['Sanskrit', 'Maths Drill'];
                    // put every subject
                    $sortOrderForEcaSubjects = ['English Reading Book', 'Nepali Reading Book', 'Dance', 'Drama', 'Music', 'Visual Art', 'Basketball', 'Futsal', 'Badminton', 'Chess', 'Taekwondo', 'Table Tennis', 'Yog'];

                    $marks = MarkSortAndMerge::sortAndMerge($casGradePointCollectionByStudent, $casGradeCollectionByStudent, $examGradePointCollectionByStudent, $examGradeCollectionByStudent, $sortOrderForMainSubjects, $sortOrderForCreditSubjects, $sortOrderForEcaSubjects, $totalGPACollectionConversion, $totalMarksCollectionByStudent);

                   
                    dump("Generating marksheet of ". $student->name);
                    Log::error($marks);
                    GenerateMarksheetForGradePreSchool::generate($student, $currentTerm, $marks);

                    //now send these marks to pdf generator
                } elseif ($currentTerm->grade->name >= 1 && $currentTerm->grade->name <= 3) {

                    $sortOrderForMainSubjects = ['Nepali', 'English', 'Mathematics', 'Science, Health and Physical Education', 'Hamro Serophero'];

                    $sortOrderForCreditSubjects = ['Sanskrit', 'Coding'];
                    // put every subject
                    $sortOrderForEcaSubjects = ['English Reading Book', 'Nepali Reading Book', 'Dance', 'Drama', 'Music', 'Visual Art', 'Basketball', 'Futsal', 'Badminton', 'Chess', 'Taekwondo', 'Table Tennis', 'Yog'];

                    $marks = MarkSortAndMerge::sortAndMerge($casGradePointCollectionByStudent, $casGradeCollectionByStudent, $examGradePointCollectionByStudent, $examGradeCollectionByStudent, $sortOrderForMainSubjects, $sortOrderForCreditSubjects, $sortOrderForEcaSubjects, $totalGPACollectionConversion, $totalMarksCollectionByStudent);

                   
                    dump("Generating marksheet of ". $student->name);
                    Log::error($marks);
                    GenerateMarksheetForGradeOneToThree::generate($student, $currentTerm, $marks);

                    //now send these marks to pdf generator
                } elseif ($currentTerm->grade->name == 4) {

                    $sortOrderForMainSubjects = ['Nepali', 'English', 'Mathematics', 'Science & Technology', 'Samajik Adhyayan', 'HPCA', 'Local Curriculum'];

                    $sortOrderForCreditSubjects = ['Sanskrit', 'Coding'];

                    // $club = $student->subject()->get();
                    $sortOrderForEcaSubjects = ['English Reading Book', 'Nepali Reading Book'];

                    $marks = MarkSortAndMerge::sortAndMerge($casGradePointCollectionByStudent, $casGradeCollectionByStudent, $examGradePointCollectionByStudent, $examGradeCollectionByStudent, $sortOrderForMainSubjects, $sortOrderForCreditSubjects, $sortOrderForEcaSubjects, $totalGPACollectionConversion, $totalMarksCollectionByStudent);
                    dump("Generating marksheet of ". $student->name);
                    GenerateMarksheetForGradeFour::generate($student, $currentTerm, $marks);
                    //now send these marks to pdf generator
                } elseif ($currentTerm->grade->name >= 5 && $currentTerm->grade->name <= 8) {

                    $sortOrderForMainSubjects = ['Nepali', 'English', 'Mathematics', 'Science & Technology', 'Samajik Adhyayan', 'HPCA', 'Local Curriculum'];

                    $sortOrderForCreditSubjects = ['Sanskrit', 'Coding'];

                    // $club = $student->subject()->get();
                    $sortOrderForEcaSubjects = ['English Reading Book', 'Nepali Reading Book'];

                    $marks = MarkSortAndMerge::sortAndMerge($casGradePointCollectionByStudent, $casGradeCollectionByStudent, $examGradePointCollectionByStudent, $examGradeCollectionByStudent, $sortOrderForMainSubjects, $sortOrderForCreditSubjects, $sortOrderForEcaSubjects, $totalGPACollectionConversion, $totalMarksCollectionByStudent);
                    dump("Generating marksheet of ". $student->name);
                    GenerateMarksheetForGradeFiveToEight::generate($student, $currentTerm, $marks);
                    //now send these marks to pdf generator
                } elseif ($currentTerm->grade->name >= 9 && $currentTerm->grade->name <= 10) {

                    $sortOrderForMainSubjects = ['Nepali', 'English', 'C. Mathematics', 'Science & Technology', 'Samajik Adhyayan', 'Additional Mathematics', 'Computer Science'];

                    $sortOrderForCreditSubjects = ['Coding'];
                    // $club = $student->subject()->first();
                    $sortOrderForEcaSubjects = ['English Reading Book', 'Nepali Reading Book'];

                    $marks = MarkSortAndMerge::sortAndMerge($casGradePointCollectionByStudent, $casGradeCollectionByStudent, $examGradePointCollectionByStudent, $examGradeCollectionByStudent, $sortOrderForMainSubjects, $sortOrderForCreditSubjects, $sortOrderForEcaSubjects, $totalGPACollectionConversion, $totalMarksCollectionByStudent);
                    dump("Generating marksheet of ". $student->name);
                    GenerateMarksheetForGradeNineAndTen::generate($student, $currentTerm, $marks);
                    //now send these marks to pdf generator
                } elseif($currentTerm->grade->name >=11 && $currentTerm->grade->name <= 12) {
                    $sortOrderForMainSubjects = ['Nepali', 'English', 'Physics', 'Chemistry', 'Mathematics', 'Biology', 'Computer Science'];
                    $sortOrderForCreditSubjects = ['Python Programming, Java Programming'];
                    // $club = $student->subject()->first();
                    $sortOrderForEcaSubjects = ['English Reading Book', 'Nepali Reading Book'];
                    //filter
                    $marks = MarkSortAndMerge::sortAndMerge($casGradePointCollectionByStudent, $casGradeCollectionByStudent, $examGradePointCollectionByStudent, $examGradeCollectionByStudent, $sortOrderForMainSubjects, $sortOrderForCreditSubjects, $sortOrderForEcaSubjects, $totalGPACollectionConversion, $totalMarksCollectionByStudent);
                    dump("Generating marksheet of ". $student->name);
                    GenerateMarksheetForGradeElevenToTwelve::generate($student, $currentTerm, $marks);
                }
            }
            // set the current term's is_result_generated to 1
            $currentTerm->is_result_generated = 1;
            $currentTerm->save();
            dump("Result Generated for Grade " . $currentTerm->grade->name . " for " . $currentTerm->name . " term");
            Log::info("Result Generated for Grade " . $currentTerm->grade->name . " for " . $currentTerm->name . " term");

        }
    }

    public function totalCasExamMarks($casMarks, $examMarks)
    {
        $sum = (int) round($casMarks + $examMarks, 0);

        $gradeBoundaries = [
            90 => "A+",
            80 => "A",
            70 => "B+",
            60 => "B",
            50 => "C+",
            40 => "C",
            35 => "D",
        ];


        foreach ($gradeBoundaries as $boundary => $grade) {
            if ($sum >= $boundary) {
                return $grade;
            }
        }

        return "NG";
    }

    public function totalSum($casMarks, $examMarks)
    {
        $sum = round($casMarks + $examMarks, 0);

        $gradeBoundaries = [
            90 => 4,
            80 => 3.6,
            70 => 3.2,
            60 => 2.8,
            50 => 2.4,
            40 => 2,
            35 => 1.6,
        ];
        foreach ($gradeBoundaries as $boundary => $grade) {
            if ($sum >= $boundary) {
                return $grade;
            }
        }
        return "NG";

    }
}
