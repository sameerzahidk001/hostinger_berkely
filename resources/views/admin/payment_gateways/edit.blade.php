@extends('admin.layout.app')
@section('title', 'Edit Payment Gateway')

@section('content')
<div class="ibox-content">
    <h3 class="mb-4">Edit Payment Gateway: {{ $gateway->name }}</h3>
    @if($gateway->isStripe())
        <div class="alert alert-info">
            Stripe is the <strong>USD</strong> path. Set Test or Live keys, then mark Gateway Active.
            Webhook URL: <code>{{ url('/stripe/webhook') }}</code>
            (events: <code>checkout.session.completed</code>)
        </div>
    @endif
    <form method="POST" action="{{ route('admin.payment-gateways.update', $gateway->id) }}">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <h5>Test Mode Keys</h5>
                <div style="margin-bottom: 15px;">
                    <label>{{ $gateway->isStripe() ? 'Publishable key' : 'ID' }}</label>
                    <input type="text" name="test_client_id" class="form-control"
                        value="{{ $gateway->test_keys['client_id'] ?? '' }}">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Secret Key</label>
                    <input type="text" name="test_secret_key" class="form-control"
                        value="{{ $gateway->test_keys['secret_key'] ?? '' }}">
                </div>
                @if($gateway->isStripe())
                <div style="margin-bottom: 15px;">
                    <label>Webhook signing secret (optional)</label>
                    <input type="text" name="test_webhook_secret" class="form-control"
                        value="{{ $gateway->test_keys['webhook_secret'] ?? '' }}">
                </div>
                @endif
            </div>
            <div class="col-md-6">
                <h5>Production Mode Keys</h5>
                <div style="margin-bottom: 15px;">
                    <label>{{ $gateway->isStripe() ? 'Publishable key' : 'ID' }}</label>
                    <input type="text" name="prod_client_id" class="form-control"
                        value="{{ $gateway->production_keys['client_id'] ?? '' }}">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Secret Key</label>
                    <input type="text" name="prod_secret_key" class="form-control"
                        value="{{ $gateway->production_keys['secret_key'] ?? '' }}">
                </div>
                @if($gateway->isStripe())
                <div style="margin-bottom: 15px;">
                    <label>Webhook signing secret (optional)</label>
                    <input type="text" name="prod_webhook_secret" class="form-control"
                        value="{{ $gateway->production_keys['webhook_secret'] ?? '' }}">
                </div>
                @endif
            </div>
            <div class="col-md-6">
                <div style="margin-bottom: 15px;">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="test" {{ $gateway->status === 'test' ? 'selected' : '' }}>Test</option>
                        <option value="live" {{ $gateway->status === 'live' ? 'selected' : '' }}>Live</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch" style="margin-bottom: 15px;">
                    <input class="form-check-input" type="checkbox" id="activeSwitch" name="active" {{ $gateway->active ?
                        'checked' : '' }}>
                    <label class="form-check-label" for="activeSwitch">Gateway Active</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Update Gateway</button>
    </form>
</div>
@endsection