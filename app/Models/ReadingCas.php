<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingCas extends Model
{
    use HasFactory;
    protected $fillable = [
        "student_id",
        "readingAssignment_id",
        "mark",
        "remarks"
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, "student_id");
    }

    public function readingAssignment(): BelongsTo
    {
        return $this->belongsTo(ReadingAssignment::class, "readingAssignment_id");
    }

    public static function calculateTotalMarksPerSubjectTeacher($subjectId, $studentId, $startDate, $endDate)
    {

        $student = Student::where("id", $studentId)->firstOrFail();
        $subject = Subject::findOrFail($subjectId);

        $school = $subject->grade->school;
        $cas = $school->cas_weightage ?? null;



        // Fetch Term to retrieve the CAS Marks based on start date and end date
        $term = Term::where("grade_id", $student->section->grade_id)->where("start_date", "<=", $startDate)->where("end_date", ">=", $endDate)->first();

        if ($term == null) {
            return "No Term Present between the Start Date and End Date";
        }

        if ($cas === null) {
            return "No CAS weightage found for the school";
        }

        $conversion = $cas / 100;

        // $casRecords = Cas::whereHas("assignment.subjectTeacher.subject", function ($query) use ($subjectId) {
        //     $query->where("subject_id", $subjectId);
        // })
        //     ->where('student_id', $studentId)
        //     ->whereDate('created_at', '>=', $startDate)
        //     ->whereDate('created_at', '<=', $endDate)
        //     ->with('assignment.casType')
        //     ->get();


        // New Correction check the dates from term instead of created_at
        // put filter here
        $casRecords = ReadingCas::whereHas("readingAssignment.subjectTeacher.subject", function ($query) use ($subjectId) {
            $query->where("subject_id", $subjectId);
        })
            ->where('student_id', $studentId)
            ->whereHas("readingAssignment", function ($query) use ($term) {
                return $query->where("term_id", $term->id);
            })
            ->get()->sortBy("readingAssignment.reading_cas_type_id");
        // -----------------------------------------------------

        $casTypeStats = [];

        foreach ($casRecords as $cas) {
            // put filter here
            $casTypeId = $cas->readingAssignment->readingCasType->id;
            // ----------------------------------------------
            // Add to existing or create one if it doesnot exists
            $casTypeStats[$casTypeId]['totalMarks'] = isset($casTypeStats[$casTypeId]['totalMarks']) ?
                $casTypeStats[$casTypeId]['totalMarks'] + $cas->mark :
                $cas->mark;

            // Keep track of total number of assignments of the given cas type, increment if exists, create if none
            $casTypeStats[$casTypeId]['count'] = isset($casTypeStats[$casTypeId]['count']) ?
                $casTypeStats[$casTypeId]['count'] + 1 :
                1;
            $casTypeStats[$casTypeId]['name'] = $cas->readingAssignment->readingCasType->name;
        }


        $averageMarksPerCasType = [];

        // Add Full Marks Per Cas Type, just in case we need to convert the marks to ratio of 100 if assignment of any CAS Type is missing
        $fullMarksPerCasType = [];


        foreach ($casTypeStats as $casTypeId => $stats) {

            $fullMarksPerCasType[$casTypeId] = ReadingCasType::findOrFail($casTypeId)->full_marks;

            $averageMarksPerCasType[$casTypeId] = $stats['totalMarks'] / $stats['count'];
        }


        $fullMarks = array_sum($fullMarksPerCasType);
        $totalMarks = array_sum($averageMarksPerCasType);


        // Convert to the ratio of 100
        if ($fullMarks == 0) {
            $averageTotalMarks = 0;
        } else {
            $averageTotalMarks = ($totalMarks / $fullMarks) * 50;
        }

        return round($averageTotalMarks, 2);
    }
    public static function gradeConversion(Subject $subject, float $mark)
    {
        $school = $subject->grade->school;
        $cas = $school->cas_weightage ?? null;

        if ($cas === null) {
            return "No CAS weightage found for the school";
        }


        if ($subject->type == "ECA" || $subject->type == "Club_ES" || $subject->type == "Club_1_MS" || $subject->type == "Club_2_MS" ||  $subject->type == "Club_1_HS" || $subject->type == "Club_2_HS" || $subject->type == "Reading_Book") {
            $gradeBoundaries = [
                40 => "Exceptional",
                30 => "More Than Satisfactory",
                25 => "Satisfactory",
                20 => "Need Improvement",
                0 => "Not Acceptable",
            ];
        } 

        foreach ($gradeBoundaries as $boundary => $grade) {
            if ($mark > $boundary) {
                return $grade;
            }
        }
        if ($subject->type == "MAIN" || $subject->type == "CREDIT" ) {
            return "NG";
        }

        return "Not Acceptable";
    }

    public static function gradePoint(Subject $subject, float $mark)
    {
        $school = $subject->grade->school;
        $cas = $school->cas_weightage ?? null;

        if ($cas === null) {
            return "No CAS weightage found for the school";
        }

   

        if ($subject->type == "ECA" || $subject->type == "Club_ES" || $subject->type == "Club_1_MS" || $subject->type == "Club_2_MS" ||  $subject->type == "Club_1_HS" || $subject->type == "Club_2_HS" || $subject->type == "Reading_Book") {
            $gradeBoundaries = [
                40 => "Exceptional",
                30 => "More Than Satisfactory",
                25 => "Satisfactory",
                20 => "Need Improvement",
                0 => "Not Acceptable",
            ];
        } 

        foreach ($gradeBoundaries as $boundary => $grade) {
            if ($mark > $boundary) {
                return $grade;
            }
        }
       
        return "Not Acceptable";
        
    }
}