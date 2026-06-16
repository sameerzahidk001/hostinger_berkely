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
                <div class="ibox-title">
                    <h5>Edit Payment</h5>
                </div>

                <div class="ibox-content">
                    <form role="form" action="{{ route('admin.payments.update', $payment->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <label for="student">Students</label>
                                <select name="student" id="student" class="form-control">
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
                                <label for="course">Course</label>
                                <select name="course" id="course" class="form-control">
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
                                <select name="package" id="package" class="form-control">
                                    <option value="">Select Package</option>
                                    @foreach($packages as $package)
                                    @if($package->currency == 'AED')
                                        <option value="{{ $package->id }}"
                                            data-courseid="{{ $package->course->id }}"
                                            data-price="{{ $package->price }}"
                                            data-currency="{{ $package->currency }}"
                                            data-packagename="{{ $package->package_name }}"
                                            
                                            {{ $payment->package_id == $package->id ? 'selected' : '' }}>
                                            {{ $package->package_name }}
                                        </option>
                                    @endif
                                    @endforeach
                                </select>
                                @error('package')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <label for="total_amount">Total Amount</label>
                                <div class="input-group">
                                    <span class="input-group-addon currency-addon">{{ $payment->courseFee->currency ?? 'AED' }}</span>
                                    <input type="hidden" name="currency" class="currency" value="{{ $payment->courseFee->currency ?? 'AED' }}">
                                    <input type="text" class="form-control" id="total_amount" name="total_amount" value="{{ old('total_amount', $payment->price) }}">
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
                                <div class="row installment-row" data-index="{{ $index + 1 }}">
                                    <div class="col-md-6">
                                        <label for="due_date_{{ $index + 1 }}">Due Date</label>
                                        <input type="date" class="form-control" name="due_date[]" id="due_date_{{ $index + 1 }}"
                                            value="{{ old('due_date.' . $index, $installment->due_date) }}">
                                        @error("due_date.$index")
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="amount_{{ $index + 1 }}">Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-addon currency-addon">{{ $payment->courseFee->currency ?? 'AED' }}</span>
                                            <input type="hidden" name="currency" class="currency" value="{{ $payment->courseFee->currency ?? 'AED' }}">
                                            <input type="text" class="form-control" name="amount[]" id="amount_{{ $index + 1 }}"
                                                value="{{ old('amount.' . $index, $installment->remaining_amount + $installment->paid_amount) }}">
                                        </div>
                                        @error("amount.$index")
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button type="button" class="remove-installment" title="Remove">&times;</button>
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

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    <script>
        let installmentIndex = {{ $payment->installments->count() }};

        $(document).ready(function () {
            $('#fullAmountInFirstInstallment').on('change', function () {
                if ($(this).is(':checked')) {
                    let totalAmount = parseFloat($('#total_amount').val()) || 0;
                    $('#amount_1').val(totalAmount);
                    $('#installmentsContainer .installment-row').slice(1).remove();
                    installmentIndex = 1;
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

            $('#course').on('change', function () {
                const selectedCourseId = $(this).val();
                const packageSelect = $('#package');
                // packageSelect.val('');
                // $('#total_amount').val('');

                packageSelect.find('option').hide();
                packageSelect.find('option[value=""]').show();

                if (selectedCourseId) {
                    packageSelect.find('option').each(function () {
                        if ($(this).data('courseid') == selectedCourseId) {
                            $(this).show();
                        }
                    });
                }
            });

            $('#package').on('change', function () {
                const price = $(this).find(':selected').data('price') || 0;
                const currency = $(this).find(':selected').data('currency') || 'AED';
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
                    <div class="row installment-row" data-index="${installmentIndex}">
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
                const totalRows = $('#installmentsContainer .installment-row').length;
                if (totalRows > 1) {
                    $(this).closest('.installment-row').remove();
                    installmentIndex--;
                }
            });

            const preselectedCourse = $('#course').val();
            if (preselectedCourse) {
                $('#course').trigger('change');
            }
        });
    </script>
@endpush