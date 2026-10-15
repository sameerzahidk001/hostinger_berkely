@extends('user.layout.app')
@section('title', 'My Payments')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>My Payments</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Home</a></li>
            <li class="active"><strong>Batch payments</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="ibox">
        <div class="ibox-title"><h5>Your batch payment status</h5></div>
        <div class="ibox-content table-responsive">
            <p class="text-muted">Read-only. Accountant / Admin update these records. You can view a payment slip when one has been uploaded.</p>
            <form method="GET" action="{{ route('user.instructor-payments.index') }}" class="m-b-md" style="margin-bottom:15px;">
                <div class="row">
                    <div class="col-sm-4 col-md-3">
                        <select name="status" class="form-control">
                            <option value="">All statuses</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        @if($status !== '')
                            <a href="{{ route('user.instructor-payments.index') }}" class="btn btn-default">Clear</a>
                        @endif
                    </div>
                </div>
            </form>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Due date</th>
                        <th>Paid date</th>
                        <th>Slip</th>
                        <th>Notes</th>
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
                            <td>{{ $row->formattedAmount() }}</td>
                            <td><span class="label {{ $row->statusBadgeClass() }}">{{ $row->statusLabel() }}</span></td>
                            <td>{{ optional($row->due_date ?? null)->format('d M Y') ?? '—' }}</td>
                            <td>{{ optional($row->paid_date)->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if($row->hasSlip())
                                    <a href="{{ route('user.instructor-payments.slip', $row->id) }}" target="_blank" class="btn btn-xs btn-default">View slip</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $row->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No payment records for you yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
