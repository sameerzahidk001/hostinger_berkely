@extends('admin.layout.app')
@section('title', 'Invoices')
@push('style')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
<link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
<style>
    .page-heading {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }

    .col {
        flex: 1;
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 46px;
        height: 20px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 1px;
        bottom: 1px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked+.slider {
        background-color: #2196F3;
    }

    input:focus+.slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked+.slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col">
        <h2>Invoices List</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li class="active">
                <a>Invoices</a>
            </li>
        </ol>
    </div>
    <div class="col-auto">
        <a class="btn btn-primary" href="{{ route('admin.payments.create') }}">Create</a>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Invoices</h5>
                </div>
                <div class="ibox-content">
                    <div class="table-responsive">
                        <table class="table table-bordered dataTables-example">
                            <thead>
                                <tr>
                                    <th>Invoice No</th>
                                    <th>Invoice Date</th>
                                    <th>Course</th>
                                    <th>Student</th>
                                    {{-- <th>Installments Request</th> --}}
                                    <th>Payment Plan</th>
                                    <th>Invoice Amount</th>
                                    <th>Paid Payments</th>
                                    <th>Paid Amount</th>
                                    <th>Payment Status</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $shownPaymentIds = [];
                                @endphp
                                @foreach ($payments as $payment)
                                @php
                                $paidInstallmentsCount = $payment->installments->where('status', 'paid')->count();
                                $paidAmountSum = $payment->installments->sum('paid_amount');
                                $totalPaid = $payment->installments->sum('paid_amount');
                                $paymentStatus = 'Pending';
                                if ($totalPaid == $payment->price) {
                                $paymentStatus = 'Paid';
                                } elseif ($totalPaid > 0) {
                                $paymentStatus = 'Partial';
                                }
                                @endphp
                                <tr>
                                    <td>INV-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td>
                                    <td data-order="{{ \Carbon\Carbon::parse($payment->created_at)->timestamp }}">{{ \Carbon\Carbon::parse($payment->created_at)->format('Y/m/d') }}</td>
                                    <td>{{ $payment->course->title ?? 'N/A' }}<br><span class="text-muted">{{ $payment->courseFee->package_name ?? 'N/A' }}</span></td>
                                    <td>{{ $payment->user->name ?? 'N/A' }}</td>
                                    {{-- <td>{{ $payment->installment_request->installments_requested ?? 'Direct' }}</td> --}}
                                    <td>
                                        <a type="button" href="javascript:void(0)" class="view-installments" data-installments='@json($payment)' data-currency="{{ $payment->courseFee->currency ?? 'AED' }}">
                                            {{ $payment->installments->count() ?? 0 }}
                                            <sup>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 512 512">
                                                    <path d="M320 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l82.7 0L201.4 265.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L448 109.3l0 82.7c0 17.7 14.3 32 32 32s32-14.3 32-32l0-160c0-17.7-14.3-32-32-32L320 0zM80 32C35.8 32 0 67.8 0 112L0 432c0 44.2 35.8 80 80 80l320 0c44.2 0 80-35.8 80-80l0-112c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 112c0 8.8-7.2 16-16 16L80 448c-8.8 0-16-7.2-16-16l0-320c0-8.8 7.2-16 16-16l112 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L80 32z" />
                                                </svg>
                                            </sup>
                                        </a>
                                    </td>
                                    <td>
                                        {!! format_payment_amount_admin($payment) !!}
                                    </td>
                                    <td>{{ $paidInstallmentsCount }}/{{ $payment->installments->count() ?? 0 }}</td>
                                    <td>{!! format_payment_aed_amount_admin($payment, (float) $paidAmountSum) !!}</td>
                                    <td>
                                        <span class="badge badge-{{ $paymentStatus == 'Paid' ? 'primary' : ($paymentStatus == 'Partial' ? 'warning' : 'success') }}">
                                            {{ $paymentStatus }}
                                        </span>
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" class="status-toggle"
                                                data-payment-id="{{ $payment->id }}"
                                                {{ $payment->status === 'Active' ? 'checked' : '' }}>
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <a class="btn btn-primary btn-sm" href="{{ route('admin.payments.edit', $payment->id) }}"><i class="fa fa-edit"></i> Edit</a>
                                        <form action="{{ route('admin.payments.destroy', $payment->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this payment?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</button>
                                        </form>
                                        @if($payment->status !== 'Active')
                                            <a class="btn btn-primary btn-sm" href="{{ route('admin.payments.send-invoice', ['id' => $payment->id]) }}"><i class="fa fa-send"></i> Send</a>
                                        @endif
                                        @if($payment->installment_request && $payment->installments->count() < 1)
                                            <button class="btn btn-primary btn-sm pay-now"
                                            data-payment-id="{{ $payment->id }}"
                                            data-course-id="{{ $payment->course?->id ?? '' }}"
                                            data-course-name="{{ $payment->course?->title ?? 'N/A' }}"
                                            data-package-id="{{ $payment->courseFee?->id ?? '' }}"
                                            data-package-name="{{ $payment->courseFee?->package_name ?? 'N/A' }}"
                                            data-student-id="{{ $payment->user?->id ?? '' }}"
                                            data-student-name="{{ $payment->user?->name ?? 'N/A' }}"
                                            data-price="{{ $payment->price }}"
                                            data-request-id="{{ $payment->installment_request?->id ?? '' }}"
                                            data-installments="{{ $payment->installment_request?->installments_requested ?? 0 }}">
                                            Make Installment
                                            </button>
                                            @endif
                                            @if ($payment->id && !in_array($payment->id, $shownPaymentIds))
                                            <form action="{{ route('admin.installments.invoice.pdf') }}" method="POST" target="_blank">
                                                @csrf
                                                <input type="hidden" name="course_id" value="{{ $payment->course?->id ?? '' }}">
                                                <input type="hidden" name="user_id" value="{{ $payment->user?->id ?? '' }}">
                                                <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                                <button type="submit" class="btn btn-primary btn-sm"> <i class="fa fa-file-pdf-o"></i> Invoice</button>
                                            </form>
                                            @php
                                            $shownPaymentIds[] = $payment->id;
                                            @endphp
                                            @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bankTransferModal" tabindex="-1" role="dialog" aria-labelledby="bankTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="bankTransferForm">
            @csrf
            <input type="hidden" name="installment_id" id="installment_id">
            <input type="hidden" name="is_edit" id="is_edit" value="0">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="bankTransferModalLabel">Create Receipt (Manual)</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="amount" id="amount_received_label">Amount Received</label>
                        <div class="input-group">
                            <span class="input-group-addon" id="manual_pay_currency_addon">AED</span>
                            <input type="number" name="amount" id="amount" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="payment_type">Payment Type</label>
                        <select class="form-control" name="payment_type" id="payment_type" required>
                            <option value="">- Select Payment Type -</option>
                            @foreach (payment_type_options() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Paid Date</label>
                        <input type="date" name="paid_date" id="paid_date" class="form-control" required max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes (optional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="bankTransferSubmit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Make a Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('payments.process') }}" method="POST" class="row">
                    @csrf
                    <input type="hidden" name="payment_id" id="payment_id">
                    <div class="col-md-6 form-group">
                        <label for="package_name">Package</label>
                        <input type="text" class="form-control" name="package_name" id="package_name" readonly>
                        <input type="hidden" name="package_id" id="package_id">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="course_name">Course</label>
                        <input type="text" class="form-control" name="course_name" id="course_name" readonly>
                        <input type="hidden" name="course_id" id="course_id">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="student_name">Student</label>
                        <input type="text" class="form-control" name="student_name" id="student_name" readonly>
                        <input type="hidden" name="student_id" id="student_id">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="total_amount">Total Amount</label>
                        <input type="text" class="form-control" name="total_amount" id="total_amount" readonly>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="installments">Installments</label>
                        <input class="form-control" type="number" name="installments" id="installments">
                        <input type="hidden" name="request_id" id="request_id">
                    </div>
                    <div class="col-md-12" id="installmentDetailsContainer"></div>
                    <div class="col-md-12" style="margin-bottom: 15px;">
                        <label for="editor">Terms & Conditions</label>
                        <textarea class="form-control" id="editor"
                            name="terms_conditions">{{ old('terms_conditions', App\Models\Payment::latest('id')->value('terms_conditions')) }}</textarea>
                    </div>
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-success">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="installmentsModal" tabindex="-1" role="dialog" aria-labelledby="installmentsModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="border: 0;">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="installmentsModalLabel">Installments</h4>
            </div>
            <div class="modal-body" style="padding: 0;">
                <table class="table table-bordered" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Invoice Issued Date</th>
                            <th>Invoice Amount</th>
                            <th>Payment Plan</th>
                            <th>Due Date</th>
                            <th>Due Amount</th>
                            <th>Receipt No</th>
                            <th>Paid Amount</th>
                            <th>Paid Date</th>
                            <th>Payment Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="installmentsTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#editor').each(function() {
            if (!this.classList.contains('summernote-initialized')) {
                $(this).summernote({
                    height: 200,
                    toolbar: [
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['para', ['ul', 'ol']],
                        ['view', ['codeview']]
                    ]
                });
                this.classList.add('summernote-initialized');
            }
        });

        $('.dataTables-example').DataTable({
            pageLength: 10,
            searching: true,
            lengthChange: true,
            paging: true,
            info: false,
            ordering: true,
            responsive: true,
            dom: 'lftip',
            order: [[1, 'desc']]
        });

        $('.status-toggle').change(function() {
            var paymentId = $(this).data('payment-id');
            var status = $(this).is(':checked') ? 'Active' : 'Inactive';

            $.ajax({
                url: '/admin/payments/update-status',
                method: 'POST',
                data: {
                    payment_id: paymentId,
                    status: status,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        console.error('Error updating status');
                        $(this).prop('checked', !$(this).is(':checked'));
                    }
                },
                error: function() {
                    console.error('Error updating status');
                    $(this).prop('checked', !$(this).is(':checked'));
                }
            });
        });
    });
