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
        <div class="ibox-title"><h5>Schedule</h5></div>
        <div class="ibox-content">
            <ul class="nav nav-tabs" role="tablist" style="margin-bottom:20px;">
                <li class="active" role="presentation">
                    <a href="#schedule-batches" aria-controls="schedule-batches" role="tab" data-toggle="tab">Sessions by batch</a>
                </li>
                <li role="presentation">
                    <a href="#schedule-calendar-tab" aria-controls="schedule-calendar-tab" role="tab" data-toggle="tab">Calendar</a>
                </li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="schedule-batches">
                    <p class="help-block">
                        Manage batch master data under <a href="{{ route('admin.class-batches.index') }}"><strong>Batches</strong></a>.
                        Below: scheduled sessions grouped by batch.
                    </p>
                    @forelse($batches as $batch)
                        <div class="panel panel-default" style="margin-bottom:18px;">
                            <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                                <div>
                                    <strong style="font-size:16px;">
                                        @if(!empty($batch['batch_code']))
                                            <span class="label label-primary">{{ $batch['batch_code'] }}</span>
                                        @endif
                                        {{ $batch['batch_name'] }}
                                    </strong>
                                    <div class="text-muted" style="margin-top:2px;">
                                        {{ $batch['course']->title ?? 'No course' }}
                                        · Head of Faculty:
                                        @php $hof = $batch['head_of_faculty'] ?? null; @endphp
                                        @if($hof && !empty($hof->id))
                                            <a href="{{ url('/instructor/' . $hof->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $hof->name }}</strong></a>
                                        @else
                                            <strong>—</strong>
                                        @endif
                                        · Instructor:
                                        @php $ins = $batch['instructor'] ?? null; @endphp
                                        @if($ins && !empty($ins->id))
                                            <a href="{{ url('/instructor/' . $ins->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $ins->name }}</strong></a>
                                        @else
                                            <strong>—</strong>
                                        @endif
                                        ·
                                        @if(!empty($batch['batch_id']))
                                            <a href="{{ route('admin.class-batches.edit', $batch['batch_id']) }}#student_ids" style="color:#1c84c6;text-decoration:underline;">
                                                <strong>{{ $batch['students']->count() }} student{{ $batch['students']->count() === 1 ? '' : 's' }}</strong>
                                            </a>
                                        @else
                                            <strong>{{ $batch['students']->count() }} student{{ $batch['students']->count() === 1 ? '' : 's' }}</strong>
                                        @endif
                                    </div>
                                </div>
                                @if(!empty($batch['primary']))
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.class-schedules.edit', $batch['primary']->id) }}">Edit schedule</a>
                                @endif
                            </div>
                            <div class="panel-body" style="padding:0;">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered" style="margin-bottom:0;">
                                        <thead>
                                            <tr>
                                                <th>When</th>
                                                <th>Duration</th>
                                                <th>Title</th>
                                                <th>Description</th>
                                                <th>Students</th>
                                                <th>Meeting link</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($batch['sessions'] as $session)
                                                <tr>
                                                    <td>{{ $session->scheduled_at?->format('d M Y H:i') }}</td>
                                                    <td>{{ $session->durationMinutes() }} min</td>
                                                    <td>{{ $session->title && $session->title !== $batch['batch_name'] ? $session->title : '—' }}</td>
                                                    <td>{{ \Illuminate\Support\Str::limit($session->notes ?: '—', 60) }}</td>
                                                    <td>{{ $session->students->count() }}</td>
                                                    <td>
                                                        @if($session->zoho_link)
                                                            <a href="{{ $session->zoho_link }}" target="_blank" rel="noopener" style="font-weight:700;">Join Now</a>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>{{ ucfirst($session->status) }}</td>
                                                    <td>
                                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.class-schedules.edit', $session->id) }}">Edit</a>
                                                        <a class="btn btn-xs btn-default" href="{{ route('admin.class-schedules.ics', $session->id) }}">.ics</a>
                                                        <form action="{{ route('admin.class-schedules.destroy', $session->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this scheduled day?');">
                                                            @csrf @method('DELETE')
                                                            <button class="btn btn-xs btn-danger" type="submit">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if(!$batch['students']->isEmpty())
                                    <div style="padding:12px 15px;border-top:1px solid #e7eaec;">
                                        <strong style="display:block;margin-bottom:8px;">Students in this batch</strong>
                                        <div class="table-responsive">
                                            <table class="table table-condensed" style="margin-bottom:0;">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($batch['students'] as $student)
                                                        <tr>
                                                            <td>{{ $student->name }}</td>
                                                            <td>{{ $student->email }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted" style="margin-bottom:0;">No batches yet. Create a class schedule to start a batch list.</p>
                    @endforelse
                </div>

                <div role="tabpanel" class="tab-pane" id="schedule-calendar-tab">
                    <p class="help-block">Classes appear on this calendar. After Zoho OAuth is connected, new classes are also created as Zoho Calendar events. You can still download the .ics file and import/subscribe it in Zoho Calendar.</p>
                    @include('admin.study-materials.schedules._calendar', ['calendarEvents' => $calendarEvents])

                    @if(Auth::guard('admin')->check())
                        <hr>
                        <h4>Zoho Calendar embed (optional)</h4>
                        <form method="POST" action="{{ route('admin.class-schedules.zoho-embed') }}">
                            @csrf
                            <div class="form-group">
                                <label>Paste Zoho Calendar embed URL or iframe code</label>
                                <textarea name="zoho_calendar_embed_url" class="form-control" rows="3" placeholder="https://calendar.zoho.com/...">{{ $zohoEmbed }}</textarea>
                                <span class="help-block">
                                    Optional: show a live Zoho Calendar iframe on this Class Schedule admin page only
                                    (not the public Training Calendar). In Zoho Calendar: settings → Public access → Embed Calendar → paste URL/iframe here.
                                </span>
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
    </div>
</div>
@endsection
