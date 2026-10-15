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
<div class="wrapper wrapper-content" id="assessments-portal">
    <style>
        /* Student theme sets .nav > li > a { color: white } for the sidebar —
           that makes content tabs invisible on a light background. */
        #assessments-portal .nav-tabs > li > a,
        #assessments-portal .nav-pills > li > a {
            color: #2f4050 !important;
            font-weight: 600;
            background: #f5f5f5;
            border: 1px solid #e7eaec;
            margin-right: 4px;
            padding: 8px 14px;
        }
        #assessments-portal .nav-tabs > li > a:hover,
        #assessments-portal .nav-tabs > li > a:focus {
            color: #1ab394 !important;
            background: #fff;
        }
        #assessments-portal .nav-tabs > li.active > a,
        #assessments-portal .nav-tabs > li.active > a:hover,
        #assessments-portal .nav-tabs > li.active > a:focus {
            color: #fff !important;
            background: #1ab394;
            border-color: #1ab394;
        }
        /* Distinct status-tab colors (Available / Upcoming / …) */
        #assessments-portal .nav-pills > li > a.tab-available { background: #e8f8f5; border-color: #1ab394; color: #0f7a66 !important; }
        #assessments-portal .nav-pills > li > a.tab-upcoming { background: #eaf2ff; border-color: #3b82f6; color: #1d4ed8 !important; }
        #assessments-portal .nav-pills > li > a.tab-in-progress { background: #fff4e5; border-color: #f59e0b; color: #b45309 !important; }
        #assessments-portal .nav-pills > li > a.tab-submitted { background: #f3e8ff; border-color: #8b5cf6; color: #6d28d9 !important; }
        #assessments-portal .nav-pills > li > a.tab-results { background: #ecfeff; border-color: #06b6d4; color: #0e7490 !important; }
        #assessments-portal .nav-pills > li > a.tab-missed { background: #fef2f2; border-color: #ef4444; color: #b91c1c !important; }
        #assessments-portal .nav-pills > li.active > a.tab-available,
        #assessments-portal .nav-pills > li.active > a.tab-available:hover,
        #assessments-portal .nav-pills > li.active > a.tab-available:focus { background: #1ab394; border-color: #1ab394; color: #fff !important; }
        #assessments-portal .nav-pills > li.active > a.tab-upcoming,
        #assessments-portal .nav-pills > li.active > a.tab-upcoming:hover,
        #assessments-portal .nav-pills > li.active > a.tab-upcoming:focus { background: #3b82f6; border-color: #3b82f6; color: #fff !important; }
        #assessments-portal .nav-pills > li.active > a.tab-in-progress,
        #assessments-portal .nav-pills > li.active > a.tab-in-progress:hover,
        #assessments-portal .nav-pills > li.active > a.tab-in-progress:focus { background: #f59e0b; border-color: #f59e0b; color: #fff !important; }
        #assessments-portal .nav-pills > li.active > a.tab-submitted,
        #assessments-portal .nav-pills > li.active > a.tab-submitted:hover,
        #assessments-portal .nav-pills > li.active > a.tab-submitted:focus { background: #8b5cf6; border-color: #8b5cf6; color: #fff !important; }
        #assessments-portal .nav-pills > li.active > a.tab-results,
        #assessments-portal .nav-pills > li.active > a.tab-results:hover,
        #assessments-portal .nav-pills > li.active > a.tab-results:focus { background: #06b6d4; border-color: #06b6d4; color: #fff !important; }
        #assessments-portal .nav-pills > li.active > a.tab-missed,
        #assessments-portal .nav-pills > li.active > a.tab-missed:hover,
        #assessments-portal .nav-pills > li.active > a.tab-missed:focus { background: #ef4444; border-color: #ef4444; color: #fff !important; }
    </style>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <ul class="nav nav-tabs" style="margin-bottom:15px;">
        <li class="{{ $section === 'all' ? 'active' : '' }}"><a href="{{ route('user.assessments.index', ['section' => 'all', 'tab' => $tab]) }}">All</a></li>
        <li class="{{ $section === 'mocks' ? 'active' : '' }}"><a href="{{ route('user.assessments.mocks', ['tab' => $tab]) }}">Mock Exams</a></li>
        <li class="{{ $section === 'practice' ? 'active' : '' }}"><a href="{{ route('user.assessments.index', ['section' => 'practice', 'tab' => $tab]) }}">Practice / General</a></li>
        @if(!empty($isStaff))
            <li class="{{ $section === 'skill' ? 'active' : '' }}"><a href="{{ route('user.assessments.index', ['section' => 'skill', 'tab' => $tab]) }}">Skill tests</a></li>
        @endif
    </ul>

    <ul class="nav nav-pills" style="margin-bottom:15px;">
        <li class="{{ $tab === 'available' ? 'active' : '' }}">
            <a class="tab-available" href="{{ request()->fullUrlWithQuery(['tab' => 'available']) }}">Available ({{ $counts['available'] ?? 0 }})</a>
        </li>
        <li class="{{ $tab === 'upcoming' ? 'active' : '' }}">
            <a class="tab-upcoming" href="{{ request()->fullUrlWithQuery(['tab' => 'upcoming']) }}">Upcoming ({{ $counts['upcoming'] ?? 0 }})</a>
        </li>
        <li class="{{ $tab === 'in_progress' ? 'active' : '' }}">
            <a class="tab-in-progress" href="{{ request()->fullUrlWithQuery(['tab' => 'in_progress']) }}">In progress ({{ $counts['in_progress'] ?? 0 }})</a>
        </li>
        <li class="{{ $tab === 'submitted' ? 'active' : '' }}">
            <a class="tab-submitted" href="{{ request()->fullUrlWithQuery(['tab' => 'submitted']) }}">Submitted ({{ $counts['submitted'] ?? 0 }})</a>
        </li>
        <li class="{{ $tab === 'results' ? 'active' : '' }}">
            <a class="tab-results" href="{{ request()->fullUrlWithQuery(['tab' => 'results']) }}">Results ({{ $counts['results'] ?? 0 }})</a>
        </li>
        <li class="{{ $tab === 'missed' ? 'active' : '' }}">
            <a class="tab-missed" href="{{ request()->fullUrlWithQuery(['tab' => 'missed']) }}">Missed ({{ $counts['missed'] ?? 0 }})</a>
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
                            @elseif($tab === 'missed')
                                Missed
                            @elseif($tab === 'upcoming')
                                Opens {{ optional($asg?->available_from)->format('d M Y H:i') ?: 'soon' }}
                            @else
                                Available
                            @endif
                        </td>
                        <td>
                            @if($tab === 'available')
                                <a class="btn btn-xs btn-primary" href="{{ route('user.assessments.start', $a->id) }}">Start</a>
                            @elseif($tab === 'in_progress' && $att)
                                <a class="btn btn-xs btn-primary" href="{{ route('user.assessments.take', $att->id) }}">Continue</a>
                            @elseif($att)
                                <a class="btn btn-xs btn-default" href="{{ route('user.assessments.result', $att->id) }}">View</a>
                            @elseif($tab === 'upcoming')
                                <span class="text-muted">Not open yet</span>
                            @elseif($tab === 'missed')
                                <span class="text-muted">Window ended</span>
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
