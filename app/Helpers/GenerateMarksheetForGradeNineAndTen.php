<?php

namespace App\Helpers;

use Anuzpandey\LaravelNepaliDate\LaravelNepaliDate;
use App\Models\Student;
use App\Models\Term;
use setasign\Fpdi\Fpdi;
use ZipArchive;

class GenerateMarksheetForGradeNineAndTen
{

    public static function generate(Student $student, Term $term, $marks)
    {
        $pdf = new Fpdi();
        $pdf->AddPage();
        $pdf->setFont('Times', '', '12');

        $path = storage_path("app/images/marksheet/Grade " . $term->grade->name . ".pdf");

        $pdf->setSourceFile($path);
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId, 0, 0, null, null, true);

        $classTeacherSignature = public_path("storage/signatures/" . $student->section->classTeacher->signature);
        // $classTeacherSignature = storage_path("app/images/principal_signature.png");
        // $principalSignature = public_path("storage/signatures/" . $term->grade->school->headOfSchool->signature);
        $principalSignature = storage_path("app/images/principal_signature.png");

        $pdf->setFont('Times', 'B', '12');
        if (strtoupper($term->name) == "FIRST") {
            $pdf->SetXY(64, 19.38);
            $pdf->Write(0.1, strtoupper($term->name));
        } elseif (strtoupper($term->name) == "SECOND") {
            $pdf->SetXY(59, 19.38);
            $pdf->Write(0.1, strtoupper($term->name));
        } elseif (strtoupper($term->name) == "THIRD") {
            $pdf->SetXY(63, 19.38);
            $pdf->Write(0.1, strtoupper($term->name));
        } else {
            $pdf->SetXY(60, 19.38);
            $pdf->Write(0.1, strtoupper($term->name));
        }
        $date = now()->format('Y-m-d');
        $nepali_date = LaravelNepaliDate::from($date)->toNepaliDateArray();
        $year = $nepali_date->year;
        $pdf->SetXY(137, 19.38);
        $pdf->Write(0.1, strtoupper($year));

        $pdf->setFont('Times', '', '12');
        // $pdf->SetXY(60, 28.2);
        // $pdf->Write(0.1, strtoupper($student->emis_no));
        $pdf->SetXY(82, 28.89);
        $pdf->Write(0.1, strtoupper($student->roll_number));
        $studentName = $student->name;
        $pdf->SetXY(130, 28.89);
        $pdf->Write(0.1, strtoupper($studentName));

        $initialYOffset = 0;
        $initalAvgYoffset = 0;

        $gradePointSum = 0;
        $mainSubjectCount = count($marks["MAIN"]);

        $totalCreditHour = 0;

        // Add Has Failed Flag to denote if student has obtained NG in any one subject
        $hasFailed = false;

        foreach ($marks["MAIN"] as $subject) {
            $y_offset = $initialYOffset;
            $y_avgoffset = $initalAvgYoffset;

            if ($subject['exam_grade'] == "NG") {
                $hasFailed = true;
            }

            if ($subject['cas_grade'] == "NG") {
                $hasFailed = true;
            }

            $pdf->SetXY(82, 53.85 + $y_avgoffset);
            $pdf->Write(0.1, $subject['credit_hour']);
            $pdf->SetXY(110, 50.5 + $y_offset);
            $pdf->Write(0.1, $subject['exam_grade']);
            $pdf->SetXY(141, 50.5 + $y_offset);
            if ($subject['exam_mark'] == "NG") {
                $subject['exam_mark'] = "0.0";
            }
            $pdf->Write(0.1, $subject['exam_mark']);
            // Add practical row
            $pdf->SetXY(110, 56.7 + $y_offset);
            $pdf->Write(0.1, $subject['cas_grade']);
            $pdf->SetXY(141, 56.7 + $y_offset);
            if ($subject['cas_mark'] == "NG") {
                $subject['cas_mark'] = "0.0";
            }
            $pdf->Write(0.1, $subject['cas_mark']);

            // Add average row
            $pdf->SetXY(178, 53.35 + $y_avgoffset);
            $pdf->Write(0.1, $subject['average_point']);


            // New: Weighted GPA for credit based GPA calculation
            $wgpa = (float) $subject['average_marks'] * (float) $subject['credit_hour'];

            $subject['average_marks'] = round($wgpa, 2);
            $totalCreditHour += $subject['credit_hour'];

            $gradePointSum += floatval($subject['average_marks']);


            $initialYOffset += 12.6;
            $initalAvgYoffset += 12.6;
        }
        // $gradePointAverage = $mainSubjectCount > 0 ? $gradePointSum / $mainSubjectCount : 0;

