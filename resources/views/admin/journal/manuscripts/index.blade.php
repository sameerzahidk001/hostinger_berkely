@extends('admin.layout.app')
@section('title', 'Journal Manuscripts')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-12"><h2>Manuscripts</h2></div>
</div>
<div class="wrapper wrapper-content">
    <p>
        Filter:
        <a href="{{ route('admin.journal.manuscripts.index') }}">All</a> |
        <a href="{{ route('admin.journal.manuscripts.index', ['status' => 'submitted']) }}">Submitted</a> |
        <a href="{{ route('admin.journal.manuscripts.index', ['status' => 'under_review']) }}">Under review</a> |
        <a href="{{ route('admin.journal.manuscripts.index', ['status' => 'published']) }}">Published</a>
    </p>
    <div class="ibox"><div class="ibox-content table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>Subject</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->title }}</td>
                    <td>{{ optional($row->author)->name ?: $row->corresponding_author_name }}</td>
                    <td>{{ optional($row->subject)->name ?: '—' }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ optional($row->submitted_at)->format('Y-m-d') }}</td>
                    <td><a href="{{ route('journal.editorial.show', $row->id) }}" target="_blank">Editorial</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $rows->links() }}
    </div></div>
</div>
@endsection
