@extends('user.layout.app')

@section('title', 'Installments')

@push('style')
    <link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
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
                                    @foreach ($installments as $installment)
                                        @if($installment->payment->status != 'pending')
                                            <tr>
                                                @php
                                                    $dueAed = ($installment->status === 'paid')
                                                        ? 0.0
                                                        : (float) ($installment->remaining_amount ?? 0);
                                                @endphp
                                                <td>INV-{{ str_pad($installment->payment_id, 6, '0', STR_PAD_LEFT) }}</td>
                                                <td>{{ \Carbon\Carbon::parse($installment->created_at)->format('d-M-Y') ?? 'N/A' }}</td>
                                                <td>{{ $installment->payment->course->title ?? 'N/A' }}</td>
                                                <td>{{ $installment->payment->courseFee->package_name ?? 'N/A' }}</td>
                                                <td>{{ format_payment_amount($installment->payment)['display'] }}</td>
                                                <td>{{ $installment->installment_number }}/{{ $installment->payment->total_installment }}</td>
                                                <td>{{ $installment->due_date ?? 'N/A' }}</td>
                                                <td>{{ format_payment_aed_amount($installment->payment, $dueAed) }}</td>
                                                <td>
                                                    @if ($installment->status === 'paid')
                                                        RC-{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>{{ $installment->paid_amount ? format_payment_aed_amount($installment->payment, (float) $installment->paid_amount) : 'N/A' }}</td>
                                                <td>{{ $installment->paid_date ?? 'N/A' }}</td>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $installment->status == 'paid' ? 'success' : 'warning' }}">
                                                        {{ ucfirst($installment->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($installment->status == 'pending')
                                                        <button type="button"
                                                            class="btn btn-primary btn-sm payNowBtn"
                                                            style="margin-right: 5px; margin-bottom: 5px;"
                                                            data-installment-id="{{ $installment->id }}">
                                                            <i class="fa fa-credit-card"></i> Pay Now
                                                        </button>
                                                    @endif

                                                    @if ($installment->status == 'paid')
                                                        <a href="{{ route('user.installments.receipt', $installment->id) }}"
                                                            class="btn btn-primary btn-sm" style="margin-right: 5px; margin-bottom: 5px;" download>
                                                            <i class="fa fa-file-text-o"></i> Receipt
                                                        </a>
                                                    @endif

                                                    @php
                                                        $paymentId = $installment->payment_id ?? null;
                                                    @endphp

                                                    @if ($paymentId && !in_array($paymentId, $shownPaymentIds))
                                                        <a href="{{ route('user.installments.invoice', $paymentId) }}"
                                                            class="btn btn-success btn-sm" style="display:inline-block; margin-right:5px; margin-bottom: 5px;" download>
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

@endsection
@push('script')
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

        $(document).ready(function () {
            $('.dataTables-example').DataTable({
                pageLength: 10,
                searching: true,
                lengthChange: true,
                paging: true,
                info: false,
                ordering: true,
                responsive: true,
                dom: 'lftip'
            });
        });
    </script>
@endpush
