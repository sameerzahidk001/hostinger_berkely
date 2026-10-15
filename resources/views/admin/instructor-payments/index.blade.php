@extends('admin.layout.app')
@section('title', 'Instructor Payments')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Instructor Payments</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Batch-wise payments</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.instructor-payments.create') }}" class="btn btn-primary">Add payment</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <div class="ibox">
        <div class="ibox-title"><h5>Payment ledger</h5></div>
        <div class="ibox-content table-responsive">
            <form method="GET" action="{{ route('admin.instructor-payments.index') }}" class="m-b-md" style="margin-bottom:15px;">
                <div class="row">
                    <div class="col-sm-3 form-group">
                        <label>Batch</label>
                        <select name="batch_id" class="form-control">
                            <option value="0">All</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((int)$filters['batch_id'] === (int)$batch->id)>
                                    {{ $batch->code }} — {{ $batch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3 form-group">
                        <label>Instructor</label>
                        <select name="instructor_id" class="form-control">
                            <option value="0">All</option>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected((int)$filters['instructor_id'] === (int)$ins->id)>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4 form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Instructor, batch, notes">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.instructor-payments.index') }}" class="btn btn-default">Clear</a>
            </form>

            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Instructor</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Due date</th>
                        <th>Paid date</th>
                        <th>Slip</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>
                                {{ $row->batch->code ?? '—' }} — {{ $row->batch->name ?? '—' }}
                                @if($row->batch && $row->batch->course)
                                    <br><small class="text-muted">{{ $row->batch->course->title }}</small>
                                @endif
                            </td>
                            <td>
                                {{ $row->instructor->name ?? '—' }}
                                @if(!empty($row->instructor->email))
                                    <br><small class="text-muted">{{ $row->instructor->email }}</small>
                                @endif
                            </td>
                            <td>{{ $row->formattedAmount() }}</td>
                            <td><span class="label {{ $row->statusBadgeClass() }}">{{ $row->statusLabel() }}</span></td>
                            <td>{{ optional($row->due_date ?? null)->format('d M Y') ?? '—' }}</td>
                            <td>{{ optional($row->paid_date)->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if($row->hasSlip())
                                    <a href="{{ route('admin.instructor-payments.slip', $row->id) }}" target="_blank" class="btn btn-xs btn-default">View slip</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($row->notes, 60) ?: '—' }}</td>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('admin.instructor-payments.edit', $row->id) }}" class="btn btn-xs btn-primary">Edit</a>
                                @if(!empty($isAdmin))
                                <form action="{{ route('admin.instructor-payments.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove this payment record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">No instructor payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
