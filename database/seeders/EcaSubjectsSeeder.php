<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Support\Arr;

class EcaSubjectsSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = Grade::all();

        foreach ($grades as $grade) {
            $typesForGrade = $this->subjectTypesForGrades[$grade->name];

            foreach ($typesForGrade as $type) {
                $subjects = $this->getSubjectsForGrade($grade->name, $type);

                foreach ($subjects as $subjectName) {
                    Subject::create(
                        [
                            "name" => $subjectName,
                            "subject_code" => $this->generateSubjectCode($subjectName, $grade->name, $type),
                            "department_id" => $this->getRandomDepartmentId(),
                            "type" => $type,
                            "credit_hr" => fake()->numberBetween(0, 30),
                            "grade_id" => $grade->id,
                        ]
                    );
                }
            }
        }

    }


    private function generateSubjectCode($subjectName, $gradeName, $typeName)
    {
        $subjectNameAbb = strtoupper(substr($subjectName, 0, 3));
        $subjectTypeAbb = strtoupper(substr($typeName, 0, 1));

        if ($gradeName == "10" || $gradeName == "11" || $gradeName == "12") {
            $subjectCode = $subjectNameAbb . "-" . $subjectTypeAbb . "-0" . $gradeName;
        } else {
            $subjectCode = $subjectNameAbb . "-" . $subjectTypeAbb . "-00" . $gradeName;
        }

        return $subjectCode;
    }



    private function getGrades()
    {
        $grades = Grade::all();
        $gradeId = [];

        foreach ($grades as $grade) {
            array_push($gradeId, $grade->id);
        }
        $randomGrades = Arr::random($gradeId, 2);
        return $randomGrades;
    }

    private function getRandomDepartmentId()
    {
        return Department::inRandomOrder()->first()->id;
    }


    private function getSubjectsForGrade($gradeName, $subjectType)
    {
        $subjects = [
            "1" => [

                "ECA" => [
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ],
               
            ],
            "2" => [
                
                "ECA" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ],
                
            ],
            "3" => [
                
                "ECA" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ],
                
            ],
            "4" => [
                
                "ECA" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                    "Basketball",
                    "Futsal"
                ],
               
                "Club_ES" => [
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ]
            ],
            "5" => [
                
               
                "Club_1_MS" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                ],
                "Club_2_MS" => [
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ]
            ],
            "6" => [
                
                
                "Club_1_MS" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                ],
                "Club_2_MS" => [
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ]
            ],
            "7" => [
                
                
                "Club_1_MS" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                ],
                "Club_2_MS" => [
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ]
            ],
            "8" => [
                
                
                "Club_1_MS" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                ],
                "Club_2_MS" => [
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ]
            ],
            "9" => [
                
                "Club_HS" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ]
            ],
            "10" => [
                
                "Club_HS" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ]
            ],
            "11" => [
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Physics',
                    'Chemistry',
                    'Mathematics',
                    'Biology',
                    'Computer Science'
                ],
                "CREDIT" => [
                    "Python Programming",
                    "Java Programming"
                ],
                 "Reading_Book"=> [
                    "English Reading Book",
                    "Nepali Reading Book"
                ],
                "Club_HS" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ]
            ],
            "12" => [
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Physics',
                    'Chemistry',
                    'Mathematics',
                    'Biology',
                    'Computer Science'
                ],
                "CREDIT" => [
                    "Python Programming",
                    "Java Programming"
                ],
                "Reading_Book" => [
                    "English Reading Book",
                    "Nepali Reading Book"
                ],
                "Club_HS" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ]
            ],
        ];

        // if (!array_key_exists($subjectType, $subjects[$gradeName])) {
        //     return null;
        // }
        $subjectsToReturn = $subjects[$gradeName][$subjectType];
        return $subjectsToReturn;
    }


    private $types = ["MAIN", "CREDIT", "ECA", "Reading_Book", "Club_ES", "Club_1_MS", "Club_2_MS", "Club_HS"];

    private $subjectTypesForGrades = [
        "1" => [
            "ECA"
        ],
        "2" => [
            
            "ECA"
            
        ],
        "3" => [
            
            "ECA",
            
        ],
        "4" => [
            
            "ECA",
            
            "Club_ES"
        ],
        "5" => [
            
            
            "Club_1_MS",
            "Club_2_MS",
        ],
        "6" => [
            
            
            "Club_1_MS",
            "Club_2_MS",

        ],
        "7" => [
            
            
            "Club_1_MS",
            "Club_2_MS"
        ],
        "8" => [
            
            
            "Club_1_MS",
            "Club_2_MS"
        ],
        "9" => [
            
            
            "Club_HS",
        ],
        "10" => [
            
            
            "Club_HS",
        ],
        "11" => [
            
            "Reading_Book",
            "Club_HS",
        ],
        "12" => [
            
            "Reading_Book",
            "Club_HS",
        ],
    ];



}