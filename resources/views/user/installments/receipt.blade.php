@extends(($forPdf ?? false) ? 'layouts.pdf' : 'user.layout.app')

@section('title', 'Installment Receipt')

@section('content')
    @php $isPdf = (bool) ($forPdf ?? false); @endphp
    <style>
        .receipt-box {
            background: #fff;
            padding: {{ $isPdf ? '8px 12px' : '24px 20px' }};
            margin: {{ $isPdf ? '0' : '20px auto' }};
            border: {{ $isPdf ? 'none' : '1px solid #e0e0e0' }};
            box-shadow: {{ $isPdf ? 'none' : '0 0 25px rgba(0, 0, 0, 0.06)' }};
            font-size: {{ $isPdf ? '12px' : '14px' }};
            line-height: {{ $isPdf ? '16px' : '20px' }};
            font-family: DejaVu Sans, 'Segoe UI', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            max-width: 800px;
        }

        .receipt-box p {
            margin: 0 0 2px;
        }

        .receipt-section {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .receipt-section h4 {
            font-size: 14px;
            border-bottom: 1px solid #ddd;
            margin: 0 0 6px;
            padding-bottom: 4px;
            color: #555;
        }

        .receipt-section p {
            margin: 0 0 3px;
        }

        .print-btn {
            text-align: center;
            margin-top: 20px;
        }

        .receipt-footer {
            border-top: 1px solid #eee;
            padding-top: 8px;
            margin-top: 12px;
            font-size: 10px;
            line-height: 14px;
            text-align: center;
            color: #777;
            page-break-inside: avoid;
        }

        .receipt-header {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .receipt-meta-row {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .receipt-meta-row p {
            margin: 0;
        }

        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }

            html, body {
                margin: 0;
                padding: 0;
            }

            .print-btn {
                display: none !important;
            }

            .receipt-box {
                box-shadow: none !important;
                border: none !important;
                max-width: 100%;
                padding: 0;
            }
        }
    </style>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="print-btn text-center" style="margin-top: 20px;">
            @unless($isPdf)
            <button class="btn btn-primary" onclick="window.print();">
                <i class="fa fa-download"></i> Print Receipt
            </button>
            @endunless
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div id="receipt-content" class="receipt-box">
                    <div class="receipt-header">
                        <div style="display: table-cell; width: 55%; vertical-align: top;">
                            @php $logoPath = public_path('frontend/images/pngs/logo-color.png'); @endphp
                            @if($isPdf && file_exists($logoPath))
                                <img src="{{ $logoPath }}" alt="Logo" style="width: 170px; height: auto; margin: 0 0 4px;">
                            @else
                                <img src="{{ asset('frontend/images/pngs/logo-color.png') }}" alt="Logo" style="width: 170px; height: auto; margin: 0 0 4px;">
                            @endif
                            <p><strong>Berkeley School of Business, Arts & Sciences</strong></p>
                            <p>Berkeley Square, Mayfair, London, W1J, UK</p>
                            <p>Mob: +44 7306 279111</p>
                            <p>Email: {{ invoice_header_email() }}</p>
                        </div>
                        <div style="display: table-cell; width: 45%; vertical-align: top; text-align: right;">
                            <h1 style="margin: 0 0 4px; font-size: 28px; line-height: 1.1;">Receipt</h1>
                            <p><strong>Receipt#</strong> RC-{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }}</p>
                            <p><strong>Invoice#</strong> INV-{{ str_pad($installment->payment->id, 6, '0', STR_PAD_LEFT) }}</p>
                            <p><strong>Payment Plan</strong> {{ $installment->installment_number }}/{{ $installment->payment->total_installment }}</p>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <h4>Student Details</h4>
                        <p><strong>Name:</strong> {{ $installment->user->name ?? 'N/A' }}</p>
                        <p><strong>Email:</strong> {{ $installment->user->email ?? 'N/A' }}</p>
                        <p><strong>Phone:</strong> {{ $installment->user->mobile_number ?? 'N/A' }}</p>
                        <p><strong>Address:</strong> {{ $installment->user->address ?? 'N/A' }}</p>
                    </div>

                    <div class="receipt-section">
                        <h4>Course Details</h4>
                        <p><strong>Course Title:</strong> {{ $installment->payment->course->title ?? 'N/A' }}</p>
                        <p><strong>Package Name:</strong> {{ $installment->payment->courseFee->package_name ?? 'N/A' }}</p>
                    </div>

                    <div class="row receipt-meta-row">
                        <div class="col-xs-3 text-left">
                            <p><strong>Paid Amount:</strong><br>{{ format_payment_aed_amount($installment->payment, (float) $installment->paid_amount) }}</p>
                        </div>
                        <div class="col-xs-3 text-center">
                            <p><strong>Due Date:</strong><br>{{ $installment->due_date ?? 'N/A' }}</p>
                        </div>
                        <div class="col-xs-3 text-center">
                            <p><strong>Paid Date:</strong><br>{{ $installment->paid_date ?? 'N/A' }}</p>
                        </div>
                        <div class="col-xs-3 text-right">
                            <p><strong>Balance Due:</strong><br>{{ format_payment_aed_amount($installment->payment, (float) $installment->remaining_amount) }}</p>
                        </div>
                    </div>

                    <div class="receipt-footer">
                        @include('partials.invoice-document-footer')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

@endpush
