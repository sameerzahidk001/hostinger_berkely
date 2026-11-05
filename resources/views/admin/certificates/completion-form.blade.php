@extends('admin.layout.app')
@section('title', 'Mark complete')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Mark student complete</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.certificates.completions.index') }}">Completions</a></li>
            <li class="active"><strong>Mark complete</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.certificates.completions.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Student *</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">Select student</option>
                            @foreach($students as $student)
                                @php
                                    $label = $student->name ?: trim(($student->first_name ?? '').' '.($student->last_name ?? ''));
                                    if ($label === '') { $label = 'User #'.$student->id; }
                                @endphp
                                <option value="{{ $student->id }}" @selected((string)old('user_id') === (string)$student->id)>
                                    {{ $label }} @if($student->email) ({{ $student->email }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course *</label>
                        <select name="course_id" class="form-control" required>
                            <option value="">Select course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected((string)old('course_id') === (string)$course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Completion date</label>
                        <input type="date" name="completed_at" class="form-control" value="{{ old('completed_at', now()->toDateString()) }}">
                    </div>
                    <div class="col-md-8 form-group">
                        <label>Notes (internal)</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" maxlength="2000">
                    </div>
                    <div class="col-md-12 form-group">
                        <label class="checkbox-inline" style="display:block;margin-bottom:8px;">
                            <input type="checkbox" name="issue_now" value="1" @checked(old('issue_now', true))>
                            Also issue certificate now (PDF + QR)
                        </label>
                        <label class="checkbox-inline" style="display:block;">
                            <input type="checkbox" name="send_email" value="1" @checked(old('send_email', true))>
                            Email PDF to the student
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.certificates.completions.index') }}" class="btn btn-white">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
