@php
    $folder = $access->folder;
    $instructors = $folder ? $folder->displayInstructors() : collect();
    $expired = $access->access_till
        && $access->access_till->toDateString() < now()->toDateString();
    // Open when access is active and not expired (folder status alone must not grey the card).
    $accessDisabled = ! $folder
        || $access->status !== 'active'
        || $expired;
    $disabledLabel = 'Access Ended';
@endphp
<div class="col-md-6 col-lg-4" style="display:flex;">
    <div class="ibox sm-folder-card" style="width:100%;display:flex;flex-direction:column;@if($accessDisabled) border:1px solid #c5c5c5;@endif">
        <div class="ibox-title" style="background:{{ $accessDisabled ? '#6b7280' : '#000435' }};color:#fff;min-height:48px;">
            <h5 style="color:#fff;margin:0;line-height:1.3;">{{ $folder->name }}</h5>
        </div>
        <div class="ibox-content" style="flex:1;display:flex;flex-direction:column;@if($accessDisabled) background:#ececec;color:#4b5563;@endif">
            <div style="flex:1;">
                <p style="margin-bottom:6px;">
                    Course:
                    @if($folder->course)
                        <a href="{{ route('course.details', ['course' => $folder->course->slug ?? $folder->course_id]) }}" target="_blank" rel="noopener"><strong>{{ $folder->course->title }}</strong></a>
                    @else — @endif
                </p>
                @php
                    $courseInstructorIds = course_instructor_ids($folder->course ?? null);
                    $headOfFaculty = $instructors->firstWhere('id', $courseInstructorIds[0] ?? null) ?: ($instructors->count() > 1 ? $instructors->get(0) : null);
                    $primaryInstructor = $instructors->firstWhere('id', $courseInstructorIds[1] ?? null)
                        ?: ($instructors->count() > 1 ? $instructors->get(1) : $instructors->first());
                @endphp
                <p style="margin-bottom:6px;">
                    Head of Faculty:
                    @if($headOfFaculty)
                        <a href="{{ url('/instructor/' . $headOfFaculty->id) }}" target="_blank" rel="noopener"><strong>{{ $headOfFaculty->name }}</strong></a>
                    @else
                        —
                    @endif
                </p>
                <p style="margin-bottom:6px;">
                    Instructor:
                    @if($primaryInstructor)
                        <a href="{{ url('/instructor/' . $primaryInstructor->id) }}" target="_blank" rel="noopener"><strong>{{ $primaryInstructor->name }}</strong></a>
                    @else
                        —
                    @endif
                </p>
                <p style="margin-bottom:6px;">Access Start: {{ optional($access->issued_at)->format('d M Y') ?: '—' }}</p>
                <p style="margin-bottom:10px;" class="text-muted">Access Expire: {{ $access->access_till ? $access->access_till->format('d M Y') : 'No expiry' }}</p>
            </div>
            <div style="margin-top:auto;">
                @if($accessDisabled)
                    <button type="button" class="btn btn-sm" disabled style="background:#9ca3af;border-color:#9ca3af;color:#fff;cursor:not-allowed;">{{ $disabledLabel }}</button>
                    <p style="margin-top:10px;margin-bottom:0;">
                        contact <a href="mailto:admin@eduberkeley.com">admin@eduberkeley.com</a>
                    </p>
                @else
                    <a class="btn btn-primary btn-sm" href="{{ route('user.study-materials.show', $folder->id) }}" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Open Now</a>
                @endif
            </div>
        </div>
    </div>
</div>
