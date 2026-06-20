@extends('user.layout.app')
@section('title', 'Dashboard')
@include('user.partials.rakbank-payment-modal')

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

    @if(auth()->user()->hasPermission('installment-list'))
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
                                            <th>Receipt No</th>
                                            <th>Invoice Issued Date</th>
                                            <th>Invoice Amount</th>
                                            <th>Payment Plan</th>
                                            <th>Due Date</th>
                                            <th>Due Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Paid Date</th>
                                            <th>Course Name</th>
                                            <th>Package Name</th>
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
                                                @php $invoiceAmount = format_payment_amount($installment->payment); @endphp
                                                <tr>
                                                    <td>INV-{{ str_pad($installment->payment_id, 6, '0', STR_PAD_LEFT) }}</td>
                                                    <td>
                                                        @if ($installment->status === 'paid')
                                                            RC-{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td data-order="{{ \Carbon\Carbon::parse($installment->created_at)->timestamp }}">{{ \Carbon\Carbon::parse($installment->created_at)->format('d-M-Y') ?? 'N/A' }}
                                                    </td>
                                                    <td>
                                                        {{ $invoiceAmount['display'] }}
                                                    </td>
                                                    <td>{{ $installment->installment_number }}/{{ $installment->payment->total_installment }}
                                                    </td>
                                                    <td>{{ $installment->due_date ? \Carbon\Carbon::parse($installment->due_date)->format('d-M-Y') : 'N/A' }}</td>
                                                    <td>{{ format_payment_aed_amount($installment->payment, (float) ($installment->paid_amount + $installment->remaining_amount)) }}</td>
                                                    <td>{{ format_payment_aed_amount($installment->payment, (float) ($installment->paid_amount ?? 0)) }}</td>
                                                    <td>{{ $installment->paid_date ?? 'N/A' }}</td>
                                                    <td>{{ $installment->payment->course->title ?? 'N/A' }}</td>
                                                    <td>{{ $installment->payment->courseFee->package_name ?? 'N/A' }}</td>
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
                                                                target="_blank" class="btn btn-primary btn-sm"
                                                                style="margin-right: 5px; margin-bottom: 5px;">
                                                                <i class="fa fa-file-text-o"></i> Receipt
                                                            </a>
                                                        @endif

                                                        @php
                                                            $paymentId = $installment->payment_id ?? null;
                                                            $courseId = $installment->payment->course->id ?? null;
                                                            $userId = $installment->user->id ?? null;
                                                        @endphp

                                                        @if ($paymentId && !in_array($paymentId, $shownPaymentIds))
                                                            <a href="{{ route('user.installments.invoice', $paymentId) }}"
                                                                target="_blank" class="btn btn-success btn-sm"
                                                                style="display:inline-block; margin-right:5px; margin-bottom: 5px;">
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
        <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document" style="max-width: 560px;">
                <div class="modal-content">
                    <button type="button" class="close close-white position-absolute top-0 right-0"
                        style="margin-top: -25px; margin-right: 10px;" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <div class="modal-body" style="padding: 24px;">
                        <div id="payment-amount-display" class="mb-3"></div>
                        <div id="payment-loading" class="text-center py-4" style="display: none;">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2 mb-0">Loading payment form...</p>
                        </div>
                        <div id="payment-error" class="alert alert-danger text-center" style="display: none;"></div>
                        <div id="hco-embedded" style="min-height: 360px;"></div>
                    </div>
                </div>
            </div>
        </div>
@endsection

@push('script')
    @if(auth()->user()->hasPermission('installment-list'))
        <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>

        <script>
            let currentInstallmentId = null;
            let currentSettlingAmount = null;
            let checkoutScriptLoaded = false;

            function resetPaymentModal() {
                $('#payment-amount-display').empty();
                $('#hco-embedded').empty();
                $('#payment-error').hide().empty();
                $('#payment-loading').hide();
            }

            function loadCheckoutScript(callback) {
                if (typeof Checkout !== 'undefined') {
                    callback();
                    return;
                }

                if (checkoutScriptLoaded) {
                    const waitForCheckout = setInterval(function () {
                        if (typeof Checkout !== 'undefined') {
                            clearInterval(waitForCheckout);
                            callback();
                        }
                    }, 50);
                    return;
                }

                checkoutScriptLoaded = true;
                const script = document.createElement('script');
                script.src = 'https://rakbankpay-nam.gateway.mastercard.com/static/checkout/checkout.min.js';
                script.setAttribute('data-error', 'errorCallback');
                script.setAttribute('data-cancel', 'cancelCallback');
                script.setAttribute('data-complete', 'completeCallback');
                script.onload = callback;
                script.onerror = function () {
                    checkoutScriptLoaded = false;
                    $('#payment-loading').hide();
                    console.error('Failed to load payment checkout script.');
                };
                document.head.appendChild(script);
            }

            function errorCallback(error) {
                console.log(JSON.stringify(error));
            }

            function cancelCallback() {
                console.log('Payment cancelled');
            }

            function completeCallback(response) {
                if (!currentInstallmentId || !currentSettlingAmount) {
                    console.error("Installment or amount missing.");
                    return;
                }

                $.ajax({
                    url: '{{ route("user.update.installment") }}',
                    method: 'POST',
                    data: {
                        amount: currentSettlingAmount,
                        installment_id: currentInstallmentId
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        if (res.success == true) {
                            $(".modal.show").modal('hide');

                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);
                        } else {
                            console.error("Something went wrong", res);
                        }
                    },
                    error: function (err) {
                        console.error("API error", err.responseText);
                    }
                });
            }

            function loadEmbeddedCheckout(sessionId) {
                loadCheckoutScript(function () {
                    try {
                        Checkout.configure({
                            session: { id: sessionId },
                        });
                        $('#payment-loading').hide();
                        Checkout.showEmbeddedPage('#hco-embedded');
                    } catch (error) {
                        $('#payment-loading').hide();
                        $('#payment-error').text('Unable to load payment form. Please try again.').show();
                        console.error("RakBank checkout init failed:", error);
                    }
                });
            }

            $(document).on('click', '.payNowBtn', function () {
                currentInstallmentId = $(this).data('installment-id');
                resetPaymentModal();
                $('#paymentModal').modal('show');
            });

            $('#paymentModal').on('shown.bs.modal', function () {
                resetPaymentModal();
                $('#payment-loading').show();

                $.ajax({
                    url: '{{ route("user.generate.rakBankPaySession") }}',
                    method: 'POST',
                    data: {
                        installment_id: currentInstallmentId,
                        return_url: window.location.href
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        $('#payment-loading').hide();

                        if (res.displayAmount) {
                            renderPaymentModalSummary('#payment-amount-display', res);
                        }

                        currentSettlingAmount = res.settlingAmount || null;

                        if (res.success !== false && res.session && res.session.id) {
                            loadEmbeddedCheckout(res.session.id);
                        } else {
                            $('#payment-error').text(res.error || 'Payment session could not be started. Please try again.').show();
                            console.error("Session creation failed", res);
                        }
                    },
                    error: function (err) {
                        $('#payment-loading').hide();
                        var message = 'Payment session could not be started. Please try again.';
                        try {
                            var body = JSON.parse(err.responseText);
                            if (body.error) {
                                message = body.error;
                            }
                        } catch (e) {}
                        $('#payment-error').text(message).show();
                        console.error("API error", err.responseText);
                    }
                });
            });

            $('#paymentModal').on('hidden.bs.modal', function () {
                resetPaymentModal();
                currentSettlingAmount = null;
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
                dom: 'lftip',
                order: [[2, 'desc']]
            });
        });
    </script>
@endpush