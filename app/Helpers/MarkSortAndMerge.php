<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class MarkSortAndMerge
{
    public static $casGradeCollectionByStudent;
    public static $casGradePointCollectionByStudent;
    public static $examGradeCollectionByStudent;
    public static $examGradePointCollectionByStudent;
    public static $totalGPACollectionConversion;
    public static $totalMarksCollectionByStudent;
    public static $sortOrderForMainSubjects;
    public static $sortOrderForCreditSubjects;
    public static $sortOrderForEcaSubjects;

    private static $subjectTypes = ["MAIN", "CREDIT", "ECA"];

    public static function sortAndMerge(
        $casGradePointCollectionByStudent,
        $casGradeCollectionByStudent,
        $examGradePointCollectionByStudent,
        $examGradeCollectionByStudent,
        $sortOrderForMainSubjects,
        $sortOrderForCreditSubjects,
        $sortOrderForEcaSubjects,
        $totalGPACollectionConversion,
        $totalMarksCollectionByStudent
    ) {
        self::$casGradeCollectionByStudent = $casGradeCollectionByStudent;
        self::$casGradePointCollectionByStudent = $casGradePointCollectionByStudent;
        self::$examGradeCollectionByStudent = $examGradeCollectionByStudent;
        self::$examGradePointCollectionByStudent = $examGradePointCollectionByStudent;
        self::$sortOrderForMainSubjects = $sortOrderForMainSubjects;
        self::$sortOrderForCreditSubjects = $sortOrderForCreditSubjects;
        self::$sortOrderForEcaSubjects = $sortOrderForEcaSubjects;
        self::$totalGPACollectionConversion = $totalGPACollectionConversion;
        self::$totalMarksCollectionByStudent = $totalMarksCollectionByStudent;

        $marks = array();
        $instance = new self();

        try {
            foreach (self::$subjectTypes as $subjectType) {
                $sortOrder = self::getSortOrder($subjectType);
                $marks[$subjectType] = collect()
                    ->merge($instance->sort(self::$casGradeCollectionByStudent, $sortOrder, $subjectType))
                    ->merge($instance->sort(self::$casGradePointCollectionByStudent, $sortOrder, $subjectType))
                    ->merge($instance->sort(self::$examGradeCollectionByStudent, $sortOrder, $subjectType))
                    ->merge($instance->sort(self::$examGradePointCollectionByStudent, $sortOrder, $subjectType))
                    ->merge($instance->sort(self::$totalGPACollectionConversion, $sortOrder, $subjectType))
                    ->merge($instance->sort(self::$totalMarksCollectionByStudent, $sortOrder, $subjectType))
                    ->groupBy('name')
                    ->map(function ($items) {
                        return Arr::collapse($items);
                    });
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $marks;
    }

    private static function getSortOrder($subjectType)
    {   
        switch ($subjectType) {
            case "MAIN":
                return self::$sortOrderForMainSubjects;
            case "CREDIT":
                return self::$sortOrderForCreditSubjects;
            case "ECA":
                return self::$sortOrderForEcaSubjects;
            default:
                return [];
        }
    }

    private function sort($collection, $sortOrder, $subjectType)
    {
        
        return $collection->filter(function ($item) use ($subjectType,$sortOrder) {
            if($item["type"]=="MAIN"||$item["type"]=="CREDIT"){
            }elseif($item["type"]=="ECA"||$item["type"]=="Reading_Book"){
                $item["type"]="ECA";
            }else{
                $subjectExists=array_search($item["name"],$sortOrder);
                if($subjectExists){
                    $item["type"]="ECA";
                }
            }
            return $item['type'] == $subjectType;
        })->sortBy(function ($item) use ($sortOrder) {
            $index = array_search($item["name"], $sortOrder);
            return $index === false ? PHP_INT_MAX : $index;
        });
    }
}
