@extends('admin.layout.app')
@section('title', 'Payment Gateways')

@section('content')
<div class="ibox-content">
    <h3 class="ibox-title">Payment Gateways</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
<div class="card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Active</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gateways as $gateway)
                <tr>
                    <td>{{ $gateway->name }}</td>
                    <td>{{ $gateway->slug }}</td>
                    <td>
                        <span class="badge bg-{{ $gateway->status === 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($gateway->status) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $gateway->active ? 'info' : 'warning' }}">
                            {{ $gateway->active ? 'Yes' : 'No' }}
                        </span>
                    </td>
                    <td>{{ $gateway->updated_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.payment-gateways.edit', $gateway->id) }}" class="btn btn-sm btn-primary">
                            Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No payment gateways found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
