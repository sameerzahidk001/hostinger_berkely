@extends('admin.layout.app')
@section('title', 'Accountant Dashboard')
@push('style')
<style>
    #accountant-dashboard .accountant-tabs {
        border-bottom: none;
        margin-bottom: 20px;
    }
    #accountant-dashboard .accountant-tabs > li {
        margin-bottom: 8px;
    }
    #accountant-dashboard .accountant-tabs > li > a {
        color: #333 !important;
        border: 1px solid #d2d2d2 !important;
        background: #f5f5f5 !important;
        border-radius: 3px;
        margin-right: 6px;
        padding: 8px 14px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
    }
    #accountant-dashboard .accountant-tabs > li.active > a,
    #accountant-dashboard .accountant-tabs > li > a:hover {
        background: #e7eaec !important;
        color: #1ab394 !important;
        border-color: #1ab394 !important;
    }
    #accountant-dashboard a.accountant-stat-card {
        display: block;
        text-decoration: none !important;
        color: #676a6c !important;
        background: #fff;
        border: 1px solid #e7eaec;
        border-radius: 4px;
        padding: 16px;
        margin-bottom: 16px;
        min-height: 110px;
    }
    #accountant-dashboard a.accountant-stat-card:hover {
        border-color: #1ab394;
        background: #fafafa;
    }
    #accountant-dashboard .accountant-stat-card .stat-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        margin-bottom: 8px;
        color: #676a6c;
    }
    #accountant-dashboard .accountant-stat-card .stat-value {
        display: block;
        font-size: 26px;
        font-weight: 700;
        line-height: 1.15;
        color: #2f4050;
    }
    #accountant-dashboard .accountant-export-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        justify-content: flex-end;
        margin-bottom: 14px;
    }
</style>
@endpush
@section('content')
@php
    $activeTab = $activeTab ?? 'overview';
    $stats = $stats ?? [];
    $rows = $rows ?? collect();
    $exportBase = [
        'tab' => $activeTab,
        'date_from' => $dateFrom ?? now()->startOfMonth()->toDateString(),
        'date_to' => $dateTo ?? now()->toDateString(),
        'activity_date_from' => $activityDateFrom ?? now()->toDateString(),
        'activity_date_to' => $activityDateTo ?? now()->toDateString(),
    ];
