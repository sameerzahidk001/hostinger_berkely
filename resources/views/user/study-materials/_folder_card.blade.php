@php
    $folder = $access->folder;
    $expired = $access->access_till
        && $access->access_till->toDateString() < now()->toDateString();
    // Open when access is active and not expired (folder status alone must not grey the card).
    $accessDisabled = ! $folder
        || $access->status !== 'active'
        || $expired;
    $disabledLabel = 'Access Ended';
@endphp
<div class="col-md-6 col-lg-4">
    <div class="ibox sm-folder-card"@if($accessDisabled) style="border:1px solid #c5c5c5;"@endif>
        <div class="ibox-title" style="background:{{ $accessDisabled ? '#6b7280' : '#000435' }};color:#fff;">
            <h5>{{ $folder->name }}</h5>
        </div>
        <div class="ibox-content"@if($accessDisabled) style="background:#ececec;color:#4b5563;"@endif>
            <div class="sm-folder-card-body">
                <p style="margin-bottom:6px;">
                    Course:
                    @include('partials.course_link', ['course' => $folder->course ?? null])
                </p>
                @php
                    $headOfFaculty = $folder?->displayHeadOfFaculty();
                    $trainerInstructors = $folder ? $folder->displayTrainerInstructors() : collect();
                @endphp
                <p style="margin-bottom:6px;">
                    Instructor:
                    @forelse($trainerInstructors as $primaryInstructor)
                        <a href="{{ url('/instructor/' . $primaryInstructor->id) }}" target="_blank" rel="noopener"><strong>{{ $primaryInstructor->name }}</strong></a>@if(! $loop->last), @endif
                    @empty
                        —
                    @endforelse
                </p>
                <p style="margin-bottom:6px;">
                    Head of Faculty:
                    @if($headOfFaculty)
                        <a href="{{ url('/instructor/' . $headOfFaculty->id) }}" target="_blank" rel="noopener"><strong>{{ $headOfFaculty->name }}</strong></a>
                    @else
                        —
                    @endif
                </p>
                <p style="margin-bottom:6px;">Access Start: {{ optional($access->issued_at)->format('d M Y') ?: '—' }}</p>
                <p style="margin-bottom:10px;" class="text-muted">Access Expire: {{ $access->access_till ? $access->access_till->format('d M Y') : 'No expiry' }}</p>
            </div>
            <div class="sm-folder-card-footer">
                @if($accessDisabled)
                    <button type="button" class="btn btn-sm" disabled style="background:#9ca3af;border-color:#9ca3af;color:#fff;cursor:not-allowed;align-self:flex-start;">{{ $disabledLabel }}</button>
                    <p class="sm-folder-card-contact">
                        contact <a href="mailto:admin@eduberkeley.com">admin@eduberkeley.com</a>
                    </p>
                @else
                    @php
                        $lms = $lms ?? app(\App\Services\StudyMaterialService::class);
                        $canManageFolder = $lms->isInstructorActor() && $lms->canManageFolder($folder);
                        $canAssignFolder = $canManageFolder && $lms->canAssignStudentAccess($folder);
                    @endphp
                    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                        <a class="btn btn-primary btn-sm" href="{{ route('user.study-materials.show', $folder->id) }}" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Open Now</a>
                        @if($canManageFolder)
                            <a class="btn btn-sm btn-default" href="{{ route('admin.study-materials.folders.edit', $folder->id) }}">Edit Folder</a>
                        @endif
                        @if($canAssignFolder)
                            <a class="btn btn-sm btn-success" href="{{ route('admin.study-materials.access.assign-student', ['folder_id' => $folder->id]) }}">Assign Access</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