</script>
<script>
    let reopenInstallmentsAfterBankTransfer = false;
    const currencyRatesToAed = @json(currency_rates_to_aed());

    function paymentCurrency(payment) {
        return String(payment.currency || payment.course_fee?.currency || payment.courseFee?.currency || 'AED').toUpperCase();
    }

    function adminDisplayAmountNumber(payment, aedAmount) {
        const currency = paymentCurrency(payment);
        const aed = parseFloat(aedAmount) || 0;

        if (currency === 'AED') {
            return aed;
        }

        const settlingTotal = parseFloat(payment.price) || 0;
        const packagePrice = parseFloat(payment.course_fee?.price || payment.courseFee?.price || 0);

        if (settlingTotal > 0 && packagePrice > 0) {
            return Math.round(((aed / settlingTotal) * packagePrice) * 100) / 100;
        }

        return aed;
    }

    function setManualPayCurrency(currency) {
        $('#manual_pay_currency_addon').text(currency);
        $('#amount_received_label').text('Amount Received (' + currency + ')');
    }

    function adminBracketAed(currency, displayAmount) {
        if (currency === 'AED') {
            return (parseFloat(displayAmount) || 0).toFixed(2);
        }

        const rate = parseFloat(currencyRatesToAed[currency] || 0);
        if (rate > 0) {
            return (parseFloat(displayAmount) * rate).toFixed(2);
        }

        return (parseFloat(displayAmount) || 0).toFixed(2);
    }

    function formatAdminPaymentAmount(payment, aedAmount) {
        const currency = paymentCurrency(payment);
        const display = adminDisplayAmountNumber(payment, aedAmount);

        if (currency === 'AED') {
            return 'AED ' + display.toFixed(2);
        }

        const bracket = adminBracketAed(currency, display);

        return currency + ' ' + display.toFixed(2) + ' <span class="text-muted">(AED ' + bracket + ')</span>';
    }

    $(document).on('click', '.open-bank-transfer-modal', function() {
        const installmentId = $(this).data('id');
        const displayAmount = $(this).data('display-amount');
        const currency = $(this).data('currency') || 'AED';

        $('#bankTransferForm')[0].reset();

        $('#installment_id').val(installmentId);
        setManualPayCurrency(currency);
        $('#amount').val(displayAmount || '');
        $('#payment_type').val('');
        $('#notes').val('');
        $('#paid_date').val('');
        $('#is_edit').val('0');

        reopenInstallmentsAfterBankTransfer = true;

        $('.modal').modal('hide');

        setTimeout(function() {
            $('#bankTransferModal').modal('show');
        }, 500);
    });

    $(document).on('click', '.edit-bank-transfer-modal', function() {
        const installmentId = $(this).data('id');
        const displayPaid = $(this).data('display-paid');
        const currency = $(this).data('currency') || 'AED';
        const paymentType = $(this).data('payment_type');
        const notes = $(this).data('notes');
        const paidDate = $(this).data('paid_date');

        $('#bankTransferForm')[0].reset();

        $('#installment_id').val(installmentId);
        setManualPayCurrency(currency);
        $('#amount').val(displayPaid || '');
        $('#payment_type').val(paymentType || '');
        $('#notes').val(notes || '');
        $('#paid_date').val(paidDate || '');
        $('#is_edit').val('1');

        reopenInstallmentsAfterBankTransfer = true;

        $('.modal').modal('hide');

        setTimeout(function() {
            $('#bankTransferModal').modal('show');
        }, 500);
    });

    $('#bankTransferModal').on('hidden.bs.modal', function() {
        if (reopenInstallmentsAfterBankTransfer) {
            $('#installmentsModal').modal('show');
            reopenInstallmentsAfterBankTransfer = false;
        }
    });

    $(document).on('click', '.view-installments', function() {
        const installments = $(this).data('installments');
        const currency = $(this).data('currency');
        let rowsHtml = '';
        installments.installments.forEach((installment, index) => {
            const paymentId = installment.payment_id.toString();
            const paddedId = paymentId.padStart(6, '0');
            const installmentId = installment.id.toString();
            const receipt = installmentId.padStart(6, '0');
            const date = new Date(installment.created_at);
            const day = String(date.getUTCDate()).padStart(2, '0');
            const month = date.toLocaleString('default', {
                month: 'short',
                timeZone: 'UTC'
            });
            const year = date.getUTCFullYear();
            const formattedDate = `${day}-${month}-${year}`;
            rowsHtml += `
                <tr>
                    <td>INV-${paddedId}</td>
                    <td>${formattedDate}</td>
                    <td>${formatAdminPaymentAmount(installments, installments.price)}</td>
                    <td>${installment.installment_number}/${installments.total_installment}</td>
                    <td>${installment.due_date ?? 'N/A'}</td>
                    <td>${formatAdminPaymentAmount(installments, parseFloat(installment.paid_amount) + parseFloat(installment.remaining_amount))}</td>
                    <td>${installment.status === 'paid' ? 'RC-' + receipt : 'N/A'}</td>
                    <td>${installment.paid_amount ? formatAdminPaymentAmount(installments, installment.paid_amount) : 'N/A'}</td>
                    <td>${installment.paid_date ?? 'N/A'}</td>
                    <td>${installment.status.charAt(0).toUpperCase() + installment.status.slice(1)}</td>
                    <td>
                        ${
                            parseFloat(installment.remaining_amount) > 0
                            ? `
                                <button class="btn btn-success btn-sm open-bank-transfer-modal" 
                                    data-id="${installment.id}" 
                                    data-display-amount="${adminDisplayAmountNumber(installments, installment.remaining_amount)}"
                                    data-currency="${paymentCurrency(installments)}">
                                    <i class="fa fa-bank"></i> Pay (Manual)
                                </button>
                            `
                            : (
                                installment.payment_method
                                ? `
                                    <button class="btn btn-success btn-sm edit-bank-transfer-modal" style="margin-right: 4px; margin-bottom: 4px;"
                                        data-id="${installment.id}" 
                                        data-display-paid="${adminDisplayAmountNumber(installments, installment.paid_amount)}"
                                        data-currency="${paymentCurrency(installments)}"
                                        data-payment_type="${installment.payment_method}" 
                                        data-notes="${installment.notes ?? ''}"
                                        data-paid_date="${installment.paid_date ?? ''}">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                    <a href="/admin/installments/manual-pay/delete/${installment.id}" style="margin-right: 4px; margin-bottom: 4px;"
                                        class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete
                                    </a>
                                `
                                : `
                                    <a href="/admin/installments/receipt/${installment.id}" 
                                        target="_blank" 
                                        class="btn btn-success btn-sm" style="margin-right: 4px; margin-bottom: 4px;">
                                        <i class="fa fa-file-text-o"></i> Receipt
                                    </a>
                                    <a href="/admin/payments/send-receipt/${installment.id}" 
                                        class="btn btn-success btn-sm"><i class="fa fa-send"></i> Send
                                    </a>
                                `
                            )
                        }
                    </td>
                </tr>
            `;
        });

        $('#installmentsTableBody').html(rowsHtml);

        $('.modal').modal('hide');

        setTimeout(function() {
            $('#installmentsModal').modal('show');
        }, 500);
    });

    $(document).ready(function() {
        $('#bankTransferForm').on('submit', function(e) {
            e.preventDefault();

            const $submitBtn = $('#bankTransferSubmit');
            setButtonLoading($submitBtn, true, 'Saving payment...');

            const formData = {
                installment_id: $('#installment_id').val(),
                amount: $('#amount').val(),
                payment_type: $('#payment_type').val(),
                paid_date: $('#paid_date').val(),
                notes: $('#notes').val(),
                is_edit: $('#is_edit').val(),
                _token: $('input[name="_token"]').val()
            };

            $.ajax({
                url: "{{ route('admin.installments.manual.pay') }}",
                method: "POST",
                data: formData,
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        $('#bankTransferModal').modal('hide');
                        location.reload();
                    } else {
                        // Validation/logic error from backend (200 status but success=false)
                        alert(response.message || 'Something went wrong.');
                    }
                },
                error: function(xhr) {
                    let msg = 'Error occurred while processing the payment.';

                    if (xhr.responseJSON) {
                        // Laravel validation (422) or thrown validation exception
                        if (xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        if (xhr.responseJSON.errors) {
                            // Collect field-specific messages
                            msg = Object.values(xhr.responseJSON.errors).flat().join("\n");
                        }
                    }

                    alert(msg);
                },
                complete: function() {
                    // Always restore the button state, even on error
                    setButtonLoading($submitBtn, false);
                }
            });
        });
    });
    $(document).ready(function() {
        @if($errors -> any())
        $('#payment_id').val(@json(old('payment_id')));
        $('#student_name').val(@json(old('student_name')));
        $('#student_id').val(@json(old('student_id')));
        $('#course_id').val(@json(old('course_id')));
        $('#course_name').val(@json(old('course_name')));
        $('#package_id').val(@json(old('package_id')));
        $('#package_name').val(@json(old('package_name')));
        $('#total_amount').val(@json(old('total_amount')));
        $('#installments').val(@json(old('installments')));
        $('#request_id').val(@json(old('request_id')));

        renderInstallmentFields(@json(old('installments')), @json(old('total_amount')));
        $('#paymentModal').modal('show');
        @endif

        $('.pay-now').click(function() {
            let paymentId = $(this).data('payment-id');
            let courseId = $(this).data('course-id');
            let courseName = $(this).data('course-name');
            let packageId = $(this).data('package-id');
            let packageName = $(this).data('package-name');
            let studentId = $(this).data('student-id');
            let studentName = $(this).data('student-name');
            let price = $(this).data('price');
            let requestId = $(this).data('request-id');
            let installments = $(this).data('installments');

            $('#payment_id').val(paymentId);
            $('#student_name').val(studentName);
            $('#student_id').val(studentId);
            $('#course_id').val(courseId);
            $('#course_name').val(courseName);
            $('#package_id').val(packageId);
            $('#package_name').val(packageName);
            $('#total_amount').val(price);
            $('#installments').val(installments).trigger('input');
            $('#request_id').val(requestId);
            $('#paymentModal').modal('show');
        });

        $('#installments').on('input', function() {
            let numInstallments = parseInt($(this).val());
            let totalAmount = parseFloat($('#total_amount').val());
            renderInstallmentFields(numInstallments, totalAmount);
        });

        function renderInstallmentFields(numInstallments, totalAmount) {
            let container = $('#installmentDetailsContainer');
            container.empty();

            let oldDates = @json(old('installment_dates', []));
            let oldAmounts = @json(old('installment_amounts', []));

            if (numInstallments > 0) {
                let perInstallmentAmount = (totalAmount / numInstallments).toFixed(2);

                for (let i = 0; i < numInstallments; i++) {
                    let dateVal = oldDates[i] ?? '';
                    let amountVal = oldAmounts[i] ?? perInstallmentAmount;

                    container.append(`
                        <div class="col-md-6 form-group">
                            <label for="installment_date_${i + 1}">Installment ${i + 1} Due Date</label>
                            <input type="date" class="form-control installment-date" name="installment_dates[]" id="installment_date_${i + 1}" value="${dateVal}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="installment_amount_${i + 1}">Installment ${i + 1} Amount</label>
                            <input type="number" class="form-control installment-amount" name="installment_amounts[]" id="installment_amount_${i + 1}" value="${amountVal}" min="0" required>
                        </div>
                    `);
                }

                $('.installment-amount').on('input', function() {
                    validateInstallmentAmounts(totalAmount);
                });
            }
        }
    });

    function setButtonLoading($btn, isLoading, loadingText = 'Processing...') {
        if (isLoading) {
            // store original text once
            if (!$btn.data('original-text')) {
                $btn.data('original-text', $btn.html());
            }
            $btn.prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> ' + loadingText);
        } else {
            $btn.prop('disabled', false)
                .html($btn.data('original-text') || 'Submit');
        }
    }
</script>
@endpush