@extends(($forPdf ?? false) ? 'layouts.pdf' : 'user.layout.app')

@section('title', 'Package Invoice')

@section('content')
    @php $isPdf = (bool) ($forPdf ?? false); @endphp
    <style>
        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                color-adjust: exact;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .invoice-container {
                margin: 0 !important;
                padding: 0 !important;
                width: 100%;
                max-width: 100%;
                box-shadow: none !important;
                border: none !important;
            }

            .print-btn {
                display: none !important;
            }
        }

        .invoice-container {
            background: #fff;
            padding: {{ $isPdf ? '8px 12px' : '24px 20px' }};
            margin: {{ $isPdf ? '0' : '20px auto' }};
            border: {{ $isPdf ? 'none' : '1px solid #e0e0e0' }};
            box-shadow: {{ $isPdf ? 'none' : '0 0 25px rgba(0, 0, 0, 0.06)' }};
            font-size: {{ $isPdf ? '12px' : '14px' }};
            line-height: {{ $isPdf ? '16px' : '20px' }};
            font-family: 'Segoe UI', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            max-width: 800px;
        }

        .invoice-container p {
            margin: 0 0 2px;
        }

        .invoice-section {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .invoice-section h4 {
            font-size: 14px;
            margin: 0 0 6px;
            padding-bottom: 2px;
            color: #555;
        }

        .invoice-footer {
            border-top: 1px solid #eee;
            padding-top: 8px;
            margin-top: 10px;
            font-size: 10px;
            line-height: 14px;
            text-align: center;
            color: #777;
            page-break-inside: avoid;
        }

        .summary {
            text-align: right;
            font-size: 12px;
            margin: 6px 0;
        }

        .summary p {
            margin: 1px 0;
        }

        .words {
            font-style: italic;
            text-align: right;
            font-size: 12px;
            margin-top: 6px;
        }

        .print-btn {
            text-align: center;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 11.5px;
        }

        th,
        td {
            padding: 5px 8px;
            border: 1px solid #ccc;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #000435;
            color: #fff;
        }
    </style>

    @php
        $displayCurrency = payment_display_currency($payments);
        $settlingAed = (float) ($payments->price ?? 0);
        $displayAmount = payment_display_amount_from_aed($payments, $settlingAed);
        $taxPercentage = (float) ($coursefee->tax_percentage ?? 0);

        if ($displayCurrency === 'AED') {
            $taxAmount = ($settlingAed * $taxPercentage) / 100;
            $summarySubtotal = $settlingAed;
            $summaryTotal = $summarySubtotal + $taxAmount;
        } else {
            $taxAmount = ($displayAmount * $taxPercentage) / 100;
            $summarySubtotal = $displayAmount;
            $summaryTotal = $summarySubtotal + $taxAmount;
        }

        $balanceDueAed = (float) $installments->sum('remaining_amount');
        $balanceDue = payment_display_amount_from_aed($payments, $balanceDueAed);
        $money = fn($n) => $displayCurrency . ' ' . number_format((float) $n, 2);
    @endphp

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="print-btn text-center" style="margin-top: 20px;">
            @unless($isPdf)
            <button class="btn btn-primary" onclick="window.print();">
                <i class="fa fa-download"></i> Print Invoice
            </button>
            @endunless
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="invoice-container">
                    <div class="invoice-header" style="display: table; width: 100%; margin-bottom: 8px;">
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
                            <h1 style="margin: 0 0 4px; font-size: 28px; line-height: 1.1;">Invoice</h1>
                            <p><strong>Invoice#</strong>
                                INV-{{ str_pad($payments->id, 6, '0', STR_PAD_LEFT) }}</p>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom: 8px;">
                        <div class="col-xs-4 pull-right text-right">
                            <p><strong>Balance Due:</strong><br>{{ $money($balanceDue) }}</p>
                        </div>
                    </div>

                    <div class="invoice-section" style="display: table; width: 100%; margin-bottom: 10px;">
                        <div style="display: table-cell; width: 50%; vertical-align: top;">
                            <h4 style="margin: 0 0 4px;">Student Details:</h4>
                            <p><strong>Name:</strong> {{ $user->name }}</p>
                            <p><strong>Email:</strong> {{ $user->email }}</p>
                            <p><strong>Phone:</strong> {{ $user->mobile_number }}</p>
                            <p><strong>Address:</strong> {{ $user->address }}</p>
                            <p><strong>City/Country:</strong> {{ $user->city }}, {{ $user->country }}</p>
                        </div>
                        <div style="display: table-cell; width: 50%; vertical-align: top; text-align: right;">
                            <p><strong>Invoice Date:</strong>
                                {{ \Carbon\Carbon::parse($payments->created_at)->format('d M Y') ?? 'N/A' }}</p>
                            <p><strong>Due Date:</strong>
                                {{ \Carbon\Carbon::parse($installments->last()->due_date)->format('d M Y') ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Training Program & Description</th>
                                <th>Tax</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $installment = $installments->first();
                                $paymentType = $installment->payment_method ?? '';
                            @endphp
                            <tr>
                                <td>1</td>
                                <td>
                                    {{ $course->title }}<br>
                                    {{ $coursefee->package_name }}<br>
                                    @if ($coursefee->short_description)
                                        {{ $coursefee->short_description }}<br>
                                    @endif
                                    @if ($coursefee->key_point)
                                        {{ $coursefee->key_point ?? '' }}<br>
                                    @endif
                                    @if ($coursefee->package_includes)
                                        {{ $coursefee->package_includes ?? '' }}
                                    @endif
                                    <ul style="list-style: none; margin: 4px 0 0 12px; padding: 0;">
                                        @if(!empty($coursefee->package_feature))
                                            @foreach ($coursefee->package_feature as $feature)
                                                <li>{{ $feature }}</li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </td>
                                <td>{{ number_format($taxPercentage, 2) }}%</td>
                                <td>{{ $money($displayAmount) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="summary">
                        <p><strong>Sub Total:</strong> {{ $money($summarySubtotal) }}</p>
                        <p><strong>Tax ({{ number_format($taxPercentage, 2) }}%):</strong>
                            {{ $money($taxAmount) }}</p>
                        <p><strong>Total:</strong> {{ $money($summaryTotal) }}</p>
                    </div>

                    @php
                        $words = null;
                        if (class_exists('NumberFormatter')) {
                            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
                            $words = ucfirst($formatter->format($displayCurrency === 'AED' ? $summaryTotal : $displayAmount));
                        }
                    @endphp

                    @if($words)
                        <div class="words">
                            <strong>Total in Words:</strong> {{ $words }}
                        </div>
                    @endif
                    @if ($installments->count() > 1)
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Due Date</th>
                                    <th>Due Amount</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($installments as $index => $installment)
                                    @if($installment->user)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ \Carbon\Carbon::parse($installment->due_date)->format('d M Y') }}</td>
                                            <td>{{ format_payment_aed_amount($payments, (float) ($installment->remaining_amount + $installment->paid_amount)) }}</td>
                                            <td>{{ $installment->paid_date ?? 'N/A' }}</td>
                                            <td>{{ ucfirst($installment->status) }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr>
                                    <td colspan="2" class="text-right"><strong>Total Paid</strong></td>
                                    <td colspan="1">
                                        <strong>{{ format_payment_aed_amount($payments, (float) $installments->sum('paid_amount')) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    @endif

                    <div class="invoice-section" style="margin-top: 12px;">
                        <div class="footer-text">
                            {!! $payments->terms_conditions !!}
                        </div>
                    </div>

                    <div class="invoice-footer">
                        @include('partials.invoice-document-footer')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

@endpush
