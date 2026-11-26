@extends('admin.layout.app')
@section('title', 'Editorial Board')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-12"><h2>Editorial board</h2></div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="alert alert-warning">Add only real confirmed editors. No placeholder names.</div>
    <div class="ibox"><div class="ibox-content">
        <form method="POST" action="{{ route('admin.journal.board.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 form-group"><input name="name" class="form-control" placeholder="Full name" required></div>
                <div class="col-md-2 form-group"><input name="role_title" class="form-control" placeholder="Role title"></div>
                <div class="col-md-3 form-group"><input name="affiliation" class="form-control" placeholder="Affiliation"></div>
                <div class="col-md-2 form-group"><input name="orcid" class="form-control" placeholder="ORCID"></div>
                <div class="col-md-2 form-group">
                    <select name="subject_id" class="form-control">
                        <option value="">Subject</option>
                        @foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <button class="btn btn-primary">Add member</button>
        </form>
        <hr>
        <table class="table table-bordered">
            <thead><tr><th>Name</th><th>Role</th><th>Affiliation</th><th>ORCID</th><th>Subject</th><th></th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->role_title }}</td>
                    <td>{{ $row->affiliation }}</td>
                    <td>{{ $row->orcid ?: '—' }}</td>
                    <td>{{ optional($row->subject)->name ?: '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.journal.board.destroy', $row->id) }}" onsubmit="return confirm('Delete?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
</div>
@endsection
