@extends('admin.layout.app')
@section('title', 'Journal Issues')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-12"><h2>Journal issues</h2></div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="ibox"><div class="ibox-content">
        <form method="POST" action="{{ route('admin.journal.issues.store') }}" class="m-b">
            @csrf
            <div class="row">
                <div class="col-md-4 form-group"><input name="title" class="form-control" placeholder="Issue title" required></div>
                <div class="col-md-2 form-group"><input name="volume" class="form-control" placeholder="Volume"></div>
                <div class="col-md-2 form-group"><input name="number" class="form-control" placeholder="Number"></div>
                <div class="col-md-2 form-group">
                    <select name="issue_type" class="form-control">
                        <option value="regular">Regular</option>
                        <option value="special">Special</option>
                        <option value="previous">Previous</option>
                    </select>
                </div>
                <div class="col-md-2 form-group"><input type="date" name="published_at" class="form-control"></div>
            </div>
            <label><input type="checkbox" name="is_current" value="1"> Mark as current</label>
            <button class="btn btn-primary">Add issue</button>
        </form>
        <table class="table table-bordered">
            <thead><tr><th>Title</th><th>Vol/No</th><th>Type</th><th>Current</th><th>Published</th><th></th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->title }}</td>
                    <td>{{ $row->volume }}/{{ $row->number }}</td>
                    <td>{{ $row->issue_type }}</td>
                    <td>{{ $row->is_current ? 'Yes' : '—' }}</td>
                    <td>{{ optional($row->published_at)->format('Y-m-d') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.journal.issues.destroy', $row->id) }}" onsubmit="return confirm('Delete?');">
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
