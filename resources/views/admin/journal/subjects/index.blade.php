@extends('admin.layout.app')
@section('title', 'Journal Subjects')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-12"><h2>Journal subjects</h2></div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="ibox"><div class="ibox-content">
        <form method="POST" action="{{ route('admin.journal.subjects.store') }}" class="form-inline" style="margin-bottom:16px;">
            @csrf
            <input name="name" class="form-control" placeholder="Subject name" required>
            <input name="sort_order" class="form-control" placeholder="Order" style="width:80px;">
            <label style="margin:0 8px;"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            <button class="btn btn-primary">Add</button>
        </form>
        <table class="table table-bordered">
            <thead><tr><th>Name</th><th>Slug</th><th>Order</th><th>Active</th><th style="width:160px;"></th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <form id="subj-{{ $row->id }}" method="POST" action="{{ route('admin.journal.subjects.update', $row->id) }}">
                            @csrf @method('PUT')
                            <input name="name" class="form-control" value="{{ $row->name }}" required>
                        </form>
                    </td>
                    <td><code>{{ $row->slug }}</code></td>
                    <td><input form="subj-{{ $row->id }}" name="sort_order" class="form-control" value="{{ $row->sort_order }}"></td>
                    <td><label><input form="subj-{{ $row->id }}" type="checkbox" name="is_active" value="1" {{ $row->is_active ? 'checked' : '' }}> Active</label></td>
                    <td>
                        <button form="subj-{{ $row->id }}" class="btn btn-xs btn-primary">Save</button>
                        <form method="POST" action="{{ route('admin.journal.subjects.destroy', $row->id) }}" style="display:inline;" onsubmit="return confirm('Delete subject?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No subjects yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
