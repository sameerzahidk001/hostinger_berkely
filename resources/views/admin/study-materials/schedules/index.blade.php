@extends('admin.layout.app')
@section('title', 'Class Schedules')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Class Schedules</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Schedules</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.class-batches.index') }}" class="btn btn-default">Batches</a>
        <a href="{{ route('admin.class-schedules.feed') }}" class="btn btn-default">Add to Zoho Calendar (.ics)</a>
        <a href="{{ route('admin.class-schedules.create') }}" class="btn btn-primary">Create Schedule</a>
        @if($isAdmin ?? Auth::guard('admin')->check())
            <a href="{{ route('admin.class-batches.create') }}" class="btn btn-primary">Create Batch</a>
        @endif
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <div class="ibox">
        <div class="ibox-title"><h5>Select a batch</h5></div>
        <div class="ibox-content">
            <p class="help-block" style="margin-top:0;">
                Open a batch to view / edit its session schedule.
                @unless($isAdmin ?? false)
                    You can create and edit schedules for batches assigned to you, and add students on each session.
                @endunless
            </p>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Batch code</th>
                            <th>Batch name</th>
                            <th>Course</th>
                            <th>Head of Faculty</th>
                            <th>Instructor</th>
                            <th>Students</th>
                            <th>Sessions</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batchList as $b)
                            <tr>
                                <td><span class="label label-primary">{{ $b->code }}</span></td>
                                <td><strong>{{ $b->name }}</strong></td>
                                <td>
                                    @if($b->course)
                                        <a href="{{ url('/course/' . ($b->course->slug ?: $b->course->id)) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">{{ $b->course->title }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($b->headOfFaculty)
                                        <a href="{{ url('/instructor/' . $b->headOfFaculty->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">{{ $b->headOfFaculty->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @php $ins = $b->instructors->first(); @endphp
                                    @if($ins)
                                        <a href="{{ url('/instructor/' . $ins->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">{{ $ins->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $b->students_count }}</td>
                                <td>{{ $b->schedules_count }}</td>
                                <td>
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.class-schedules.batch', $b->id) }}">Open schedule</a>
                                    <a class="btn btn-xs btn-default" href="{{ route('admin.class-schedules.create', ['batch_id' => $b->id]) }}">Add session</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No batches yet. Create a batch first, then add sessions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>Calendar</h5></div>
        <div class="ibox-content">
            @include('admin.study-materials.schedules._calendar', ['calendarEvents' => $calendarEvents])
            @if(Auth::guard('admin')->check())
                <hr>
                <h4>Zoho Calendar embed (optional)</h4>
                <form method="POST" action="{{ route('admin.class-schedules.zoho-embed') }}">
                    @csrf
                    <div class="form-group">
                        <label>Paste Zoho Calendar embed URL or iframe code</label>
                        <textarea name="zoho_calendar_embed_url" class="form-control" rows="3" placeholder="https://calendar.zoho.com/...">{{ $zohoEmbed }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save embed</button>
                </form>
                @if($zohoEmbed)
                    <div style="margin-top:16px;">
                        <iframe src="{{ $zohoEmbed }}" style="width:100%;min-height:520px;border:1px solid #e7eaec;" title="Zoho Calendar"></iframe>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
