@extends('user.layout.app')
@section('title', 'Dashboard')

@push('style')
<link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
<style>
    .wrapper-content {
        padding-right: 20px;
    }
    .admin-dt-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px 16px;
        margin-bottom: 14px;
        width: 100%;
    }
    .admin-dt-toolbar .dataTables_length,
    .admin-dt-toolbar .dataTables_filter {
        float: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .admin-dt-toolbar .dataTables_length label,
    .admin-dt-toolbar .dataTables_filter label {
        margin-bottom: 0;
        font-weight: normal;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .admin-dt-toolbar .dataTables_filter {
        margin-left: auto !important;
    }
    @media (max-width: 768px) {
        .admin-dt-toolbar .dataTables_filter {
            margin-left: 0 !important;
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(isset($courseAccesses) && $courseAccesses->isNotEmpty())
        <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0;">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ibox">
                        <div class="ibox-title" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                            <h5 style="margin:0;">Course Access</h5>
                            <a href="{{ route('user.study-materials.index') }}" class="btn btn-xs btn-default">All Study Materials</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                @foreach($courseAccesses as $access)
                    @include('user.study-materials._folder_card', ['access' => $access])
                @endforeach
            </div>
        </div>
    @elseif(!empty($isInstructor))
        <div class="wrapper wrapper-content animated fadeInRight">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ibox">
                        <div class="ibox-title"><h5 style="margin:0;">Course Access</h5></div>
                        <div class="ibox-content text-center text-muted">
                            No folders assigned yet. When admin assigns you folder access, it will appear here.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(empty($isInstructor) && auth()->user()->hasPermission('installment-list'))
        <div class="wrapper wrapper-content animated fadeInRight">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>Admission Information</h5>
                        </div>
                        <div class="ibox-content">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover dataTables-example">
                                    <thead>
                                        <tr>
                                            <th>Invoice No</th>
                                            <th>Invoice Issued Date</th>
                                            <th>Course Name</th>
                                            <th>Package Name</th>
                                            <th>Invoice Amount</th>
                                            <th>Payment Plan</th>
                                            <th>Due Date</th>
                                            <th>Due Amount</th>
                                            <th>Receipt No</th>
                                            <th>Paid Amount</th>
                                            <th>Paid Date</th>
                                            <th>Payment Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $shownPaymentIds = [];
                                        @endphp
                                        @foreach ($data['installments'] as $installment)
                                            @if($installment->payment->status === 'Active')
                                                @php
                                                    $invoiceAmount = format_payment_amount($installment->payment);
                                                    $dueAed = ($installment->status === 'paid')
                                                        ? 0.0
                                                        : (float) ($installment->remaining_amount ?? 0);
                                                @endphp
                                                <tr>
                                                    <td>INV-{{ str_pad($installment->payment_id, 6, '0', STR_PAD_LEFT) }}</td>
                                                    <td data-order="{{ \Carbon\Carbon::parse($installment->created_at)->timestamp }}">{{ \Carbon\Carbon::parse($installment->created_at)->format('d-M-Y') ?? 'N/A' }}
                                                    </td>
                                                    <td>{{ $installment->payment->course->title ?? 'N/A' }}</td>
                                                    <td>{{ $installment->payment->courseFee->package_name ?? 'N/A' }}</td>
                                                    <td>
                                                        {{ $invoiceAmount['display'] }}
                                                    </td>
                                                    <td>{{ $installment->installment_number }}/{{ $installment->payment->total_installment }}
                                                    </td>
                                                    <td>{{ $installment->due_date ? \Carbon\Carbon::parse($installment->due_date)->format('d-M-Y') : 'N/A' }}</td>
                                                    <td>{{ format_payment_aed_amount($installment->payment, $dueAed) }}</td>
                                                    <td>
                                                        @if ($installment->status === 'paid')
                                                            RC-{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>{{ format_payment_aed_amount($installment->payment, (float) ($installment->paid_amount ?? 0)) }}</td>
                                                    <td>{{ $installment->paid_date ?? 'N/A' }}</td>
                                                    <td>
                                                        <span
                                                            class="badge badge-{{ $installment->status == 'paid' ? 'success' : 'warning' }}">
                                                            {{ ucfirst($installment->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if ($installment->status == 'pending')
                                                            <button type="button" class="btn btn-primary btn-sm payNowBtn"
                                                                style="margin-right: 5px; margin-bottom: 5px;"
                                                                data-installment-id="{{ $installment->id }}">
                                                                <i class="fa fa-credit-card"></i> Pay Now
                                                            </button>
                                                        @endif

                                                        @if ($installment->status == 'paid')
                                                            <a href="{{ route('user.installments.receipt', $installment->id) }}"
                                                                class="btn btn-primary btn-sm"
                                                                style="margin-right: 5px; margin-bottom: 5px;" download>
                                                                <i class="fa fa-file-text-o"></i> Receipt
                                                            </a>
                                                        @endif

                                                        @php
                                                            $paymentId = $installment->payment_id ?? null;
                                                        @endphp

                                                        @if ($paymentId && !in_array($paymentId, $shownPaymentIds))
                                                            <a href="{{ route('user.installments.invoice', $paymentId) }}"
                                                                class="btn btn-success btn-sm"
                                                                style="display:inline-block; margin-right:5px; margin-bottom: 5px;" download>
                                                                <i class="fa fa-file-pdf-o"></i> Invoice
                                                            </a>
                                                            @php
                                                                $shownPaymentIds[] = $paymentId;
                                                            @endphp
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script')
    @if(auth()->user()->hasPermission('installment-list'))
        <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>

        <script>
            $(document).on('click', '.payNowBtn', function () {
                var $btn = $(this);
                var installmentId = $btn.data('installment-id');
                var originalHtml = $btn.html();

                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Redirecting...');

                $.ajax({
                    url: '{{ route("user.generate.noonCheckout") }}',
                    method: 'POST',
                    data: {
                        installment_id: installmentId
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        if (res.success !== false && res.checkoutUrl) {
                            window.location.href = res.checkoutUrl;
                            return;
                        }

                        $btn.prop('disabled', false).html(originalHtml);
                        alert(res.error || 'Payment session could not be started. Please try again.');
                    },
                    error: function (err) {
                        $btn.prop('disabled', false).html(originalHtml);
                        var message = 'Payment session could not be started. Please try again.';
                        try {
                            var body = JSON.parse(err.responseText);
                            if (body.error) {
                                message = body.error;
                            }
                        } catch (e) {}
                        alert(message);
                    }
                });
            });
        </script>
    @endif

    <script>
        $(document).ready(function () {
            $('.dataTables-example').DataTable({
                pageLength: 10,
                searching: true,
                lengthChange: true,
                paging: true,
                info: false,
                ordering: true,
                responsive: true,
                dom: '<"admin-dt-toolbar"<l><f>>rtip',
                order: [[1, 'desc']]
            });
        });
    </script>
@endpush
