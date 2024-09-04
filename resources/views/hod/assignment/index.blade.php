@extends('layouts.hod.app')
@section('Page Title', 'All Assignments')

@section('title', 'CAS')

@section('content')
<div class="w-full">
    <table class="w-full " id="myTable">
        <thead class="bg-dark-gray">
            <tr class="*:text-left bg-dark-gray h-14 border">
                <th class="pl-4">S.No.</th>
                <th>Term</th>
                <th>Name</th>
                <th>Date</th>
                <th>Subject</th>
                <th>Subject Teacher</th>
                <th>Section</th>
                <th>CAS Type</th>
                <th class="align-center-important" data-dt-order="disable">Actions</th>
            </tr>
        </thead>
        @php
        $i=1;
        @endphp
        @foreach ($sortedAssignments as $assignment)
        <tr class="h-14 border-b-2 hover:bg-dark-gray">
            <td>{{ $i++ }}</td>
            <td>{{ $assignment->term->name }}</td>
            <td>{{ $assignment->name }}</td>
            <td>{{ $assignment->date_assigned }}</td>
            <td>{{ $assignment->subjectTeacher->subject->name }}</td>
            <td>{{ $assignment->subjectTeacher->teacher->name }}</td>

            <td>{{ $assignment->subjectTeacher->section->name }}</td>

            
            @if($assignment->subjectTeacher->subject->type=="MAIN" || $assignment->subjectTeacher->subject->type=="CREDIT")
            <td>{{ $assignment->casType->name }}</td>

            <td class="pr-4">

                <a href="{{ route('hodAssignments.view', $assignment->id) }}"
                    class="px-4 py-[0.3rem] border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>


            </td>
            @elseif($assignment->subjectTeacher->subject->type=="ECA")
            <td>{{ $assignment->ecaCasType->name }}</td>

            <td class="pr-4">

                <a href="{{ route('hodEcaAssignments.view', $assignment->id) }}"
                    class="px-4 py-[0.3rem] border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>


            </td>
            @elseif($assignment->subjectTeacher->subject->type=="Reading_Book")
            <td>{{ $assignment->readingCasType->name }}</td>

            <td class="pr-4">

                <a href="{{ route('hodReadingAssignments.view', $assignment->id) }}"
                    class="px-4 py-[0.3rem] border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>


            </td>
            @else
            <td>{{ $assignment->ecaCasType->name }}</td>

            <td class="pr-4">

                <a href="{{ route('hodClubAssignments.view', $assignment->id) }}"
                    class="px-4 py-[0.3rem] border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>


            </td>
            @endif
        </tr>
        @endforeach
        {{-- @foreach ($ecaAssignments as $assignment)
        <tr class="h-14 border-b-2 hover:bg-dark-gray">
            <td>{{ $i++ }}</td>
            <td>{{ $assignment->name }}</td>
            <td>{{ $assignment->date_assigned }}</td>
            <td>{{ $assignment->subjectTeacher->subject->name }}</td>

            <td>{{ $assignment->subjectTeacher->section->name }}</td>
            <td>{{ $assignment->ecaCasType->name }}</td>
            <td>{{ $assignment->term->name }}</td>

            <td class="pr-4">

                <a href="{{ route('hodEcaAssignments.view', $assignment->id) }}"
                    class="px-4 py-[0.3rem] border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>


            </td>
        </tr>
        @endforeach
        @foreach ($clubAssignments as $assignment)
        <tr class="h-14 border-b-2 hover:bg-dark-gray">
            <td>{{ $i++ }}</td>
            <td>{{ $assignment->name }}</td>
            <td>{{ $assignment->date_assigned }}</td>
            <td>{{ $assignment->subjectTeacher->subject->name }}</td>

            <td>{{ $assignment->subjectTeacher->section->name }}</td>
            <td>{{ $assignment->ecaCasType->name }}</td>
            <td>{{ $assignment->term->name }}</td>

            <td class="pr-4">

                <a href="{{ route('hodClubAssignments.view', $assignment->id) }}"
                    class="px-4 py-[0.3rem] border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>


            </td>
        </tr>
        @endforeach
        @foreach ($readingAssignments as $assignment)
        <tr class="h-14 border-b-2 hover:bg-dark-gray">
            <td>{{ $i++ }}</td>
            <td>{{ $assignment->name }}</td>
            <td>{{ $assignment->date_assigned }}</td>
            <td>{{ $assignment->subjectTeacher->subject->name }}</td>

            <td>{{ $assignment->subjectTeacher->section->name }}</td>
            <td>{{ $assignment->readingCasType->name }}</td>
            <td>{{ $assignment->term->name }}</td>

            <td class="pr-4">

                <a href="{{ route('hodReadingAssignments.view', $assignment->id) }}"
                    class="px-4 py-[0.3rem] border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>


            </td>
        </tr>
        @endforeach --}}
    </table>

</div>
@endsection