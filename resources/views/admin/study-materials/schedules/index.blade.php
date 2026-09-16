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
        <div class="ibox-title"><h5>Batch list</h5></div>
        <div class="ibox-content">
            <p class="help-block">Batches grouped from class schedules. Each batch shows the selected instructor, sessions, and students.</p>
            @forelse($batches as $batch)
                <div class="panel panel-default" style="margin-bottom:16px;">
                    <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                        <div>
                            <strong style="font-size:16px;">{{ $batch['batch_name'] }}</strong>
                            <div class="text-muted" style="margin-top:2px;">
                                {{ $batch['course']->title ?? 'No course' }}
                                · Instructor: <strong>{{ $batch['instructor']->name ?? '—' }}</strong>
                                · {{ $batch['students']->count() }} student{{ $batch['students']->count() === 1 ? '' : 's' }}
                            </div>
                        </div>
                        @if(!empty($batch['primary']))
                            <a class="btn btn-xs btn-primary" href="{{ route('admin.class-schedules.edit', $batch['primary']->id) }}">Edit schedule</a>
                        @endif
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 style="margin-top:0;">Schedule</h5>
                                <ul class="list-unstyled" style="margin-bottom:0;">
                                    @foreach($batch['sessions'] as $session)
                                        <li style="padding:8px 0;border-bottom:1px solid #f0f0f0;">
                                            <strong>{{ $session->scheduled_at?->format('d M Y H:i') }}</strong>
                                            · {{ $session->durationMinutes() }} min
                                            · {{ ucfirst($session->status) }}
                                            @if($session->title && $session->title !== $batch['batch_name'])
                                                · {{ $session->title }}
                                            @endif
                                            <div style="margin-top:6px;">
                                                @if($session->zoho_link)
                                                    <a href="{{ $session->zoho_link }}" target="_blank" rel="noopener"
                                                       style="font-weight:700;color:#1ab394;">Join Now</a>
                                                    <span class="text-muted"> · </span>
                                                    <a href="{{ $session->zoho_link }}" target="_blank" rel="noopener"
                                                       style="word-break:break-all;">{{ $session->zoho_link }}</a>
                                                @else
                                                    <span class="text-muted">No meeting link yet</span>
                                                @endif
                                                <span class="text-muted"> · </span>
                                                <a href="{{ route('admin.class-schedules.edit', $session->id) }}">Edit</a>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5 style="margin-top:0;">Students</h5>
                                @if($batch['students']->isEmpty())
                                    <p class="text-muted" style="margin-bottom:0;">No students assigned to this batch yet.</p>
                                @else
                                    <ul class="list-unstyled" style="margin-bottom:0;">
                                        @foreach($batch['students'] as $student)
                                            <li style="padding:6px 0;border-bottom:1px solid #f0f0f0;">
                                                <strong>{{ $student->name }}</strong>
                                                <div class="text-muted">{{ $student->email }}</div>
                                                <div class="text-muted" style="font-size:12px;margin-top:2px;">
                                                    Batch schedule:
                                                    {{ $batch['sessions']->pluck('scheduled_at')->filter()->map(fn ($d) => $d->format('d M Y H:i'))->implode(', ') ?: '—' }}
                                                    · Instructor: {{ $batch['instructor']->name ?? '—' }}
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted" style="margin-bottom:0;">No batches yet. Create a class schedule to start a batch list.</p>
            @endforelse
        </div>
    </div>

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
                                    <a href="{{ $row->zoho_link }}" target="_blank" rel="noopener" style="font-weight:700;">Join Now</a>
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
