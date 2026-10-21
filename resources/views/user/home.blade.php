@extends('user.layout.app')
@section('title', 'Dashboard')

@push('style')
<style>
    .wrapper-content { padding-right: 20px; }
</style>
@include('user.payments._admission_styles')
@endpush

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(!empty($isInstructor) || ($scheduleBatches ?? collect())->isNotEmpty())
        <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0;">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ibox">
                        <div class="ibox-title" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                            <h5 style="margin:0;">Class Schedule</h5>
                            <div>
                                @if(!empty($isInstructor))
                                    <a href="{{ route('admin.class-batches.index') }}" class="btn btn-xs btn-default">Batches</a>
                                    <a href="{{ route('user.class-schedules.index') }}" class="btn btn-xs btn-default">Schedules</a>
                                    <a href="{{ route('admin.class-schedules.create') }}" class="btn btn-xs btn-primary">Add session</a>
                                @else
                                    <a href="{{ route('user.class-schedules.index') }}" class="btn btn-xs btn-default">Schedules</a>
                                @endif
                            </div>
                        </div>
                        <div class="ibox-content">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" style="margin-bottom:0;">
                                    <thead>
                                        <tr>
                                            <th>Batch</th>
                                            <th>Course</th>
                                            <th>Students</th>
                                            <th>Sessions</th>
                                            <th>Next class</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse(($scheduleBatches ?? collect()) as $batch)
                                            @php
                                                $course = $batch['course'] ?? null;
                                                $openKey = $batch['batch_id'] ?: ($batch['key'] ?? '');
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if(!empty($batch['batch_code']))
                                                        <span class="label label-primary">{{ $batch['batch_code'] }}</span>
                                                    @endif
                                                    <strong>{{ $batch['batch_name'] }}</strong>
                                                </td>
                                                <td>
                                                    @include('partials.course_link', ['course' => $course])
                                                </td>
                                                <td>{{ $batch['student_count'] ?? 0 }}</td>
                                                <td>{{ $batch['session_count'] }}</td>
                                                <td>
                                                    @if(!empty($batch['next_at']))
                                                        {{ $batch['next_at']->format('d M Y H:i') }}
                                                        @if(!empty($batch['next_timezone']))
                                                            <small class="text-muted">({{ $batch['next_timezone'] }})</small>
                                                        @endif
                                                    @else
                                                        No upcoming
                                                    @endif
                                                </td>
                                                <td>
                                                    <a class="btn btn-primary btn-sm" href="{{ route('user.class-schedules.batch', $openKey) }}" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;font-weight:700;">Open schedule</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No class schedules assigned yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(isset($courseAccesses) && $courseAccesses->isNotEmpty())
        @include('user.study-materials._folder_cards_styles')
        <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0;">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ibox">
                        <div class="ibox-title" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                            <h5 style="margin:0;">Course Access</h5>
                            <div>
                                @if(!empty($isInstructor))
                                    <a href="{{ route('admin.study-materials.folders.index') }}" class="btn btn-xs btn-default">Manage Folders</a>
                                    @if(app(\App\Services\StudyMaterialService::class)->canGrantStudentAccess())
                                    <a href="{{ route('admin.study-materials.access.assign-student') }}" class="btn btn-xs btn-success">Assign Access</a>
                                    @endif
                                @else
                                    <a href="{{ route('user.study-materials.index') }}" class="btn btn-xs btn-default">All Study Materials</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sm-folder-cards-row">
                @foreach($courseAccesses as $access)
                    @include('user.study-materials._folder_card', ['access' => $access])
                @endforeach
            </div>
        </div>
    @elseif(!empty($isInstructor))
        <div class="wrapper wrapper-content animated fadeInRight">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ibox">
                        <div class="ibox-title"><h5 style="margin:0;">Course Access</h5></div>
                        <div class="ibox-content text-center text-muted">
                            No folders assigned yet. When admin assigns you folder access, it will appear here.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(empty($isInstructor) && auth()->user()->hasPermission('installment-list'))
        <div class="wrapper wrapper-content animated fadeInRight">
            <div class="row">
                <div class="col-lg-12">
                    @include('user.payments._admission_table')
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script')
    @if(auth()->user()->hasPermission('installment-list'))
        @include('user.payments._admission_scripts')
    @endif
@endpush
