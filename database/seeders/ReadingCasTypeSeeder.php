<?php

namespace Database\Seeders;

use App\Models\ReadingCasType;
use App\Models\School;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReadingCasTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $casTypes = ["Class Participation", "Homework","Book Log","Essential Skill"];
        $cas_marks = [15,10,15,10];
        $schools = School::all();

        foreach ($schools as $school) {
            $i = 0;
            foreach ($casTypes as $casType) {
                ReadingCasType::create([
                    "name" => $casType,
                    "school_id" => $school->id,
                    "full_marks" => $cas_marks[$i],
                    "weightage" => $cas_marks[$i],
                ]);
                $i++;
            }
        }
    }
}