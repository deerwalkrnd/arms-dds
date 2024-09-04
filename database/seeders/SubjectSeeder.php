<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Support\Arr;

class SubjectSeeder extends Seeder
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
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Mathematics',
                    'Science',
                    'Hamro Serophero'
                ],
                "CREDIT" => [
                    "Sanskrit",
                    "Coding"
                ],
                "ECA" => [
                    "Basketball",
                    "Futsal",
                    "Badminton",
                    "Chess",
                    "Taekwondo",
                    "TT",
                    "Yoga"
                ],
                "Reading_Book" => [
                    "Nepali Reading Book",
                    "English Reading Book"
                ],
            ],
            "2" => [
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Mathematics',
                    'Science',
                    'Hamro Serophero'
                ],
                "CREDIT" => [
                    "Sanskrit",
                    "Coding"
                ],
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
                "Reading_Book" => [
                    "Nepali Reading Book",
                    "English Reading Book"
                ],
            ],
            "3" => [
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Mathematics',
                    'Science',
                    'Hamro Serophero'
                ],
                "CREDIT" => [
                    "Sanskrit",
                    "Coding"
                ],
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
                "Reading_Book" => [
                    "Nepali Reading Book",
                    "English Reading Book"
                ],
            ],
            "4" => [
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Mathematics',
                    'Science and Technology',
                    'Samajik Aadhyan',
                    'HPCA'
                ],
                "CREDIT" => [
                    "Sanskrit",
                    "Coding"
                ],
                "ECA" => [
                    "Dance",
                    "Drama",
                    "Music",
                    "Visual Art",
                    "Basketball",
                    "Futsal"
                ],
                "Reading_Book" => [
                    "Nepali Reading Book",
                    "English Reading Book"
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
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Mathematics',
                    'Science and Technology',
                    'Samajik Aadhyan',
                    'HPCA',
                    'Nepal Bhasa'
                ],
                "CREDIT" => [
                    "Sanskrit",
                    "Coding"
                ],
                "Reading_Book" => [
                    "English Reading Book",
                    "Nepali Reading Book"
                ],
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
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Mathematics',
                    'Science and Technology',
                    'Samajik Aadhyan',
                    'HPCA',
                    'Nepal Bhasa'
                ],
                "CREDIT" => [
                    "Sanskrit",
                    "Coding"
                ],
                "Reading_Book" => [
                    "English Reading Book",
                    "Nepali Reading Book"
                ],
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
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Mathematics',
                    'Science and Technology',
                    'Samajik Aadhyan',
                    'HPCA',
                    'Nepal Bhasa'
                ],
                "CREDIT" => [
                    "Sanskrit",
                    "Coding"
                ],
                "Reading_Book" => [
                    "English Reading Book",
                    "Nepali Reading Book"
                ],
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
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Mathematics',
                    'Science and Technology',
                    'Samajik Aadhyan',
                    'HPCA',
                    'Nepal Bhasa'
                ],
                "CREDIT" => [
                    "Sanskrit",
                    "Coding"
                ],
                "Reading_Book" => [
                    "English Reading Book",
                    "Nepali Reading Book"
                ],
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
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Compulsory Mathematics',
                    'Science and Technology',
                    'Samajik Aadhyan',
                    'Additional Mathematics',
                    'Computer Science'
                ],
                "CREDIT" => [
                    "Coding"
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
            "10" => [
                "MAIN" => [
                    'Nepali',
                    'English',
                    'Compulsory Mathematics',
                    'Science and Technology',
                    'Samajik Aadhyan',
                    'Additional Mathematics',
                    'Computer Science'
                ],
                "CREDIT" => [
                    "Coding"
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
            "MAIN",
            "CREDIT",
            "ECA",
            "Reading_Book"
        ],
        "2" => [
            "MAIN",
            "CREDIT",
            "ECA",
            "Reading_Book"
        ],
        "3" => [
            "MAIN",
            "CREDIT",
            "ECA",
            "Reading_Book"
        ],
        "4" => [
            "MAIN",
            "CREDIT",
            "ECA",
            "Reading_Book",
            "Club_ES"
        ],
        "5" => [
            "MAIN",
            "CREDIT",
            "Reading_Book",
            "Club_1_MS",
            "Club_2_MS",
        ],
        "6" => [
            "MAIN",
            "CREDIT",
            "Reading_Book",
            "Club_1_MS",
            "Club_2_MS",

        ],
        "7" => [
            "MAIN",
            "CREDIT",
            "Reading_Book",
            "Club_1_MS",
            "Club_2_MS"
        ],
        "8" => [
            "MAIN",
            "CREDIT",
            "Reading_Book",
            "Club_1_MS",
            "Club_2_MS"
        ],
        "9" => [
            "MAIN",
            "CREDIT",
            "Reading_Book",
            "Club_HS",
        ],
        "10" => [
            "MAIN",
            "CREDIT",
            "Reading_Book",
            "Club_HS",
        ],
        "11" => [
            "MAIN",
            "CREDIT",
            "Reading_Book",
            "Club_HS",
        ],
        "12" => [
            "MAIN",
            "CREDIT",
            "Reading_Book",
            "Club_HS",
        ],
    ];



}