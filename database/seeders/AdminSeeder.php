<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ;

        // $admin = User::create([
        //     "name" => "System Admin",
        //     "email" => "dmt@dhading.deerwalk.edu.np",
        //     "password" => bcrypt("password")
        //     // New Passowrd: WithLoveFromDMT2024
        // ]);

        $schoolAdmin = User::create([
            "name" => "ARMS Admin",
            "email" => "admin@dhading.deerwalk.edu.np",
            "password" => bcrypt("admin123"),
            // New Passowrd: armsadmin2024

        ]);

        // Retrieve the role with name 'admin'
        $adminRole = Role::where('name', 'admin')->first();
        $hodRole = Role::where("name", "hod")->first();
        $hosRole = Role::where("name", "hos")->first();
        $teacherRole = Role::where("name", "teacher")->first();



        // Set the user_id and attach the role to the user
        // $admin->roles()->attach($adminRole->id, ['user_id' => $admin->id]);
        $schoolAdmin->roles()->attach($adminRole->id, ['user_id' => $schoolAdmin->id]);
        $schoolAdmin->roles()->attach($hosRole->id, ['user_id' => $schoolAdmin->id]);
        $schoolAdmin->roles()->attach($hodRole->id, ['user_id' => $schoolAdmin->id]);
        $schoolAdmin->roles()->attach($teacherRole->id, ['user_id' => $schoolAdmin->id]);

    }
}
