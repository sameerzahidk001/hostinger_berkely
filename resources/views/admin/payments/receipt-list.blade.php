@extends('admin.layout.app')
@section('title', 'Receipts')
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
        <h2>Receipts List</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li class="active">
                <a>Receipts</a>
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
                    <h5>Receipts</h5>
                </div>
                <div class="ibox-content">
                    <div class="table-responsive">
                        <table class="table table-bordered dataTables-example">
                            <thead>
                                <tr>
                                    <th>Invoice No</th>
                                    <th>Invoice Issued Date</th>
                                    <th>Student</th>
                                    <th>Invoice Amount</th>
                                    <th>Payment Plan</th>
                                    <th>Due Date</th>
                                    <th>Due Amount</th>
                                    <th>Receipt No</th>
                                    <th>Paid Amount</th>
                                    <th>Paid Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($installments as $installment)
                                <tr>
                                    <td>INV-{{ str_pad($installment->payment_id, 6, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($installment->created_at)->format('Y/m/d') }}</td>
                                    <td>{{ $installment->payment->user->name ?? 'N/A' }}</td>
                                    <td>{!! format_payment_aed_amount_admin($installment->payment, (float) $installment->payment->price) !!}</td>
                                    <td>{{ $installment->installment_number }}/{{ $installment->payment->total_installment }}</td>
                                    <td>{{ \Carbon\Carbon::parse($installment->due_date)->format('Y/m/d') }}</td>
                                    <td>{!! format_payment_aed_amount_admin($installment->payment, (float) ($installment->paid_amount + $installment->remaining_amount)) !!}</td>
                                    <td>RC-{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }}</td>
                                    <td>{!! format_payment_aed_amount_admin($installment->payment, (float) $installment->paid_amount) !!}</td>
                                    <td>
                                        @if($installment->paid_date)
                                        {{ \Carbon\Carbon::parse($installment->paid_date)->format('Y/m/d') }}
                                        @else
                                        N/A
                                        @endif
                                    </td>

                                    <td>
                                        @if ($installment->payment_method)
                                        <button class="btn btn-success btn-sm edit-bank-transfer-modal" style="margin-right: 4px; margin-bottom: 4px;"
                                            data-id="{{ $installment->id }}"
                                            data-display-paid="{{ payment_display_amount_from_aed($installment->payment, (float) $installment->paid_amount) }}"
                                            data-currency="{{ payment_display_currency($installment->payment) }}"
                                            data-payment_type="{{ $installment->payment_method }}"
                                            data-notes="{{ $installment->notes }}"
                                            data-paid_date="{{ $installment->paid_date }}">
                                            <i class="fa fa-edit"></i> Edit
                                        </button>
                                        <a href="{{ route('admin.installments.manual.pay.delete', ['id' => $installment->id]) }}" style="margin-right: 4px; margin-bottom: 4px;"
                                            class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete
                                        </a>
                                        @endif
                                        <a href="{{ route('admin.installments.receipt', $installment->id) }}"
                                            target="_blank"
                                            class="btn btn-success btn-sm" style="margin-right: 4px; margin-bottom: 4px;">
                                            <i class="fa fa-file-text-o"></i> Receipt
                                        </a>
                                        <a href="{{ route('admin.payments.send-receipt', $installment->id) }}"
                                            class="btn btn-success btn-sm"><i class="fa fa-send"></i> Send
                                        </a>
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

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('.dataTables-example').DataTable({
            pageLength: 10,
            searching: true,
            lengthChange: true,
            paging: true,
            info: false,
            ordering: true,
            responsive: true,
            dom: 'lftip',
            order: []
        });

    });

    let reopenInstallmentsAfterBankTransfer = false;

    $(document).on('click', '.edit-bank-transfer-modal', function() {
        const installmentId = $(this).data('id');
        const paidAmount = $(this).data('paid_amount');
        const paymentType = $(this).data('payment_type');
        const notes = $(this).data('notes');
        const paidDate = $(this).data('paid_date');

        $('#bankTransferForm')[0].reset();

        $('#installment_id').val(installmentId);
        $('#amount').val(paidAmount || '');
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

    function setButtonLoading($btn, isLoading, loadingText = 'Processing...') {
        if (isLoading) {
            // store original text once
            if (!$btn.data('original-text')) {
                $btn.data('original-text', $btn.html());
            }
            $btn.prop('disabled', true)
                .html('<