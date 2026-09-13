@php
    $folder = $access->folder;
    $instructors = $folder ? $folder->displayInstructors() : collect();
    $accessDisabled = !$folder || $folder->status !== 'active' || $access->status !== 'active';
@endphp
<div class="col-md-6 col-lg-4">
    <div class="ibox" @if($accessDisabled) style="border:1px solid #c5c5c5;" @endif>
        <div class="ibox-title" style="background:{{ $accessDisabled ? '#6b7280' : '#000435' }};color:#fff;">
            <h5 style="color:#fff;">{{ $folder->name }}</h5>
        </div>
        <div class="ibox-content" @if($accessDisabled) style="background:#ececec;color:#4b5563;" @endif>
            <p>
                Course:
                @if($folder->course)
                    <a href="{{ url('/' . ($folder->course->slug ?? $folder->course_id)) }}" target="_blank" rel="noopener"><strong>{{ $folder->course->title }}</strong></a>
                @else — @endif
            </p>
            <p>
                Instructor:
                @if($instructors->isEmpty())
                    —
                @else
                    @foreach($instructors as $instructor)
                        <a href="{{ url('/instructor/' . $instructor->id) }}" target="_blank" rel="noopener"><strong>{{ $instructor->name }}</strong></a>@if(!$loop->last), @endif
                    @endforeach
                @endif
            </p>
            <p style="margin-bottom:4px;">Access Start: {{ optional($access->issued_at)->format('d M Y') ?: '—' }}</p>
            <p class="text-muted">Access Expire: {{ $access->access_till ? $access->access_till->format('d M Y') : 'No expiry' }}</p>
            @if($accessDisabled)
                <button type="button" class="btn btn-sm" disabled style="background:#9ca3af;border-color:#9ca3af;color:#fff;cursor:not-allowed;">Access is disabled.</button>
                <p style="margin-top:10px;margin-bottom:0;">
                    contact <a href="mailto:admin@eduberkeley.com">admin@eduberkeley.com</a>
                </p>
            @else
                <a class="btn btn-primary btn-sm" href="{{ route('user.study-materials.show', $folder->id) }}" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Open folder</a>
            @endif
        </div>
    </div>
</div>
