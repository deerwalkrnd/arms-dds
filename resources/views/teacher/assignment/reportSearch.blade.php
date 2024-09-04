@extends('layouts.teacher.app')

@section('Page Title', 'CAS Report')



@section('title')

    <div>CAS Report of - 
        <span class="text-custom-black font-normal">{{ $subjectTeacher->subject->name }}
            <span class="text-xs">{{ $subjectTeacher->section->name }}</span></span>
    </div>

@endsection

@section('content')
    <div class="w-full flex flex-col gap-2">

        {{-- Form Container --}}
        <div class="pt-3 pb-2 px-2">
            <form method="POST" action="{{ route('teacherCasReport.search') }}">
                @csrf
                @method('POST')

                {{-- Filters for search --}}
                <div class="text-base font-normal flex gap-2">

                    {{-- Subject teacher filter --}}
                    <select name="subjectTeacher" id="subjectTeacher"
                        class="border-2 text-black border-dark-orange px-2 rounded-sm selectSubject text-base">


                        {{-- Place holder option --}}
                        <option value="{{ null }}"  class="bg-white text-black selectOptionSubject">--Select
                            Subject--
                        </option>

                        {{-- Looping through subject teachers --}}
                        @foreach ($subjectTeachers as $subjectTeacherIteration)
                            {{-- @if ($subjectTeacherIteration->id == $subjectTeacher->id) --}}
                                <option value="{{ $subjectTeacherIteration->id }}" class="bg-white text-black"> 
                                    {{ $subjectTeacherIteration->subject->name }} --
                                    {{ $subjectTeacherIteration->section->name }}</option>
                            {{-- @else
                                <option value="{{ $subjectTeacher->id }}" class="bg-white text-black">
                                    {{ $subjectTeacherIteration->subject->name }} --
                                    {{ $subjectTeacherIteration->section->name }}</option>
                            @endif --}}
                        @endforeach


                    </select>

                    {{-- Term Filter --}}
                    <select name="term" id="term"
                        class="border-2 text-black border-dark-orange px-2 rounded-sm selectTerm text-base" disabled>

                        {{-- Select option placeholder --}}
                        <option value="{{ null }}" class="bg-white text-black selectOptionTerm">--Select a subject first--
                        </option>

                        {{-- Loo[ong through terms] --}}
                        @foreach ($terms as $termIteration)
                            {{-- @if ($termIteration->id == $term->id) --}}
                                <option value="{{ $term->id }}" class="bg-white text-black">
                                    {{ $termIteration->name }} --
                                    Grade {{ $termIteration->grade->name }}</option>
                            {{-- @else
                                <option value="{{ $term->id }}" class="bg-white text-black">
                                    {{ $termIteration->name }} --
                                    Grade {{ $termIteration->grade->name }}</option>
                            @endif --}}
                        @endforeach


                    </select>

                    {{-- CAS Type filter --}}
                    <select name="casType" id="casType"
                        class="border-2 text-black border-dark-orange px-2 rounded-sm selectCasType text-base" disabled>


                        <option value="{{ null }}" id="placeholderCasType" class="bg-white text-black selectOptionCasType">
                            --Select a subject first--
                        </option>


                        @foreach ($casTypes as $casType)
                            <option value="{{ $casType->id }}" class="bg-white text-black">
                                {{ $casType->name }} -- {{$casType->full_marks}} -- {{ $casType->school->name }}</option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="px-2 bg-dark-orange text-white rounded-md text-base py-[0.3rem]">Generate</button>
                </div>
            </form>
        </div>


        {{-- Table of student marks --}}
        <div class="border overflow-x-scroll w-full">

            <table class="table-auto w-full">
                <thead class="bg-dark-gray sticky top-0 z-20">
                    <tr class="h-6">

                        <th class="text-left pl-4 w-[35px]">S.No.</th>
                        <th class="text-left w-1/6 pl-4">Names</th>

                        {{-- Looping through cas type names --}}
                        @foreach ($cas as $casType => $assignments)


                            <th colspan="{{ count($assignments) }}" class="border border-neutral-500">{{ $casType }}-{{$casType->weightage}}
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        <th></th>
                        <th></th>

                        {{-- Looping through assignment names --}}
                        @foreach ($cas as $casType => $assignments)
                            @foreach ($assignments as $assignmentJson => $casMarks)
                                <th class="text-xs font-normal text-gray-700 border border-neutral-500">
                                    {{ json_decode($assignmentJson)->name }}</th>
                            @endforeach
                        @endforeach

                    </tr>
                </thead>

                <tbody class="text-sm">

                    @foreach ($students as $student)
                        <tr class="h-8 hover:bg-dark-gray">

                            <td class="pl-4 w-[35px]">{{ $student->roll_number }}</td>

                            <td class="w-1/6 pl-4">{{ $student->name }}</td>


                            {{-- Looping through casTypes, assignments, casMarks and mapping with student ids --}}
                            @foreach ($cas as $casType => $assignments)
                                @foreach ($assignments as $assignmentName => $casMarks)
                                    @foreach ($casMarks as $casMark)
                                        @if ($casMark->student_id == $student->id)
                                            <td class="text-center border-x border-neutral-500">{{ $casMark->mark }}</td>
                                        @endif
                                    @endforeach
                                @endforeach
                            @endforeach

                        </tr>
                    @endforeach
                </tbody>


            </table>
        </div>

    </div>
