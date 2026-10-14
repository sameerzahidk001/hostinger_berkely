@extends('admin.layout.app')
@section('title', 'Edit Payment')
@push('style')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
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
        .installment-row {
            position: relative;
            margin-bottom: 20px;
            padding-right: 50px;
        }
        .remove-installment {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #dc3545;
            color: #fff;
            border: none;
            margin-right: 15px;
            padding: 5px 11px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 25px;
            line-height: 1;
        }
    </style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col">
        <h2>Payments</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li>
                <a href="{{ route('admin.payments.index') }}">Payments</a>
            </li>
            <li class="active">
                <strong>Edit</strong>
            </li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title" style="display:flex;justify-content:space-between;align-items:center;">
                    <h5 style="margin:0;">Edit Payment</h5>
                    <form method="POST" action="{{ route('admin.payments.push-crm', $payment->id) }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn btn-info btn-sm">Push to Zoho CRM</button>
                    </form>
                </div>
                @if(\Illuminate\Support\Facades\Schema::hasColumn('payments', 'zoho_crm_sync_status'))
                    <div class="alert alert-{{ ($payment->zoho_crm_sync_status ?? '') === 'failed' ? 'danger' : 'info' }}" style="margin:12px 15px 0;">
                        <strong>CRM:</strong>
                        INV-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}
                        · Status: {{ $payment->zoho_crm_sync_status ?: 'not synced' }}
                        @if(!empty($payment->zoho_crm_invoice_id))
                            · CRM Invoice ID: {{ $payment->zoho_crm_invoice_id }}
                        @endif
                        @if(!empty($payment->zoho_crm_last_error))
                            <br><span class="text-danger">{{ $payment->zoho_crm_last_error }}</span>
                        @endif
                    </div>
                @endif

                <div class="ibox-content">
                    <form role="form" action="{{ route('admin.payments.update', $payment->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <label for="student">Student</label>
                                <select name="student" id="student" class="form-control js-type-find" data-placeholder="Type to find student">
                                    <option value="">Select Student</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $payment->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('student')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <label for="course">Batch / Course</label>
                                <select name="course" id="course" class="form-control js-type-find" data-placeholder="Type to find batch / course">
                                    <option value="">Select Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" {{ $payment->course_id == $course->id ? 'selected' : '' }}>
                                            {{ $course->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('course')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <label for="package">Packages</label>
                                <select name="package" id="package" class="form-control js-type-find" data-placeholder="Type to find package">
                                    <option value="">Select Package</option>
                                </select>
                                @error('package')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <label for="total_amount">Total Amount</label>
                                <div class="input-group">
                                    <span class="input-group-addon currency-addon">{{ payment_display_currency($payment) }}</span>
                                    <input type="hidden" name="currency" class="currency" value="{{ payment_display_currency($payment) }}">
                                    <input type="text" class="form-control" id="total_amount" name="total_amount" value="{{ old('total_amount', number_format(payment_display_amount_from_aed($payment, (float) $payment->price), 2, '.', '')) }}">
                                </div>
                                @error('total_amount')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-lg-12">
                                <div style="display: table-cell; vertical-align: middle;">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" id="fullAmountInFirstInstallment" {{ $payment->total_installment == 1 ? 'checked' : '' }}> Pay full amount in one go.
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div id="installmentsContainer">
                            @foreach($payment->installments as $index => $installment)
                                @php
                                    $hasReceipt = (float) $installment->paid_amount > 0
                                        || in_array($installment->status, ['paid', 'partial'], true);
                                @endphp
                                <div class="row installment-row" data-index="{{ $index + 1 }}" data-has-receipt="{{ $hasReceipt ? '1' : '0' }}">
                                    <input type="hidden" name="installment_id[]" value="{{ $installment->id }}">
                                    <div class="col-md-6">
                                        <label for="due_date_{{ $index + 1 }}">Due Date</label>
                                        <input type="date" class="form-control" name="due_date[]" id="due_date_{{ $index + 1 }}"
                                            value="{{ old('due_date.' . $index, $installment->due_date) }}">
                                        @error("due_date.$index")
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                        @if($hasReceipt)
                                            <small class="text-success">Receipt kept ({{ ucfirst($installment->status) }})</small>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label for="amount_{{ $index + 1 }}">Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-addon currency-addon">{{ payment_display_currency($payment) }}</span>
                                            <input type="hidden" name="currency" class="currency" value="{{ payment_display_currency($payment) }}">
                                            <input type="text" class="form-control" name="amount[]" id="amount_{{ $index + 1 }}"
                                                value="{{ old('amount.' . $index, number_format(payment_display_amount_from_aed($payment, (float) ($installment->remaining_amount + $installment->paid_amount)), 2, '.', '')) }}">
                                        </div>
                                        @error("amount.$index")
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    @if(! $hasReceipt)
                                        <button type="button" class="remove-installment" title="Remove">&times;</button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-primary" id="addInstallment">Add Installment</button>
                        <div class="row">
                            <div class="col-md-12" style="margin-bottom: 15px;">
                                <label for="editor">Terms & Conditions</label>
                                <textarea class="form-control" id="editor" name="terms_conditions">{{ old('terms_conditions', $payment->terms_conditions) }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;">
                                <button class="btn btn-primary" type="submit"><i class="fa fa-check"></i>&nbsp;Update</button>
                                <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i>&nbsp;Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@include('admin.partials.type-find-selects')
@push('script')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    <script>
        let installmentIndex = {{ $payment->installments->count() }};

        $(document).ready(function () {
            $('#fullAmountInFirstInstallment').on('change', function () {
                if ($(this).is(':checked')) {
                    const $rowsWithReceipt = $('#installmentsContainer .installment-row[data-has-receipt="1"]');
                    if ($rowsWithReceipt.length > 1 || ($rowsWithReceipt.length === 1 && !$rowsWithReceipt.first().is(':first-child'))) {
                        $(this).prop('checked', false);
                        alert('Cannot collapse to one installment while paid receipts exist on later installments.');
                        return;
                    }
                    let totalAmount = parseFloat($('#total_amount').val()) || 0;
                    $('#amount_1').val(totalAmount);
                    $('#installmentsContainer .installment-row').slice(1).filter(function () {
                        return $(this).attr('data-has-receipt') !== '1';
                    }).remove();
                    installmentIndex = $('#installmentsContainer .installment-row').length || 1;
                }
            });

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

            @php
                $invoicePackagesForJs = $packages->map(function ($package) {
                    return [
                        'id' => (string) $package->id,
                        'course_id' => (string) $package->courses_id,
                        'price' => $package->price,
                        'currency' => $package->currency,
                        'name' => $package->package_name,
                        'label' => $package->package_name . ' (' . $package->currency . ')',
                    ];
                })->values();
            @endphp
            const allPackages = @json($invoicePackagesForJs);

            function filterPackagesByCourse(selectedCourseId, preferPackageId) {
                const $package = $('#package');
                const courseId = selectedCourseId ? String(selectedCourseId) : '';
                const keepId = preferPackageId ? String(preferPackageId) : '';

                $package.empty().append($('<option>', { value: '', text: 'Select Package' }));

                if (courseId) {
                    allPackages
                        .filter(function (pkg) { return String(pkg.course_id) === courseId; })
                        .forEach(function (pkg) {
                            const $opt = $('<option>', {
                                value: pkg.id,
                                text: pkg.label
                            });
                            $opt.attr({
                                'data-courseid': pkg.course_id,
                                'data-price': pkg.price,
                                'data-currency': pkg.currency,
                                'data-packagename': pkg.name
                            });
                            $package.append($opt);
                        });
                }

                if (keepId && $package.find('option[value="' + keepId + '"]').length) {
                    $package.val(keepId);
                } else {
                    $package.val('');
                }

                if ($package.hasClass('select2-hidden-accessible')) {
                    $package.select2('destroy');
                }
                if (typeof window.initTypeFindSelects === 'function') {
                    window.initTypeFindSelects($package.parent());
                }
            }

            $('#course').on('change', function () {
                filterPackagesByCourse($(this).val(), null);
                $('#total_amount').val('');
            });

            $('#package').on('change', function () {
                const $selected = $(this).find(':selected');
                const price = $selected.data('price') || 0;
                const currency = $selected.data('currency') || 'AED';
                $('#total_amount').val(price);
                if ($('#fullAmountInFirstInstallment').is(':checked')) {
                    $('#amount_1').val(price);
                }
                $('.currency').val(currency);
                $('.currency-addon').text(currency);
            });

            $('#addInstallment').on('click', function () {
                installmentIndex++;
                const currencySymbol = $('.currency-addon').first().text().trim();

                const installmentHtml = `
                    <div class="row installment-row" data-index="${installmentIndex}" data-has-receipt="0">
                        <input type="hidden" name="installment_id[]" value="">
                        <div class="col-md-6">
                            <label for="due_date_${installmentIndex}">Due Date</label>
                            <input type="date" class="form-control" name="due_date[]" id="due_date_${installmentIndex}">
                        </div>
                        <div class="col-md-6">
                            <label for="amount_${installmentIndex}">Amount</label>
                            <div class="input-group">
                                <span class="input-group-addon currency-addon">${currencySymbol}</span>
                                <input type="text" class="form-control" name="amount[]" id="amount_${installmentIndex}">
                            </div>
                        </div>
                        <button type="button" class="remove-installment" title="Remove">&times;</button>
                    </div>
                `;

                $('#installmentsContainer').append(installmentHtml);
            });

            $('#installmentsContainer').on('click', '.remove-installment', function () {
                const $row = $(this).closest('.installment-row');
                if ($row.attr('data-has-receipt') === '1') {
                    alert('Paid installments with receipts cannot be removed.');
                    return;
                }
                const totalRows = $('#installmentsContainer .installment-row').length;
                if (totalRows > 1) {
                    $row.remove();
                    installmentIndex = $('#installmentsContainer .installment-row').length;
                }
            });

            const preselectedCourse = $('#course').val();
            const preselectedPackage = '{{ $payment->package_id }}';
            if (preselectedCourse) {
                filterPackagesByCourse(preselectedCourse, preselectedPackage);
            }
        });
    </script>
@endpush