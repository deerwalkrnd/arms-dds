@extends('layouts.admin.app')

@section('Page Title', 'Edit Assignment')

@section('title')
    <div class="flex flex-col">
       Select School
    </div>
@endsection

@section('content')
<div class="px-14 py-6 flex flex-wrap gap-x-14 gap-y-5 items-center justify-center w-full">
    <form action="{{route('adminAssignments.schoolSelect')}}" method="POST">
        @csrf
        <select name="school" id="">
            @foreach ($schools as $school)
                <option value="{{$school->id}}">{{$school->name}}</option>
            @endforeach
        </select>
        <button>submit</button>
    </form>
</div>
@endsection


