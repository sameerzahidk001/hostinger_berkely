@extends('layouts.app')

@section('content')
<div class="px-6 min-[1200px]:px-[72px] py-10">
    <h1 class="text-3xl font-bold mb-6">All programmes</h1>
    <ul class="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
        @foreach($courses as $course)
            <li>
                <a class="underline" href="{{ route('course.details', $course->slug) }}">{{ $course->title }}</a>
            </li>
        @endforeach
    </ul>
</div>
@endsection
