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
        <a href="{{ route('admin.class-schedules.feed') }}" class="btn btn-default">Add to Zoho Calendar (.ics)</a>
        <a href="{{ route('admin.class-schedules.create') }}" class="btn btn-primary">Create Schedule</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <div class="ibox">
        <div class="ibox-title"><h5>Schedule calendar</h5></div>
        <div class="ibox-content">
            <p class="help-block">Classes created here appear on this calendar. After Zoho OAuth is connected, new classes are also created as Zoho Calendar events. You can still download the .ics file and import/subscribe it in Zoho Calendar.</p>
            @include('admin.study-materials.schedules._calendar', ['calendarEvents' => $calendarEvents])
        </div>
    </div>

    @if(Auth::guard('admin')->check())
    <div class="ibox">
        <div class="ibox-title"><h5>Zoho Calendar embed (optional)</h5></div>
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.class-schedules.zoho-embed') }}">
                @csrf
                <div class="form-group">
                    <label>Paste Zoho Calendar embed URL or iframe code</label>
                    <textarea name="zoho_calendar_embed_url" class="form-control" rows="3" placeholder="https://calendar.zoho.com/...">{{ $zohoEmbed }}</textarea>
                    <span class="help-block">
                        In Zoho Calendar: calendar settings → Public access → Embed Calendar → copy the URL or iframe.
                    </span>
                </div>
                <button type="submit" class="btn btn-primary">Save embed</button>
            </form>
            @if($zohoEmbed)
                <div style="margin-top:16px;">
                    <iframe src="{{ $zohoEmbed }}" style="width:100%;min-height:520px;border:1px solid #e7eaec;" title="Zoho Calendar"></iframe>
                </div>
            @endif
        </div>
    </div>
    @endif

    <div class="ibox">
        <div class="ibox-title"><h5>All classes</h5></div>
        <div class="ibox-content table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Batch</th><th>Course</th><th>Instructor</th><th>When</th><th>Students</th><th>Zoho Meeting</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $row)
                        <tr>
                            <td>{{ $row->batch_name }}</td>
                            <td>{{ $row->course->title ?? '—' }}</td>
                            <td>{{ $row->instructor->name ?? '—' }}</td>
                            <td>{{ $row->scheduled_at?->format('d M Y H:i') }}</td>
                            <td>{{ $row->students->count() }}</td>
                            <td>
                                @if($row->zoho_link)
                                    <a href="{{ $row->zoho_link }}" target="_blank" rel="noopener">Open</a>
                                @else — @endif
                            </td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td>
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.class-schedules.edit', $row->id) }}">Edit</a>
                                <a class="btn btn-xs btn-default" href="{{ route('admin.class-schedules.ics', $row->id) }}">.ics</a>
                                @if(Auth::guard('admin')->check())
                                <form action="{{ route('admin.class-schedules.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-danger" type="submit">Delete</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No schedules yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $schedules->links() }}
        </div>
    </div>
</div>
@endsection
