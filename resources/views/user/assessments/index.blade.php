@extends('user.layout.app')
@section('title', $section === 'mocks' ? 'Mock Exams' : 'Assessments')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $section === 'mocks' ? 'Mock Exams' : ($section === 'skill' ? 'Skill Tests' : 'Assessments') }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Home</a></li>
            <li class="active"><strong>{{ ucfirst($section === 'all' ? 'Assessments' : $section) }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <ul class="nav nav-tabs" style="margin-bottom:15px;">
        @if(!$isInstructor)
            <li class="{{ $section === 'all' ? 'active' : '' }}"><a href="{{ route('user.assessments.index', ['section' => 'all', 'tab' => $tab]) }}">All</a></li>
            <li class="{{ $section === 'mocks' ? 'active' : '' }}"><a href="{{ route('user.assessments.mocks', ['tab' => $tab]) }}">Mock Exams</a></li>
            <li class="{{ $section === 'practice' ? 'active' : '' }}"><a href="{{ route('user.assessments.index', ['section' => 'practice', 'tab' => $tab]) }}">Practice / General</a></li>
        @else
            <li class="{{ $section === 'skill' || $section === 'all' ? 'active' : '' }}"><a href="{{ route('user.assessments.index', ['section' => 'skill', 'tab' => $tab]) }}">Skill tests</a></li>
        @endif
    </ul>

    <ul class="nav nav-pills" style="margin-bottom:15px;">
        <li class="{{ $tab === 'upcoming' ? 'active' : '' }}">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'upcoming']) }}">Upcoming ({{ $counts['upcoming'] }})</a>
        </li>
        <li class="{{ $tab === 'in_progress' ? 'active' : '' }}">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'in_progress']) }}">In progress ({{ $counts['in_progress'] }})</a>
        </li>
        <li class="{{ $tab === 'submitted' ? 'active' : '' }}">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'submitted']) }}">Submitted ({{ $counts['submitted'] }})</a>
        </li>
        <li class="{{ $tab === 'results' ? 'active' : '' }}">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'results']) }}">Results ({{ $counts['results'] }})</a>
        </li>
    </ul>

    <div class="ibox"><div class="ibox-content table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Window</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($list as $row)
                    @php
                        $a = $row['assessment'];
                        $asg = $row['assignment'];
                        $att = $row['attempt'] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $a->title }}</td>
                        <td>{{ $a->kindLabel() }}</td>
                        <td>{{ $a->duration_minutes ? $a->duration_minutes.' min' : '—' }}</td>
                        <td>
                            @if($asg)
                                {{ optional($asg->available_from)->format('d M Y H:i') ?? 'Open' }}
                                —
                                {{ optional($asg->available_until)->format('d M Y H:i') ?? 'No end' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($att)
                                {{ $att->statusLabel() }}
                                @if($att->results_published && $att->score_raw !== null)
                                    · {{ $att->score_raw }}/{{ $att->score_max }} · {{ $att->passFailLabel() }}
                                @endif
                            @else
                                Upcoming
                            @endif
                        </td>
                        <td>
                            @if($tab === 'upcoming')
                                <a class="btn btn-xs btn-primary" href="{{ route('user.assessments.start', $a->id) }}">Start</a>
                            @elseif($tab === 'in_progress' && $att)
                                <a class="btn btn-xs btn-primary" href="{{ route('user.assessments.take', $att->id) }}">Continue</a>
                            @elseif($att)
                                <a class="btn btn-xs btn-default" href="{{ route('user.assessments.result', $att->id) }}">View</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Nothing in this tab.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
