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
        color: #fff !important;
        border: none !important;
        border-radius: 3px;
        margin-right: 6px;
        padding: 8px 14px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
    }
    #accountant-dashboard .tab-overview { background: #2f4050; }
    #accountant-dashboard .tab-this-month { background: #1c84c6; }
    #accountant-dashboard .tab-last-month { background: #23c6c8; }
    #accountant-dashboard .tab-this-year { background: #1ab394; }
    #accountant-dashboard .tab-custom { background: #676a6c; }
    #accountant-dashboard .accountant-tabs > li.active > a,
    #accountant-dashboard .accountant-tabs > li > a:hover {
        opacity: 0.9;
        color: #fff !important;
    }
    #accountant-dashboard a.widget {
        display: block;
        color: #fff !important;
        text-decoration: none !important;
        margin-bottom: 16px;
    }
    #accountant-dashboard a.widget:hover {
        color: #fff !important;
        opacity: 0.95;
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
            <ul class="nav nav-tabs accountant-tabs" role="tablist">
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
                    <a class="tab-custom" href="{{ route('admin.dashboard.accountant', ['tab' => 'custom', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}">Custom Date Sales</a>
                </li>
            </ul>

            @if($activeTab === 'overview')
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.index') }}" class="widget style1 navy-bg">
                            <div class="row">
                                <div class="col-xs-4">
                                    <i class="fa fa-file-text-o fa-3x"></i>
                                </div>
                                <div class="col-xs-8 text-right">
                                    <span>All Invoices</span>
                                    <h2 class="font-bold">{{ $stats['invoice_total'] ?? 0 }}</h2>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}" class="widget style1 yellow-bg">
                            <div class="row">
                                <div class="col-xs-4">
                                    <i class="fa fa-clock-o fa-3x"></i>
                                </div>
                                <div class="col-xs-8 text-right">
                                    <span>Unpaid Invoices</span>
                                    <h2 class="font-bold">{{ $stats['invoice_unpaid'] ?? 0 }}</h2>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.index', ['status' => 'partial']) }}" class="widget style1 blue-bg">
                            <div class="row">
                                <div class="col-xs-4">
                                    <i class="fa fa-adjust fa-3x"></i>
                                </div>
                                <div class="col-xs-8 text-right">
                                    <span>Partially Paid</span>
                                    <h2 class="font-bold">{{ $stats['invoice_partial'] ?? 0 }}</h2>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.payments.receipts') }}" class="widget style1 lazur-bg">
                            <div class="row">
                                <div class="col-xs-4">
                                    <i class="fa fa-list-alt fa-3x"></i>
                                </div>
                                <div class="col-xs-8 text-right">
                                    <span>All Receipts</span>
                                    <h2 class="font-bold">{{ $stats['receipts_total'] ?? 0 }}</h2>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="row" style="margin-top:8px;">
                    <div class="col-lg-4 col-md-4">
                        <a href="{{ route('admin.dashboard.accountant', ['tab' => 'this_month']) }}" class="widget style1 navy-bg">
                            <div class="row">
                                <div class="col-xs-4">
                                    <i class="fa fa-line-chart fa-3x"></i>
                                </div>
                                <div class="col-xs-8 text-right">
                                    <span>This Month's Sales</span>
                                    <h2 class="font-bold">AED {{ number_format((float) ($stats['sales_this_month'] ?? 0), 2) }}</h2>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4">
                        <a href="{{ route('admin.dashboard.accountant', ['tab' => 'last_month']) }}" class="widget style1 yellow-bg">
                            <div class="row">
                                <div class="col-xs-4">
                                    <i class="fa fa-calendar fa-3x"></i>
                                </div>
                                <div class="col-xs-8 text-right">
                                    <span>Last Month Sales</span>
                                    <h2 class="font-bold">AED {{ number_format((float) ($stats['sales_last_month'] ?? 0), 2) }}</h2>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-4">
                        <a href="{{ route('admin.dashboard.accountant', ['tab' => 'this_year']) }}" class="widget style1 lazur-bg">
                            <div class="row">
                                <div class="col-xs-4">
                                    <i class="fa fa-money fa-3x"></i>
                                </div>
                                <div class="col-xs-8 text-right">
                                    <span>This Year</span>
                                    <h2 class="font-bold">AED {{ number_format((float) ($stats['sales_this_year'] ?? 0), 2) }}</h2>
                                </div>
                            </div>
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
                                <th>Amount (Paid)</th>
                                <th>Installment</th>
                                <th>Payment Status</th>
                                <th>Action</th>
                                <th>Paid Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
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
                                    <td>{{ $row['created_at'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No paid sales found for this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($rows->isNotEmpty())
                            <tfoot>
                                <tr style="font-weight:700;background:#f9f9f9;">
                                    <td colspan="4" class="text-right">Total</td>
                                    <td>AED {{ number_format((float) ($salesTotalAed ?? 0), 2) }}</td>
                                    <td colspan="4"></td>
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
            </form>
            <p class="m-b-sm" style="font-weight:600;">
                Report for: {{ $activityDateFrom ?? now()->toDateString() }} - {{ $activityDateTo ?? now()->toDateString() }}
            </p>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width:170px;">Date &amp; Time</th>
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
                                <td colspan="3" class="text-center text-muted">No accountant activities for this range.</td>
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