@endphp
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-12">
        <h2>Accountant Dashboard</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Accountant Dashboard</strong></li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight" id="accountant-dashboard">
    <div class="ibox">
        <div class="ibox-content">
            <ul class="nav nav-tabs accountant-tabs" role="tablist">
                <li class="{{ $activeTab === 'overview' ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard.accountant', ['tab' => 'overview']) }}">Overview</a>
                </li>
                <li class="{{ $activeTab === 'this_month' ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard.accountant', ['tab' => 'this_month']) }}">This Month Sales</a>
                </li>
                <li class="{{ $activeTab === 'last_month' ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard.accountant', ['tab' => 'last_month']) }}">Last Month Sales</a>
                </li>
                <li class="{{ $activeTab === 'this_year' ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard.accountant', ['tab' => 'this_year']) }}">This Year Sales</a>
                </li>
                <li class="{{ $activeTab === 'custom' ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard.accountant', ['tab' => 'custom', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}">Custom Date Sales</a>
                </li>
            </ul>

            <div class="accountant-export-bar">
                <a class="btn btn-success btn-sm"
                   href="{{ route('admin.dashboard.accountant.export', array_merge($exportBase, [
                       'scope' => $activeTab === 'overview' ? 'overview' : 'sales',
                       'format' => 'excel',
                   ])) }}">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </a>
                <a class="btn btn-danger btn-sm"
                   href="{{ route('admin.dashboard.accountant.export', array_merge($exportBase, [
                       'scope' => $activeTab === 'overview' ? 'overview' : 'sales',
                       'format' => 'pdf',
                   ])) }}">
                    <i class="fa fa-file-pdf-o"></i> Export PDF
                </a>
            </div>

            @if($activeTab === 'overview')
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.index') }}" class="accountant-stat-card">
                            <span class="stat-label">All Invoices</span>
                            <span class="stat-value">{{ $stats['invoice_total'] ?? 0 }}</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}" class="accountant-stat-card">
                            <span class="stat-label">Unpaid Invoices</span>
                            <span class="stat-value">{{ $stats['invoice_unpaid'] ?? 0 }}</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.index', ['status' => 'partial']) }}" class="accountant-stat-card">
                            <span class="stat-label">Partially Paid Invoices</span>
                            <span class="stat-value">{{ $stats['invoice_partial'] ?? 0 }}</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.receipts') }}" class="accountant-stat-card">
                            <span class="stat-label">All Receipts</span>
                            <span class="stat-value">{{ $stats['receipts_total'] ?? 0 }}</span>
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4 col-md-4">
                        <a href="{{ route('admin.dashboard.accountant', ['tab' => 'this_month']) }}" class="accountant-stat-card">
                            <span class="stat-label">This Month's Sales</span>
                            <span class="stat-value">AED {{ number_format((float) ($stats['sales_this_month'] ?? 0), 2) }}</span>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4">
                        <a href="{{ route('admin.dashboard.accountant', ['tab' => 'last_month']) }}" class="accountant-stat-card">
                            <span class="stat-label">Last Month Sales</span>
                            <span class="stat-value">AED {{ number_format((float) ($stats['sales_last_month'] ?? 0), 2) }}</span>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4">
                        <a href="{{ route('admin.dashboard.accountant', ['tab' => 'this_year']) }}" class="accountant-stat-card">
                            <span class="stat-label">This Year Sales</span>
                            <span class="stat-value">AED {{ number_format((float) ($stats['sales_this_year'] ?? 0), 2) }}</span>
                        </a>
                    </div>
                </div>
            @else
                @if($activeTab === 'custom')
                    <form method="GET" action="{{ route('admin.dashboard.accountant') }}" class="form-inline" style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
                        <input type="hidden" name="tab" value="custom">
                        <div class="form-group">
                            <label for="date_from" class="m-r-xs">From</label>
                            <input type="date" id="date_from" name="date_from" class="form-control" value="{{ $dateFrom }}">
                        </div>
                        <div class="form-group">
                            <label for="date_to" class="m-r-xs">To</label>
                            <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $dateTo }}">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Receipt Date</th>
                                <th>Receipt Number</th>
                                <th>Invoice No#</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Package</th>
                                <th>Amount (Paid)</th>
                                <th>Installment</th>
                                <th>Payment Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row['receipt_date'] ?? $row['created_at'] ?? '—' }}</td>
                                    <td>{{ $row['receipt_no'] ?? '—' }}</td>
                                    <td>{{ $row['invoice_no'] ?? '—' }}</td>
                                    <td>{{ $row['student'] }}</td>
                                    <td>{{ $row['course'] }}</td>
                                    <td>{{ $row['package'] }}</td>
                                    <td>{{ $row['amount'] }}</td>
                                    <td>{{ $row['installments'] ?? '—' }}</td>
                                    <td>{{ $row['status'] }}</td>
                                    <td>
                                        @if(!empty($row['url']))
                                            <a href="{{ $row['url'] }}">View</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted">No paid sales found for this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($rows->isNotEmpty())
                            <tfoot>
                                <tr style="font-weight:700;background:#f9f9f9;">
                                    <td colspan="7" class="text-right">Total</td>
                                    <td>AED {{ number_format((float) ($salesTotalAed ?? 0), 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="ibox" style="margin-top:16px;" id="accountant-activities">
        <div class="ibox-title">
            <h5 style="margin:0;font-weight:700;text-transform:uppercase;">{{ $activityTitle ?? 'Accountant - ACTIVITY REPORT' }}</h5>
        </div>
        <div class="ibox-content">
            <form method="GET" action="{{ route('admin.dashboard.accountant') }}" class="form-inline" style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                @if($activeTab === 'custom')
                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                @endif
                <div class="form-group">
                    <label for="activity_date_from" class="m-r-xs">From</label>
                    <input type="date" id="activity_date_from" name="activity_date_from" class="form-control" value="{{ $activityDateFrom ?? now()->toDateString() }}">
                </div>
                <div class="form-group">
                    <label for="activity_date_to" class="m-r-xs">To</label>
                    <input type="date" id="activity_date_to" name="activity_date_to" class="form-control" value="{{ $activityDateTo ?? now()->toDateString() }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                <a class="btn btn-success btn-sm"
                   href="{{ route('admin.dashboard.accountant.export', array_merge($exportBase, ['scope' => 'activity', 'format' => 'excel'])) }}">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </a>
                <a class="btn btn-danger btn-sm"
                   href="{{ route('admin.dashboard.accountant.export', array_merge($exportBase, ['scope' => 'activity', 'format' => 'pdf'])) }}">
                    <i class="fa fa-file-pdf-o"></i> Export PDF
                </a>
            </form>
            <p class="m-b-sm" style="font-weight:600;">
                Report for: {{ $activityDateFrom ?? now()->toDateString() }} - {{ $activityDateTo ?? now()->toDateString() }}
            </p>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width:170px;">Date &amp; Time</th>
                            <th style="width:160px;">Name</th>
                            <th style="width:140px;">Contact Number</th>
                            <th>Activity</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($activities ?? collect()) as $activity)
                            <tr>
                                <td>
                                    @if(!empty($activity['occurred_at']) && is_object($activity['occurred_at']))
                                        {{ $activity['occurred_at']->format('M j, Y g:i A') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $activity['user_name'] ?? '—' }}</td>
                                <td>{{ $activity['contact_number'] ?? '—' }}</td>
                                <td>{{ activity_action_label($activity['action'] ?? '') }}</td>
                                <td>
                                    @if(!empty($activity['url']))
                                        <a href="{{ $activity['url'] }}">{{ $activity['item'] ?? 'View' }}</a>
                                    @else
                                        {{ $activity['item'] ?? '—' }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No accountant activities for this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($activities ?? null, 'links'))
                <div class="text-right">{{ $activities->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
