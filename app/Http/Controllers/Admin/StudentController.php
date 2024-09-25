<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\StudentImport;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Http\Request;

use App\Http\Requests\StudentRequest;
use App\Models\Section;
use App\Helpers\ZipExtractor;
use App\Models\Student;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $students = Student::with("section")->get()->sortBy("roll_number");

            return view('admin.students.index', compact('students'));

        } catch (Exception $e) {

            Log::error($e->getMessage());
            return redirect()->back()->withInput()->withErrors(["errors" => "Failed to retrieve students"]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $sections = Section::all()->sortBy('grade.name');
            $subjectTypes = ['Club_ES','Club_1_MS','Club_2_MS','Club_1_HS','Club_2_HS'];
            $subjects = Subject::whereIn('type',$subjectTypes)->get();
            return view('admin.students.create', compact("sections","subjects"));

        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->back()->withInput()->withErrors(["errors" => "Failed to create student"]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StudentRequest $request)
    {
        $data = $request->validated();

        try {
            if ($request->hasFile("image")) {

                $studentName = str_replace(" ", "_", $data['name']);
                $name = $studentName . "_" . $data["roll_number"];

                // Get the file extension
                $extension = $request->file('image')->getClientOriginalExtension();

                // Generate a unique filename to avoid conflicts
                $fileName = $name . '_' . Carbon::now()->timestamp . '.' . $extension;

                // Store the file with the new filename
                $path = $data["image"]->storeAs("students", $fileName, 'public');

                $data["image"] = $fileName;
            }
            $sectionId=$data["section_id"];
            $section=Section::findOrFail($sectionId);
            $grade=$section->grade->name;
            // Checking for the club amount and their respective grades 
            if (isset($data['subject'])) {
                $subjects=$data["subject"];
                if(count($subjects)==2 && !($grade >=5 && $grade<=8)){
                    return redirect()->back()->withInput()->withErrors(["errors" => "The Section and Club amount must match"]);
                }elseif(count($subjects)==1 && !($grade==4 || ($grade>=9 && $grade<=12))){
                    return redirect()->back()->withInput()->withErrors(["errors" => "The Section and Club amount must match"]);
                }elseif(count($subjects)>2){
                    return redirect()->back()->withInput()->withErrors(["errors" => "Invalid Club Count"]);
                }
            }
           
            $student = Student::create($data);
            if (!empty($data['subject'])) {
                $student->subject()->attach($data['subject']);
            }
            return redirect(route('students.index'))->with('success', 'Student added successfully');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withInput()->withErrors(["errors" => "Failed to create student"]);
        }
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $student = Student::findOrFail($id);
            $sections = Section::all()->sortBy("grade.name");
            $subjectTypes = ['Club_ES','Club_1_MS','Club_2_MS','Club_1_HS','Club_2_HS'];
            $subjects = Subject::whereIn('type',$subjectTypes)->get();

            return view('admin.students.edit')->with(compact('student', 'sections',"subjects"));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withInput()->withErrors(["errors" => "Failed to edit student"]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StudentRequest $request, $id)
    {
        $data = $request->validated();
        try {
            $student = Student::findOrFail($id);

            if ($request->hasFile("image")) {

                $studentName = str_replace(" ", "_", $data['name']);
                $name = $studentName . "_" . $data["roll_number"];

                // Get the file extension
                $extension = $request->file('image')->getClientOriginalExtension();

                // Generate a unique filename to avoid conflicts
                $fileName = $name . '_' . Carbon::now()->timestamp . '.' . $extension;

                // Store the file with the new filename
                Storage::delete("public/students/" . $student->image);

                $path = $data["image"]->storeAs("students", $fileName, 'public');

                $data["image"] = $fileName;
            }
            $sectionId=$data["section_id"];
            $section=Section::findOrFail($sectionId);
            $grade=$section->grade->name;
            // Checking for the club amount and their respective grades 
            if (isset($data['subject'])) {
                $subjects=$data["subject"];
                if(count($subjects)==2 && !($grade >=5 && $grade<=8)){
                    return redirect()->back()->withInput()->withErrors(["errors" => "The Section and Club amount must match"]);
                }elseif(count($subjects)==1 && !($grade==4 || ($grade>=9 && $grade<=12))){
                    return redirect()->back()->withInput()->withErrors(["errors" => "The Section and Club amount must match"]);
                }elseif(count($subjects)>2){
                    return redirect()->back()->withInput()->withErrors(["errors" => "Invalid Club Count"]);
                }
            }
            $student->update($data);
            if (isset($data['subject'])) {
                $student->subject()->sync($data['subject']);
                $student->update($data);
            }

            return redirect(route('students.index'))->with('success', 'Student information updated');

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withInput()->withErrors(["errors" => "Failed to create student"]);
        }

    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Student $student)
    {
        try {
            $student = Student::findOrFail($student->id);

            $student->update(["status" => "DROPPED_OUT"]);

            // Storage::delete("public/students/" . $student->image);

        // $student->delete();

            return redirect(route('students.index'))->with('success', 'Student Successfully Deleted');
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->back()->withInput()->withErrors(["errors" => "Failed to delete student"]);

        }
    }

    public function getBulkUpload()
    {
        return view('admin.students.bulkUpload');
    }

    public function bulkSample()
    {
        $file = public_path('file/StudentUploadSample.csv');
        return response()->download($file);
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'student_csv' => 'required|mimes:csv,xlsx,txt',
            'zipFile' => 'required|mimes:zip'
        ]);

        $extension = $request->file('student_csv')->extension();
        $zipFileExtension = $request->file('zipFile')->extension();
        $fileName = time() . '.' . $extension;
        $zipFileName = time() . '.' . $zipFileExtension;
        $path = $request->file('student_csv')->storeAs('public/csv', $fileName);
        $zipFilePath = $request->file('zipFile')->storeAs('public/zip', $zipFileName);
        $extractZipFileToFolder = 'public/students';

        try {
            ZipExtractor::extractZip($extractZipFileToFolder, $zipFilePath);
        } catch (Exception $e) {
            return redirect()->route('student.getBulkUpload')->with('error', $e->getMessage());
        }

        $studentImport = new StudentImport;

        $studentImport->import($path);

        if ($studentImport->failures()->isNotEmpty()) {
            return redirect(route('student.getBulkUpload'))->withFailures($studentImport->failures());
        }
        Storage::delete($path);
        return redirect(route('students.index'))->with('success', 'Student Uploaded Successfully');
    }
}