        // New logic for WGPA average
        $gradePointAverage = $mainSubjectCount > 0 ? number_format($gradePointSum / $totalCreditHour, 2) : "0.00";

        $gradeBoundaries = [
            "3.6" => "A+",
            "3.2" => "A",
            "2.8" => "B+",
            "2.4" => "B",
            "2.0" => "C+",
            "1.6" => "C",
            "1.2" => "D",
            "0.8" => "NG"
        ];

        $gradeAverage = '';

        uksort($gradeBoundaries, function ($a, $b) {
            return floatval($b) <=> floatval($a);
        });

        foreach ($gradeBoundaries as $boundary => $grade) {
            if ($gradePointAverage > floatval($boundary)) {

                $gradeAverage = $grade;
                break;
            }
        }

        // If student has failed donot show the GPA
        if ($hasFailed) {
            $gradePointAverage = "";
            $gradeAverage = "";
        }

        $pdf->SetXY(62, 143.5);
        $pdf->Write(0.1, $gradePointAverage);

        $pdf->SetXY(151.5, 143.5);
        $pdf->Write(0.1, $gradeAverage);

        $initialYOffset = 0;
        $initalAvgYoffset = 0;

        foreach ($marks["CREDIT"] as $subject) {
            $y_offset = $initialYOffset;
            $y_avgoffset = $initalAvgYoffset;

            $pdf->SetXY(79, 177.65 + $y_avgoffset);
            $pdf->Write(0.1, $subject['credit_hour']);
            $pdf->SetXY(110, 174.9 + $y_offset);
            $pdf->Write(0.1, $subject['exam_grade']);
            $pdf->SetXY(142, 174.9 + $y_offset);
            if ($subject['exam_mark'] == "NG") {
                $subject['exam_mark'] = "0.0";
            }
            $pdf->Write(0.1, $subject['exam_mark']);
            // Add practical row
            $pdf->SetXY(110, 181.1 + $y_offset);
            $pdf->Write(0.1, $subject['cas_grade']);
            $pdf->SetXY(142, 181.1 + $y_offset);
            if ($subject['cas_mark'] == "NG") {
                $subject['cas_mark'] = "0.0";
            }
            $pdf->Write(0.1, $subject['cas_mark']);

            // Add average row
            $pdf->SetXY(178, 177.65 + $y_avgoffset);
            $pdf->Write(0.1, $subject['average_point']);


            $initialYOffset += 13.2;
            $initalAvgYoffset += 13.2;
        }
        // $pdf->SetFont('ZapfDingbats', '', 10);
        // $checkMark = "4";

        // $x_positions = [55, 90, 125, 155, 188];
        // $y_positions = [209.5,215, 221.5];

        // $i = 0;
        // foreach ($marks["ECA"] as $subject) {
        //     $cas_mark = $subject["cas_mark"];
        //     $index = array_search($cas_mark, ['Exceptional', 'More Than Satisfactory', 'Satisfactory', 'Need Improvement', 'Not Acceptable']);

        //     $pdf->setXY($x_positions[$index], $y_positions[$i]);
        //     $pdf->Write(0.1, $checkMark);

        //     if($subject['type']=='Club_HS'){
        //         $pdf->setFont('Times', '', '12');
        //         $pdf->setXY(13.8,220.6);
        //         $pdf->Write(0.1, $subject['name']);
        //         $pdf->SetFont('ZapfDingbats', '', 10);
        //     }
        //     $i++;
        // }

        $pdf->Image($classTeacherSignature, 18, 243, 20, 20);
        $pdf->Image($principalSignature, 185, 243, 20, 20);

        $outputFolder = storage_path("app/results/Grade " . $term->grade->name . " " . $term->name . "/");

        if (!file_exists($outputFolder)) {
            mkdir($outputFolder, 0755, true);
        }

        $outputFilePath = $outputFolder . "Grade " . $term->grade->name . " " . $student->name . "_" . $student->roll_number . ".pdf";

        $pdf->Output("F", $outputFilePath);

        $zip_file = storage_path("app/results/Grade " . $term->grade->name . " " . $term->name . ".zip");

        touch($zip_file);

        $zip = new ZipArchive;

        $this_zip = $zip->open($zip_file);

        if ($this_zip) {

            $file_path = $outputFolder . "Grade " . $term->grade->name . " " . $student->name . "_" . $student->roll_number . ".pdf";

            $name = "Grade " . $term->grade->name . " " . $student->name . "_" . $student->roll_number . ".pdf";

            $zip->addFile($file_path, $name);
        }
    }
}
