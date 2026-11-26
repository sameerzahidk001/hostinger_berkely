@extends('admin.layout.app')
@section('title', 'Academic Journal')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Academic Journal</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Journal overview</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="alert alert-info">
        Managed inside the same Superadmin. Do not publish fake ISSN, indexing, editors, or citation stats.
    </div>
    <div class="row">
        @foreach([
            ['Subjects', $counts['subjects'], 'admin.journal.subjects.index'],
            ['Policies', $counts['policies'], 'admin.journal.policies.index'],
            ['Issues', $counts['issues'], 'admin.journal.issues.index'],
            ['Editorial board', $counts['board'], 'admin.journal.board.index'],
            ['Manuscripts', $counts['manuscripts'], 'admin.journal.manuscripts.index'],
            ['Published', $counts['published'], 'admin.journal.manuscripts.index'],
        ] as $card)
            <div class="col-md-4">
                <div class="ibox">
                    <div class="ibox-content">
                        <h3>{{ $card[0] }}</h3>
                        <p class="text-muted" style="font-size:28px;margin:0;">{{ $card[1] }}</p>
                        <a href="{{ route($card[2], $card[0] === 'Published' ? ['status' => 'published'] : []) }}">Manage</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <p>Submitted awaiting action: <strong>{{ $counts['submitted'] }}</strong></p>
</div>
@endsection
