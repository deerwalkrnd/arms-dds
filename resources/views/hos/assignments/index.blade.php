@extends('layouts.hos.app')
@section('Page Title', 'All Assignments')

@section('title')
    <p>Assignments</p>
    <a href="{{ route('hosCasReport.index') }}"
        class="bg-dark-orange text-white px-3 rounded-md markType text-sm font-normal flex items-center hover:bg-white hover:text-dark-orange border border-dark-orange transition-colors py-2">Report</a>
@endsection

@section('content')
    <div class="w-full">
        <table class="w-full" id="myTable">
            <thead class="bg-dark-gray">
                <tr class="bg-dark-gray h-14 border">
                    <th class="pl-4 text-left">S.No.</th>
                    <th class="text-left">Term</th>
                    <th class="text-left">Name</th>
                    <th class="text-left">Date</th>
                    <th class="text-left">Subject</th>
                    <th class="text-left">Subject Teacher</th>
                    <th class="text-left">Section</th>
                    <th class="text-left">CAS Type</th>
                    <th class="pr-4 text-center align-center-important" data-dt-order="disable">Actions</th>
                </tr>
            </thead>
            @php
            $i=1;
            @endphp
            @foreach ($sortedAssignments as $assignment)
            {{-- @dd($assignment->subjectTeacher->subject->type) --}}
                <tr class="h-16 border-b-2 hover:bg-dark-gray">
                    <td class="pl-4">{{ $i++ }}</td>
                    <td>{{ $assignment->term->name }}</td>
                    <td>{{ $assignment->name }}</td>
                    <td>{{ $assignment->date_assigned }}</td>
                    <td>{{ $assignment->subjectTeacher->subject->name }}</td>
                    <td>{{ $assignment->subjectTeacher->teacher->name }}</td>
                    <td>{{ $assignment->subjectTeacher->section->name }}</td>

                    {{-- filter for the different  subject types  --}}
                    
                    {{-- MAIN/CREDIT --}}
                    
                    @if($assignment->subjectTeacher->subject->type=="MAIN" || $assignment->subjectTeacher->subject->type=="CREDIT")
                    <td>{{ $assignment->casType->name }}</td>
                    <td class="pr-4 text-center">
                        <div class="flex gap-2 justify-center">
                            <a href="{{ route('hosAssignments.edit', $assignment->id) }}">
                                <x-edit-button>Edit</x-edit-button>
                            </a>
                            <form action="{{ route('hosAssignments.destroy', $assignment->id) }}" method="POST"
                                id="delete-{{ $assignment->id }}">
                                @csrf
                                @method('DELETE')
                                <x-delete-button>Delete</x-delete-button>
                            </form>          
                        </div>

                    </td>

                    {{-- ECA --}}
                    
                    @elseif($assignment->subjectTeacher->subject->type=="ECA")
                    <td>{{ $assignment->ecaCasType->name }}</td>
                    <td class="pr-4 text-center">
                        <div class="flex gap-2 justify-center">
                            <a href="{{ route('hosEcaAssignments.edit', $assignment->id) }}">
                                <x-edit-button>Edit</x-edit-button>
                            </a>
                            <form action="{{ route('hosEcaAssignments.destroy', $assignment->id) }}" method="POST"
                                id="delete-{{ $assignment->id }}">
                                @csrf
                                @method('DELETE')
                                <x-delete-button>Delete</x-delete-button>
                            </form>          
                        </div>

                    </td>

                    {{-- Reading Book --}}

                    @elseif($assignment->subjectTeacher->subject->type=="Reading_Book")
                    <td>{{ $assignment->readingCasType->name }}</td>
                    <td class="pr-4 text-center">
                        <div class="flex gap-2 justify-center">
                            <a href="{{ route('hosReadingAssignments.edit', $assignment->id) }}">
                                <x-edit-button>Edit</x-edit-button>
                            </a>
                            <form action="{{ route('hosReadingAssignments.destroy', $assignment->id) }}" method="POST"
                                id="delete-{{ $assignment->id }}">
                                @csrf
                                @method('DELETE')
                                <x-delete-button>Delete</x-delete-button>
                            </form>          
                        </div>

                    </td>
                    @else
                    <td>{{ $assignment->ecaCasType->name }}</td>
                    <td class="pr-4 text-center">
                        <div class="flex gap-2 justify-center">
                            <a href="{{ route('hosClubAssignments.edit', $assignment->id) }}">
                                <x-edit-button>Edit</x-edit-button>
                            </a>
                            <form action="{{ route('hosClubAssignments.destroy', $assignment->id) }}" method="POST"
                                id="delete-{{ $assignment->id }}">
                                @csrf
                                @method('DELETE')
                                <x-delete-button>Delete</x-delete-button>
                            </form>          
                        </div>

                    </td>
                    @endif
            @endforeach
            {{-- @foreach ($ecaAssignments as $assignment)
            <tr class="h-16 border-b-2 hover:bg-dark-gray">
                <td class="pl-4">{{ $i++ }}</td>
                <td>{{ $assignment->name }}</td>
                <td>{{ $assignment->date_assigned }}</td>
                <td>{{ $assignment->subjectTeacher->subject->name }}</td>

                <td>{{ $assignment->subjectTeacher->section->name }}</td>
                <td>{{ $assignment->ecaCasType->name }}</td>
                <td>{{ $assignment->term->name }}</td>
                <td class="pr-4 text-center">
                    <div class="flex gap-2 justify-center">
                        <a href="{{ route('hosEcaAssignments.edit', $assignment->id) }}">
                            <x-edit-button>Edit</x-edit-button>
                        </a>
                        <form action="{{ route('hosEcaAssignments.destroy', $assignment->id) }}" method="POST"
                            id="delete-{{ $assignment->id }}">
                            @csrf
                            @method('DELETE')
                            <x-delete-button>Delete</x-delete-button>
                        </form>          
                    </div>

                </td>
        @endforeach
        @foreach ($clubAssignments as $assignment)
            <tr class="h-16 border-b-2 hover:bg-dark-gray">
                <td class="pl-4">{{ $i++ }}</td>
                <td>{{ $assignment->name }}</td>
                <td>{{ $assignment->date_assigned }}</td>
                <td>{{ $assignment->subjectTeacher->subject->name }}</td>

                <td>{{ $assignment->subjectTeacher->section->name }}</td>
                <td>{{ $assignment->ecaCasType->name }}</td>
                <td>{{ $assignment->term->name }}</td>
                <td class="pr-4 text-center">
                    <div class="flex gap-2 justify-center">
                        <a href="{{ route('hosClubAssignments.edit', $assignment->id) }}">
                            <x-edit-button>Edit</x-edit-button>
                        </a>
                        <form action="{{ route('hosClubAssignments.destroy', $assignment->id) }}" method="POST"
                            id="delete-{{ $assignment->id }}">
                            @csrf
                            @method('DELETE')
                            <x-delete-button>Delete</x-delete-button>
                        </form>          
                    </div>

                </td>
        @endforeach
        @foreach ($readingAssignments as $assignment)
            <tr class="h-16 border-b-2 hover:bg-dark-gray">
                <td class="pl-4">{{ $i++ }}</td>
                <td>{{ $assignment->name }}</td>
                <td>{{ $assignment->date_assigned }}</td>
                <td>{{ $assignment->subjectTeacher->subject->name }}</td>

                <td>{{ $assignment->subjectTeacher->section->name }}</td>
                <td>{{ $assignment->readingCasType->name }}</td>
                <td>{{ $assignment->term->name }}</td>
                <td class="pr-4 text-center">
                    <div class="flex gap-2 justify-center">
                        <a href="{{ route('hosReadingAssignments.edit', $assignment->id) }}">
                            <x-edit-button>Edit</x-edit-button>
                        </a>
                        <form action="{{ route('hosReadingAssignments.destroy', $assignment->id) }}" method="POST"
                            id="delete-{{ $assignment->id }}">
                            @csrf
                            @method('DELETE')
                            <x-delete-button>Delete</x-delete-button>
                        </form>          
                    </div>

                </td>
        @endforeach --}}
        </table>

    </div>
@endsection
