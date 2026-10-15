@extends(lms_portal_layout())
@section('title', 'Import MCQ')
@section('content')
@include('admin.assessments.partials.searchable-select-assets')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Import MCQ from Excel / CSV</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.questions.index') }}">Question Bank</a></li>
            <li class="active"><strong>Import</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="ibox">
        <div class="ibox-content">
            <p>Upload a <strong>.csv</strong> file (Excel → Save As → CSV). Columns:</p>
            <pre style="background:#f5f5f5;padding:10px;">topic,stem,option_a,option_b,option_c,option_d,option_e,correct,max_marks,course_id
Unit 1,"What is 2+2?",2,3,4,5,6,C,1,
</pre>
            <p class="text-muted">
                <code>correct</code> = A/B/C/D/E (or 1–5). <code>option_e</code> is optional (5th answer). Leave <code>course_id</code> blank for no course.
                Download a blank template: <a href="{{ route('admin.assessments.questions.import-template') }}">mcq-import-template.csv</a>
            </p>
            <form method="POST" action="{{ route('admin.assessments.questions.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Default course (optional, used when course_id column is empty)</label>
                    <select name="course_id" class="form-control lms-searchable" data-placeholder="Type to find course">
                        <option value="">—</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>CSV file *</label>
                    <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                </div>
                <button type="submit" class="btn btn-primary">Import MCQs</button>
                <a href="{{ route('admin.assessments.questions.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
