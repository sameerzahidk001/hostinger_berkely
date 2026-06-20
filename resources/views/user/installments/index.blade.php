@extends('user.layout.app')

@section('title', 'Installments')
@include('user.partials.rakbank-payment-modal')

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
                                    @foreach ($installments as $installment)
                                        @if($installment->payment->status != 'pending')
                                            <tr>
                                                <td>INV-{{ str_pad($installment->payment_id, 6, '0', STR_PAD_LEFT) }}</td>
                                                <td>
                                                    @if ($installment->status === 'paid')
                                                        RC-{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($installment->created_at)->format('d-M-Y') ?? 'N/A' }}</td>
                                                <td>{{ format_payment_amount($installment->payment)['display'] }}</td>
                                                <td>{{ $installment->installment_number }}/{{ $installment->payment->total_installment }}</td>
                                                <td>{{ $installment->due_date ?? 'N/A' }}</td>
                                                <td>{{ format_payment_aed_amount($installment->payment, (float) ($installment->paid_amount + $installment->remaining_amount)) }}</td>
                                                <td>{{ $installment->paid_amount ? format_payment_aed_amount($installment->payment, (float) $installment->paid_amount) : 'N/A' }}</td>
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
                                                        <!-- Trigger button -->
                                                        <button type="button" 
                                                            class="btn btn-primary btn-sm payNowBtn"
                                                            style="margin-right: 5px; margin-bottom: 5px;"
                                                            data-toggle="modal"
                                                            data-target="#paymentModal{{ $installment->id }}"
                                                            data-amount="{{ $installment->remaining_amount }}"
                                                            data-installment-id="{{ $installment->id }}">
                                                            <i class="fa fa-credit-card"></i> Pay Now
                                                        </button>

                                                        <!-- Modal -->
                                                        <div class="modal fade" id="paymentModal{{ $installment->id }}" tabindex="-1" role="dialog"
                                                            aria-labelledby="paymentModalLabel{{ $installment->id }}" aria-hidden="true">
                                                            <div class="modal-dialog" role="document" style="max-width: 520px;">
                                                                <div class="modal-content">
                                                                    <button type="button" id="closeModal" class="close close-white position-absolute top-0 right-0" style="margin-top: -25px;" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">×</span>
                                                                    </button>
                                                                    <div class="modal-body" style="padding: 28px 24px; text-align: center;">
                                                                        <div id="payment-amount-display-{{ $installment->id }}" class="mb-2"></div>
                                                                        <div id="payment-error-{{ $installment->id }}" class="alert alert-danger" style="display:none;"></div>
                                                                        <div id="payment-loading-{{ $installment->id }}" class="text-center text-muted py-3" style="display:none;">Preparing secure payment...</div>
                                                                        <button type="button"
                                                                            class="btn btn-primary btn-lg payment-start-btn"
                                                                            data-installment-id="{{ $installment->id }}"
                                                                            id="payment-start-btn-{{ $installment->id }}"
                                                                            style="display:none; min-width: 180px; margin-top: 12px;">
                                                                            <i class="fa fa-lock"></i> Pay
                                                                        </button>
                                                                        <p class="payment-secure-note" style="display:none; font-size: 12px; color: #999; margin-top: 14px; margin-bottom: 0;">
                                                                            Card details are entered on the secure payment screen.
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if ($installment->status == 'paid')
                                                        <a href="{{ route('user.installments.receipt', $installment->id) }}"
                                                            target="_blank" class="btn btn-primary btn-sm" style="margin-right: 5px; margin-bottom: 5px;">
                                                            <i class="fa fa-file-text-o"></i> Receipt
                                                        </a>
                                                    @endif

                                                    @php
                                                        $paymentId = $installment->payment_id ?? null;
                                                        $courseId = $installment->payment->course->id ?? null;
                                                        $userId = $installment->user->id ?? null;
                                                    @endphp

                                                    @if ($paymentId && !in_array($paymentId, $shownPaymentIds))
                                                        <form action="{{ route('user.installments.invoice.pdf') }}" method="POST" target="_blank" style="display:inline-block; margin-right:5px; margin-bottom: 5px;">
                                                            @csrf
                                                            <input type="hidden" name="course_id" value="{{ $courseId }}">
                                                            <input type="hidden" name="user_id" value="{{ $userId }}">
                                                            <input type="hidden" name="payment_id" value="{{ $paymentId }}">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="fa fa-file-pdf-o"></i> Invoice
                                                            </button>
                                                        </form>
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
    {{-- <script src="https://test-rakbankpay.mtf.gateway.mastercard.com/static/checkout/checkout.min.js" data-error="errorCallback" data-cancel="cancelCallback" data-complete="completeCallback"></script> --}}
    <script src="https://rakbankpay-nam.gateway.mastercard.com/static/checkout/checkout.min.js" data-error="errorCallback" data-cancel="cancelCallback" data-complete="completeCallback"></script>
    <script type="text/javascript" src="https://www.simplify.com/commerce/simplify.pay.js"></script>
    <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>

    <script>
        let currentInstallmentId = null;
        let currentAmount = null;
        let currentCheckoutSessionId = null;
        let checkoutScriptLoaded = false;

        function showPaymentAmount(targetSelector, displayAmount) {
            if (!displayAmount) {
                return;
            }

            $(targetSelector).html(
                '<div style="font-size:13px;font-weight:500;color:#666;margin-bottom:4px;">Amount to pay</div>' +
                '<div style="font-size:22px;font-weight:700;">' + displayAmount + '</div>'
            );
        }

        function persistPaymentContext() {
            if (!currentInstallmentId || !currentAmount) {
                return;
            }

            sessionStorage.setItem('rakbank_installment_id', String(currentInstallmentId));
            sessionStorage.setItem('rakbank_settling_amount', String(currentAmount));
        }

        function restorePaymentContext() {
            if (!currentInstallmentId) {
                currentInstallmentId = sessionStorage.getItem('rakbank_installment_id');
            }

            if (!currentAmount) {
                currentAmount = sessionStorage.getItem('rakbank_settling_amount');
            }
        }

        function clearPaymentContext() {
            sessionStorage.removeItem('rakbank_installment_id');
            sessionStorage.removeItem('rakbank_settling_amount');
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
            document.head.appendChild(script);
        }

        function launchHostedPayment(modal) {
            if (!currentCheckoutSessionId) {
                modal.find('[id^="payment-error-"]').text('Payment session expired. Please close and try again.').show();
                return;
            }

            modal.find('.payment-start-btn').prop('disabled', true);
            persistPaymentContext();

            loadCheckoutScript(function () {
                try {
                    Checkout.configure({
                        session: { id: currentCheckoutSessionId },
                    });
                    modal.modal('hide');
                    Checkout.showPaymentPage();
                } catch (error) {
                    modal.find('.payment-start-btn').prop('disabled', false);
                    modal.find('[id^="payment-error-"]').text('Unable to open secure payment. Please try again.').show();
                    console.error("RakBank checkout launch failed:", error);
                }
            });
        }

        function errorCallback(error) {
            console.log(JSON.stringify(error));
        }

        function cancelCallback() {
            console.log('Payment cancelled');
        }

        function completeCallback(response) {
            restorePaymentContext();

            if (!currentInstallmentId || !currentAmount) {
                console.error("Installment or amount missing.");
                return;
            }

            $.ajax({
                url: '{{ route("user.update.installment") }}',
                method: 'POST',
                data: { 
                    amount: currentAmount,
                    installment_id: currentInstallmentId
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    if (res.success == true) {
                        clearPaymentContext();
                        $(".modal.show").modal('hide');

                        setTimeout(function(){
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

        $('.payNowBtn').on('click', function () {
            currentInstallmentId = $(this).data('installment-id');
            currentAmount = $(this).data('amount');
            currentCheckoutSessionId = null;
        });

        $(document).on('click', '.payment-start-btn', function () {
            launchHostedPayment($(this).closest('.modal'));
        });

        $('[id^="paymentModal"]').on('shown.bs.modal', function () {
            var modal = $(this);
            var modalId = modal.attr('id');
            var installmentId = modalId.replace('paymentModal', '');
            var amountDisplayId = '#payment-amount-display-' + installmentId;
            var errorDisplayId = '#payment-error-' + installmentId;
            var loadingDisplayId = '#payment-loading-' + installmentId;
            var startBtnId = '#payment-start-btn-' + installmentId;

            currentCheckoutSessionId = null;
            $(amountDisplayId).empty();
            $(errorDisplayId).hide().empty();
            $(startBtnId).hide().prop('disabled', false);
            modal.find('.payment-secure-note').hide();
            $(loadingDisplayId).show();

            $.ajax({
                url: '{{ route("user.generate.rakBankPaySession") }}',
                method: 'POST',
                data: {
                    installment_id: installmentId,
                    return_url: window.location.href
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    $(loadingDisplayId).hide();

                    if (res.displayAmount) {
                        showPaymentAmount(amountDisplayId, res.displayAmount);
                    }

                    if (res.settlingAmount) {
                        currentAmount = res.settlingAmount;
                    }

                    if (res.success !== false && res.session && res.session.id) {
                        currentCheckoutSessionId = res.session.id;
                        $(startBtnId).show();
                        modal.find('.payment-secure-note').show();
                    } else {
                        $(errorDisplayId).text(res.error || 'Payment session could not be started. Please try again.').show();
                        console.error("Session creation failed", res);
                    }
                },
                error: function (err) {
                    $(loadingDisplayId).hide();
                    var message = 'Payment session could not be started. Please try again.';
                    try {
                        var body = JSON.parse(err.responseText);
                        if (body.error) {
                            message = body.error;
                        }
                    } catch (e) {}
                    $(errorDisplayId).text(message).show();
                    console.error("API error", err.responseText);
                }
            });
        });

        $('[id^="paymentModal"]').on('hide.bs.modal', function () {
            currentCheckoutSessionId = null;
        });

        loadCheckoutScript(function () {
            restorePaymentContext();
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