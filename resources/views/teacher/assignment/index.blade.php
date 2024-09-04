@extends('layouts.teacher.app')
@section('Page Title', 'All Assignments')

@section('title')
    <p>CAS</p>
    <a href="{{ route('teacherCasReport.index') }}"
       class="bg-dark-orange text-white px-3 rounded-md markType text-sm font-normal flex items-center hover:bg-white hover:text-dark-orange border border-dark-orange transition-colors">Report</a>
@endsection

@section('content')
    <div class="w-full">
        <table class="w-full" id="myTable" >
            <thead class="bg-dark-gray">
            <tr class="bg-dark-gray h-14 border">
                <th class="pl-4 text-left">SNo.</th>
                <th class="text-left">Term</th>
                <th class="text-left">Name</th>
                <th class="text-left">Date</th>
                <th class="text-left">Subject</th>
                <th class="text-left">Subject Teacher</th>
                <th class="text-left">Section</th>
                <th class="text-left">CAS Type</th>
                <th class="text-center align-center-important" data-dt-order="disable">Actions</th>
            </tr>
            </thead>
            @php
                $i=1;
            @endphp
            @foreach ($sortedAssignments as $assignment)
                <tr class="h-16 border-b-2 hover:bg-dark-gray">
                    <td class="pl-4">{{ $i++ }}</td>
                    <td>{{ $assignment->term->name }}</td>
                    <td>{{ $assignment->name }}</td>
                    <td>{{ $assignment->date_assigned }}</td>
                    <td>{{ $assignment->subjectTeacher->subject->name }}</td>
                    <td>{{ $assignment->subjectTeacher->teacher->name }}</td>

                    <td>{{ $assignment->subjectTeacher->section->name }}</td>

                    @if($assignment->subjectTeacher->subject->type=="MAIN" || $assignment->subjectTeacher->subject->type=="CREDIT")
                    <td>{{ $assignment->casType->name }}</td>
                    <td class="pr-4">


                        <div class="flex gap-2 justify-center">
                            @if ($assignment->submitted == '0')
                                <a href="{{ route('teacherAssignments.edit', $assignment->id) }}"
                                   class="px-4 py-1 border bg-white border-custom-black text-custom-black  hover:text-white hover:bg-custom-black transition-colors rounded-md">Edit</a>
                            @else
                                <a href="{{ route('teacherAssignments.view', $assignment->id) }}"
                                   class="px-4 py-1 border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>
                            @endif

                            <form action="{{ route('teacherAssignments.destroy', $assignment->id) }}" method="POST" id="delete-{{ $assignment->id }}">

                                @csrf
                                @method('DELETE')
                                <x-delete-button>Delete</x-delete-button>
                            </form>
                        </div>

                    </td>
                    @elseif($assignment->subjectTeacher->subject->type=="ECA")
                    <td>{{ $assignment->ecaCasType->name }}</td>
                    <td class="pr-4">


                        <div class="flex gap-2 justify-center">
                            @if ($assignment->submitted == '0')
                                <a href="{{ route('teacherEcaAssignments.edit', $assignment->id) }}"
                                   class="px-4 py-1 border bg-white border-custom-black text-custom-black  hover:text-white hover:bg-custom-black transition-colors rounded-md">Edit</a>
                            @else
                                <a href="{{ route('teacherEcaAssignments.view', $assignment->id) }}"
                                   class="px-4 py-1 border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>
                            @endif

                            <form action="{{ route('teacherEcaAssignments.destroy', $assignment->id) }}" method="POST" id="delete-{{ $assignment->id }}">

                                @csrf
                                @method('DELETE')
                                <x-delete-button>Delete</x-delete-button>
                            </form>
                        </div>

                    </td>
                    @elseif($assignment->subjectTeacher->subject->type=="Reading_Book")
                    <td>{{ $assignment->readingCasType->name }}</td>
                    <td class="pr-4">


                        <div class="flex gap-2 justify-center">
                            @if ($assignment->submitted == '0')
                                <a href="{{ route('teacherReadingAssignments.edit', $assignment->id) }}"
                                   class="px-4 py-1 border bg-white border-custom-black text-custom-black  hover:text-white hover:bg-custom-black transition-colors rounded-md">Edit</a>
                            @else
                                <a href="{{ route('teacherReadingAssignments.view', $assignment->id) }}"
                                   class="px-4 py-1 border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>
                            @endif

                            <form action="{{ route('teacherReadingAssignments.destroy', $assignment->id) }}" method="POST" id="delete-{{ $assignment->id }}">

                                @csrf
                                @method('DELETE')
                                <x-delete-button>Delete</x-delete-button>
                            </form>
                        </div>

                    </td>
                    @else
                    <td>{{ $assignment->ecaCasType->name }}</td>
                    <td class="pr-4">


                        <div class="flex gap-2 justify-center">
                            @if ($assignment->submitted == '0')
                                <a href="{{ route('teacherClubAssignments.edit', $assignment->id) }}"
                                   class="px-4 py-1 border bg-white border-custom-black text-custom-black  hover:text-white hover:bg-custom-black transition-colors rounded-md">Edit</a>
                            @else
                                <a href="{{ route('teacherClubAssignments.view', $assignment->id) }}"
                                   class="px-4 py-1 border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>
                            @endif

                            <form action="{{ route('teacherClubAssignments.destroy', $assignment->id) }}" method="POST" id="delete-{{ $assignment->id }}">

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
                <td class="pr-4">
                    <div class="flex gap-2 justify-center">
                        @if ($assignment->submitted == '0')
                            <a href="{{ route('teacherEcaAssignments.edit', $assignment->id) }}"
                                class="px-4 py-1 border bg-white border-custom-black text-custom-black  hover:text-white hover:bg-custom-black transition-colors rounded-md">Edit</a>
                        @else
                            <a href="{{ route('teacherEcaAssignments.view', $assignment->id) }}"
                                class="px-4 py-1 border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>
                        @endif

                        <form action="{{ route('teacherEcaAssignments.destroy', $assignment->id) }}" method="POST"
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
                <td class="pr-4">
                    <div class="flex gap-2 justify-center">
                        @if ($assignment->submitted == '0')
                            <a href="{{ route('teacherClubAssignments.edit', $assignment->id) }}"
                                class="px-4 py-1 border bg-white border-custom-black text-custom-black  hover:text-white hover:bg-custom-black transition-colors rounded-md">Edit</a>
                        @else
                            <a href="{{ route('teacherClubAssignments.view', $assignment->id) }}"
                                class="px-4 py-1 border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>
                        @endif

                        <form action="{{ route('teacherClubAssignments.destroy', $assignment->id) }}" method="POST"
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
                <td class="pr-4">
                    <div class="flex gap-2 justify-center">
                        @if ($assignment->submitted == '0')
                            <a href="{{ route('teacherReadingAssignments.edit', $assignment->id) }}"
                                class="px-4 py-1 border bg-white border-custom-black text-custom-black  hover:text-white hover:bg-custom-black transition-colors rounded-md">Edit</a>
                        @else
                            <a href="{{ route('teacherReadingAssignments.view', $assignment->id) }}"
                                class="px-4 py-1 border border-custom-black bg-custom-black text-white hover:text-custom-black hover:bg-transparent transition-colors rounded-md">View</a>
                        @endif

                        <form action="{{ route('teacherReadingAssignments.destroy', $assignment->id) }}" method="POST"
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