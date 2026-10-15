@extends('admin.layout.app')
@section('title', 'Create Payment')
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
                <strong>Create</strong>
            </li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Create</h5>
                </div>

                <div class="ibox-content">

                    <form role="form" action="{{ route('admin.payments.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <label for="student">Student</label>
                                <select name="student" id="student" class="form-control js-type-find" data-placeholder="Type to find student">
                                    <option value="">Select Student</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('student') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('student')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <label for="invoice_source">Invoice source</label>
                                <select name="invoice_source" id="invoice_source" class="form-control">
                                    <option value="course" @selected(old('invoice_source', 'course') === 'course')>Course package</option>
                                    <option value="page" @selected(old('invoice_source') === 'page')>Page fee package (no Course)</option>
                                </select>
                            </div>
                            <div class="col-lg-4 js-course-invoice-fields" style="margin-bottom: 15px;">
                                <label for="course">Batch / Course</label>
                                <select name="course" id="course" class="form-control js-type-find" data-placeholder="Type to find batch / course">
                                    <option value="">Select Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" {{ old('course') == $course->id ? 'selected' : '' }}>
                                            {{ $course->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('course')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-lg-4 js-course-invoice-fields" style="margin-bottom: 15px;">
                                <label for="package">Packages</label>
                                <select name="package" id="package" class="form-control js-type-find" data-placeholder="Type to find package">
                                    <option value="">Select Package</option>
                                </select>
                                @error('package')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-lg-4 js-page-invoice-fields" style="margin-bottom: 15px; display:none;">
                                <label for="page_fee_package">Page fee package</label>
                                <select name="page_fee_package" id="page_fee_package" class="form-control js-type-find" data-placeholder="Type to find page package">
                                    <option value="">Select Page Package</option>
                                    @foreach(($pageFeePackages ?? []) as $pkg)
                                        <option value="{{ $pkg->id }}"
                                            data-price="{{ $pkg->price }}"
                                            data-currency="{{ $pkg->currency }}"
                                            {{ (string) old('page_fee_package') === (string) $pkg->id ? 'selected' : '' }}>
                                            {{ $pkg->category?->service?->page?->page_name ?? 'Page' }}
                                            / {{ $pkg->category?->service?->name ?? 'Service' }}
                                            / {{ $pkg->category?->name ?? 'Category' }}
                                            — {{ $pkg->package_name }} ({{ $pkg->currency }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('page_fee_package')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <label for="total_amount">Total Amount</label>
                                <div class="input-group">
                                    <span class="input-group-addon currency-addon">{{ old('currency', '$') }}</span>
                                    <input type="hidden" name="currency" class="currency" value="{{ old('currency', '$') }}">
                                    <input type="text" class="form-control" id="total_amount" name="total_amount" value="{{ old('total_amount', 0) }}" readonly>
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
                                        <input type="checkbox" id="fullAmountInFirstInstallment"> Pay full amount in one go.
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div id="installmentsContainer">
                            @php
                                $oldDueDates = old('due_date', []);
                                $oldAmounts = old('amount', []);
                                $installmentCount = max(1, count($oldDueDates));
                            @endphp

                            @for($index = 0; $index < $installmentCount; $index++)
                                <div class="row installment-row" data-index="{{ $index + 1 }}">
                                    <div class="col-md-6">
                                        <label for="due_date_{{ $index + 1 }}">Due Date</label>
                                        <input type="date" class="form-control" name="due_date[]" id="due_date_{{ $index + 1 }}"
                                            value="{{ $oldDueDates[$index] ?? '' }}">
                                        @error("due_date.$index")
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="amount_{{ $index + 1 }}">Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-addon currency-addon">{{ old('currency', '$') }}</span>
                                            <input type="hidden" name="currency" class="currency" value="{{ old('currency', '$') }}">
                                            <input type="text" class="form-control" name="amount[]" id="amount_{{ $index + 1 }}"
                                                value="{{ $oldAmounts[$index] ?? '' }}">
                                        </div>
                                        @error("amount.$index")
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button type="button" class="remove-installment" title="Remove">&times;</button>
                                </div>
                            @endfor
                        </div>
                        <button type="button" class="btn btn-primary" id="addInstallment">Add Installment</button>
                        <div class="row">
                            <div class="col-md-12" style="margin-bottom: 15px;">
                                <label for="editor">Terms & Conditions</label>
                                <p class="help-block m-b-xs">
                                    Loaded from the package’s Invoice T&amp;C template
                                    (<a href="{{ route('admin.invoice-terms-templates.index') }}" target="_blank">manage templates</a>).
                                    You can still edit for this invoice.
                                </p>
                                <textarea class="form-control" id="editor"
                                    name="terms_conditions">{{ old('terms_conditions', App\Models\Payment::latest('id')->value('terms_conditions')) }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;">
                                <button class="btn btn-primary " type="submit"><i
                                        class="fa fa-check"></i>&nbsp;Submit</button>
                                <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary"><i
                                        class="fa fa-arrow-left"></i>&nbsp;Cancel</a>
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
        let installmentIndex = {{ $installmentCount }};

        $(document).ready(function () {

            $('#fullAmountInFirstInstallment').on('change', function () {
                if ($(this).is(':checked')) {
                    let totalAmount = parseFloat($('#total_amount').val()) || 0;
                    $('#amount_1').val(totalAmount);
                } else {
                    $('#amount_1').val('');
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
                        'terms' => (string) $package->resolvedTermsHtml(),
                    ];
                })->values();
                $defaultTermsHtml = old('terms_conditions', \App\Models\Payment::latest('id')->value('terms_conditions') ?? '');
                $pagePackageTermsForJs = collect($pageFeePackages ?? [])->mapWithKeys(function ($pkg) {
                    return [(string) $pkg->id => (string) $pkg->resolvedTermsHtml()];
                });
            @endphp
            const allPackages = @json($invoicePackagesForJs);
            const pagePackageTerms = @json($pagePackageTermsForJs);
            const defaultTermsHtml = @json($defaultTermsHtml);

            function applyInvoiceTerms(html) {
                const content = (html && String(html).trim() !== '') ? html : defaultTermsHtml;
                if ($('#editor').hasClass('summernote-initialized') || $('#editor').next('.note-editor').length) {
                    $('#editor').summernote('code', content || '');
                } else {
                    $('#editor').val(content || '');
                }
            }

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
                                'data-packagename': pkg.name,
                                'data-terms': pkg.terms || ''
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
                const selectedCourseId = $(this).val();
                $('#total_amount').val('');
                $('.currency').val('$');
                $('.currency-addon').text('$');
                filterPackagesByCourse(selectedCourseId, null);
            });

            $('#package').on('change', function () {
                const $selected = $(this).find(':selected');
                const price = $selected.data('price') || 0;
                const currency = $selected.data('currency') || 'AED';
                const currencySymbol = currency === 'AED' ? 'AED' : currency;
                const pkgId = String($selected.val() || '');
                const pkg = allPackages.find(function (p) { return String(p.id) === pkgId; });
                $('#total_amount').val(price);
                if ($('#fullAmountInFirstInstallment').is(':checked')) {
                    $('#amount_1').val(price);
                }
                $('.currency').val(currency);
                $('.currency-addon').text(currencySymbol);
                applyInvoiceTerms(pkg ? pkg.terms : '');
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

            // Remove installment
            $('#installmentsContainer').on('click', '.remove-installment', function () {
                const totalRows = $('#installmentsContainer .installment-row').length;

                if (totalRows > 1) {
                    $(this).closest('.installment-row').remove();
                }
            });

            const preselectedCourse = $('#course').val() || "{{ old('course') }}";
            const preselectedPackage = "{{ old('package') }}";
            if (preselectedCourse) {
                $('#course').val(String(preselectedCourse));
                filterPackagesByCourse(preselectedCourse, preselectedPackage);
                if (preselectedPackage) {
                    $('#package').trigger('change');
                }
            } else {
                filterPackagesByCourse('', null);
            }

            function toggleInvoiceSource() {
                const source = $('#invoice_source').val();
                if (source === 'page') {
                    $('.js-course-invoice-fields').hide();
                    $('.js-page-invoice-fields').show();
                    $('#course').val('');
                    $('#package').val('');
                } else {
                    $('.js-course-invoice-fields').show();
                    $('.js-page-invoice-fields').hide();
                    $('#page_fee_package').val('');
                }
            }

            $('#invoice_source').on('change', toggleInvoiceSource);
            toggleInvoiceSource();

            $('#page_fee_package').on('change', function () {
                const $selected = $(this).find(':selected');
                const price = $selected.data('price') || 0;
                const currency = $selected.data('currency') || 'AED';
                const currencySymbol = currency === 'AED' ? 'AED' : currency;
                $('#total_amount').val(price);
                if ($('#fullAmountInFirstInstallment').is(':checked')) {
                    $('#amount_1').val(price);
                }
                $('.currency').val(currency);
                $('.currency-addon').text(currencySymbol);
                const pagePkgId = String($selected.val() || '');
                applyInvoiceTerms(pagePackageTerms[pagePkgId] || '');
            });

            if ($('#invoice_source').val() === 'page' && $('#page_fee_package').val()) {
                $('#page_fee_package').trigger('change');
            }
        });
    </script>
@endpush