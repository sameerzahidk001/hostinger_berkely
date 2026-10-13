@extends('admin.layout.app')
@section('title', 'Accountant Dashboard')
@push('style')
<style>
    #accountant-dashboard .accountant-tabs > li > a {
        color: #fff !important;
        border: none !important;
        border-radius: 3px;
        margin-right: 6px;
        padding: 8px 14px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
    }
    #accountant-dashboard .tab-overview { background: #1ab394; }
    #accountant-dashboard .tab-this-month { background: #1c84c6; }
    #accountant-dashboard .tab-last-month { background: #f8ac59; }
    #accountant-dashboard .tab-this-year { background: #23c6c8; }
    #accountant-dashboard .tab-custom { background: #ed5565; }
    #accountant-dashboard .accountant-tabs > li.active > a,
    #accountant-dashboard .accountant-tabs > li > a:hover {
        opacity: 0.9;
        color: #fff !important;
    }
    #accountant-dashboard .stat-card {
        display: block;
        color: #fff;
        border-radius: 4px;
        padding: 18px 16px;
        margin-bottom: 16px;
        min-height: 110px;
    }
    #accountant-dashboard .stat-card .stat-value {
        font-size: 28px;
        font-weight: 700;
        line-height: 1.1;
    }
    #accountant-dashboard .stat-card .stat-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        margin-top: 8px;
        display: block;
    }
</style>
@endpush
@section('content')
@php
    $activeTab = $activeTab ?? 'overview';
    $stats = $stats ?? [];
    $rows = $rows ?? collect();
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
            <ul class="nav nav-tabs accountant-tabs" role="tablist" style="margin-bottom:16px;border-bottom:none;">
                <li class="{{ $activeTab === 'overview' ? 'active' : '' }}">
                    <a class="tab-overview" href="{{ route('admin.dashboard.accountant', ['tab' => 'overview']) }}">Overview</a>
                </li>
                <li class="{{ $activeTab === 'this_month' ? 'active' : '' }}">
                    <a class="tab-this-month" href="{{ route('admin.dashboard.accountant', ['tab' => 'this_month']) }}">This Month Sales</a>
                </li>
                <li class="{{ $activeTab === 'last_month' ? 'active' : '' }}">
                    <a class="tab-last-month" href="{{ route('admin.dashboard.accountant', ['tab' => 'last_month']) }}">Last Month Sales</a>
                </li>
                <li class="{{ $activeTab === 'this_year' ? 'active' : '' }}">
                    <a class="tab-this-year" href="{{ route('admin.dashboard.accountant', ['tab' => 'this_year']) }}">This Year Sales</a>
                </li>
                <li class="{{ $activeTab === 'custom' ? 'active' : '' }}">
                    <a class="tab-custom" href="{{ route('admin.dashboard.accountant', ['tab' => 'custom', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}">Custom Date Range</a>
                </li>
            </ul>

            @if($activeTab === 'overview')
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.index') }}" class="stat-card" style="background:#ed5565;">
                            <span class="stat-value">{{ $stats['invoice_total'] ?? 0 }}</span>
                            <span class="stat-label">All Invoices</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}" class="stat-card" style="background:#f8ac59;">
                            <span class="stat-value">{{ $stats['invoice_unpaid'] ?? 0 }}</span>
                            <span class="stat-label">Unpaid Invoices</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.index', ['status' => 'partial']) }}" class="stat-card" style="background:#1c84c6;">
                            <span class="stat-value">{{ $stats['invoice_partial'] ?? 0 }}</span>
                            <span class="stat-label">Partially Paid Invoices</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.receipts') }}" class="stat-card" style="background:#1ab394;">
                            <span class="stat-value">{{ $stats['receipts_total'] ?? 0 }}</span>
                            <span class="stat-label">All Receipts</span>
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
                                <th>Student</th>
                                <th>Course</th>
                                <th>Package</th>
                                <th>Amount</th>
                                <th>Installments</th>
                                <th>Payment Status</th>
                                <th>Invoice</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row['student'] }}</td>
                                    <td>
                                        @if(!empty($row['url']))
                                            <a href="{{ $row['url'] }}">{{ $row['course'] }}</a>
                                        @else
                                            {{ $row['course'] }}
                                        @endif
                                    </td>
                                    <td>{{ $row['package'] }}</td>
                                    <td>{{ $row['amount'] }}</td>
                                    <td>{{ $row['installments'] ?? '—' }}</td>
                                    <td>{{ $row['status'] }}</td>
                                    <td>{{ $row['invoice_status'] }}</td>
                                    <td>{{ $row['created_at'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No invoices found for this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
