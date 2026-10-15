@extends('admin.layout.app')
@section('title', 'Zoho CRM — Invoices & Receipts')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Zoho CRM — Invoices & Receipts</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Zoho CRM</strong></li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('fail'))
        <div class="alert alert-danger">{{ session('fail') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="ibox">
                <div class="ibox-title"><h5>Connection status</h5></div>
                <div class="ibox-content">
                    <p><strong>Configured:</strong> {{ !empty($status['configured']) ? 'Yes' : 'No' }}</p>
                    <p><strong>Sync enabled:</strong> {{ !empty($status['enabled']) ? 'Yes' : 'No' }}</p>
                    <p><strong>Connected as:</strong> {{ $status['connected_email'] ?: '—' }}</p>
                    @if(!empty($status['error']))
                        <p class="text-danger">{{ $status['error'] }}</p>
                    @endif
                    <p class="text-muted">Pending sync: {{ $pending }} · Failed: {{ $failed }}</p>
                    <form method="POST" action="{{ route('admin.zoho-crm.retry') }}" style="display:inline;">@csrf
                        <button class="btn btn-warning btn-sm" type="submit">Retry failed</button>
                    </form>
                    <form method="POST" action="{{ route('admin.zoho-crm.backfill') }}" style="display:inline;margin-left:6px;">@csrf
                        <input type="hidden" name="limit" value="100">
                        <button class="btn btn-primary btn-sm" type="submit">Backfill (100)</button>
                    </form>
                </div>
            </div>
            <div class="ibox">
                <div class="ibox-title"><h5>OAuth setup (CRM-1)</h5></div>
                <div class="ibox-content">
                    <ol style="padding-left:18px;">
                        <li>Open <a href="https://api-console.zoho.com" target="_blank" rel="noopener">api-console.zoho.com</a> → Self Client (separate from Meeting).</li>
                        <li>Generate a code with scope:<br><code style="word-break:break-all;">{{ $scopes }}</code></li>
                        <li>Run:<br><code>php artisan zoho:crm-connect --client-id=... --client-secret=... --code=GRANT --enable</code></li>
                        <li>In CRM layouts, add custom fields: Website Invoice ID, Website Receipt ID, Installment No, Balance Due, Package Name, Gateway.</li>
                        <li>Contact = student · Deal = course/package · Invoice = INV-###### · Receipt = RC-######.</li>
                    </ol>
                </div>
            </div>
            <div class="ibox">
                <div class="ibox-title"><h5>Delivery order</h5></div>
                <div class="ibox-content">
                    <ul class="list-unstyled" style="margin:0;">
                        <li><span class="label label-primary">done</span> <strong>CRM-1 Setup</strong> — OAuth, fields, sandbox connection</li>
                        <li style="margin-top:6px;"><span class="label label-primary">done</span> <strong>CRM-2 Sync service</strong> — Contact / Invoice / Receipt upsert + API log</li>
                        <li style="margin-top:6px;"><span class="label label-primary">done</span> <strong>CRM-3 Live push</strong> — invoice create + payment success</li>
                        <li style="margin-top:6px;"><span class="label label-primary">done</span> <strong>CRM-4 Backfill</strong> — Active / Partial / Paid (+ receipts)</li>
                        <li style="margin-top:6px;"><span class="label label-primary">done</span> <strong>CRM-5 Admin</strong> — last sync status + Push to CRM</li>
                    </ul>
                    <p class="help-block text-muted" style="margin-top:10px;margin-bottom:0;">
                        Nightly retry: <code>php artisan zoho:crm-retry</code> (scheduled 02:00). Backfill: <code>php artisan zoho:crm-backfill</code>.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="ibox">
                <div class="ibox-title"><h5>CRM credentials</h5></div>
                <div class="ibox-content">
                    <form method="POST" action="{{ route('admin.zoho-crm.settings.save') }}">
                        @csrf
                        <div class="form-group">
                            <label>Enable CRM sync</label>
                            <select name="ZOHO_CRM_ENABLED" class="form-control">
                                <option value="0" @selected(($values['ZOHO_CRM_ENABLED'] ?? '0') !== '1')>Disabled</option>
                                <option value="1" @selected(($values['ZOHO_CRM_ENABLED'] ?? '0') === '1')>Enabled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Client ID</label>
                            <input type="text" class="form-control" name="ZOHO_CRM_CLIENT_ID" value="{{ $values['ZOHO_CRM_CLIENT_ID'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label>Client Secret</label>
                            <input type="text" class="form-control" name="ZOHO_CRM_CLIENT_SECRET" value="{{ $values['ZOHO_CRM_CLIENT_SECRET'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label>Refresh Token</label>
                            <input type="text" class="form-control" name="ZOHO_CRM_REFRESH_TOKEN" value="{{ $values['ZOHO_CRM_REFRESH_TOKEN'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label>API Base</label>
                            <input type="text" class="form-control" name="ZOHO_CRM_API_BASE" value="{{ $values['ZOHO_CRM_API_BASE'] ?? 'https://www.zohoapis.com/crm/v2' }}">
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label>Invoice module API name</label>
                                <input type="text" class="form-control" name="ZOHO_CRM_MODULE_INVOICE" value="{{ $values['ZOHO_CRM_MODULE_INVOICE'] ?? 'Invoices' }}">
                            </div>
                            <div class="col-md-4">
                                <label>Receipt module API name</label>
                                <input type="text" class="form-control" name="ZOHO_CRM_MODULE_RECEIPT" value="{{ $values['ZOHO_CRM_MODULE_RECEIPT'] ?? 'Receipts' }}">
                            </div>
                            <div class="col-md-4">
                                <label>Deal module API name</label>
                                <input type="text" class="form-control" name="ZOHO_CRM_MODULE_DEAL" value="{{ $values['ZOHO_CRM_MODULE_DEAL'] ?? 'Deals' }}">
                            </div>
                        </div>
                        <p class="help-block text-muted" style="margin-top:10px;">
                            Contact = student. Deal / Enrolment = course or page fee package. Invoice = INV-######. Receipt = RC-######.
                            Matching is by student email and Website Invoice/Receipt ID (update, do not duplicate).
                        </p>
                        <button class="btn btn-primary" type="submit">Save CRM settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