@endsection


@section('script')
    <script>
        $(".selectTerm").change(function() {
            $(".selectOptionTerm").hide();
        })

        $(".selectSubject").change(function() {
            $(".selectOptionSubject").hide();
        })
    </script>
    <script>
        $(document).ready(function() {
            $('#subjectTeacher').select2({});
            $('#term').select2({});
            $('#casType').select2({});
        });
    </script>
    <script>
        $(document).ready(function() {
            var selectedSubjectTeacherId;
            $('#subjectTeacher').change(function() {
                $("option[id='placeholderSubject']").remove();
                $("#term").find('option').remove().end();
                $("#term").prop('disabled', true);
                $("#casType").find('option').remove().end();
                $("#casType").prop('disabled', true);
                selectedSubjectTeacherId = $("#subjectTeacher").find(":selected").val();
                console.log(selectedSubjectTeacherId);
                appendOption(null, "--Choose a term--", "term");
                appendOption(null, "--Choose a CAS type--", "casType");

                if(selectedSubjectTeacherId){

                    $.ajax({
                        type: "POST",
                        url: '{{ route('teacherCasReport.populateTerm') }}',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            "subjectTeacherId": selectedSubjectTeacherId
                        },
                        success: function(data) {
                            let terms = JSON.parse(data);
                            console.log(terms);
                            $("#term").prop('disabled', false);
                            $("#placeholderTerm").hide();
                            terms.map((term) => {
                                appendOption(term.id, `${term.name} -- Grade ${term.grade}`,
                                    "term");
                            });
                        },
                        error: function(xhr, status, text) {
    
                            console.error(text);
                        },
                    });
    
    
                    $.ajax({
                        type: "POST",
                        url: '{{ route('teacherCasReport.populateCasType') }}',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            "subjectTeacherId": selectedSubjectTeacherId
                        },
                        success: function(data) {
                            let casTypes = JSON.parse(data);
                            console.log(casTypes);
                            $("#casType").prop('disabled', false);
                            $("#placeholderCasType").hide();
                            casTypes.map((casType) => {
                                appendOption(casType.id, `${casType.name} -- ${casType.schoolName}`,
                                    "casType");
                            });
                        },
                        error: function(xhr, status, text) {
    
                            console.error(text);
                        },
                    });
                }

                

            });
        });
    
        function appendOption(value, label, selectId) {
            let optionHtml = `<option value="${value}" class="bg-white text-black">${label}</option>`;
            $(`#${selectId}`).append(optionHtml);
        }

    </script>
@endsection