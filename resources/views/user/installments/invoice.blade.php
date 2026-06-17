@extends('user.layout.app')

@section('title', 'Package Invoice')

@section('content')
    <style>
        @media print {
            @page {
                size: A4;
                margin: 0;
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
                page-break-after: auto;
            }

            .print-btn {
                display: none !important;
            }
        }


        .invoice-container {
            background: #fff;
            padding: 40px 30px;
            margin: 30px auto;
            border: 1px solid #e0e0e0;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.06);
            font-size: 15px;
            line-height: 24px;
            font-family: 'Segoe UI', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            max-width: 800px;
        }

        .invoice-section {
            margin-bottom: 30px;
        }

        .invoice-section h4 {
            font-size: 18px;
            margin-bottom: 12px;
            padding-bottom: 6px;
            color: #555;
        }

        .invoice-section p {
            margin-bottom: 8px;
        }

        .invoice-footer {
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 12px;
            text-align: center;
            color: #777;
        }

        .summary {
            text-align: right;
            font-size: 13px;
            margin: 10px 0;
        }

        .summary p {
            margin: 2px 0;
        }

        .words {
            font-style: italic;
            text-align: right;
            font-size: 13px;
            margin-top: 10px;
        }

        .print-btn {
            text-align: center;
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 12.5px;
        }

        th,
        td {
            padding: 8px 10px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background: #000435;
            color: #fff;
        }
    </style>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="print-btn text-center" style="margin-top: 20px;">
            <button class="btn btn-primary" onclick="window.print();">
                <i class="fa fa-download"></i> Print Invoice
            </button>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="invoice-container">
                    <!-- Your invoice content starts -->

                    <div class="invoice-header" style="display: table; width: 100%; margin-bottom: 15px;">
                        <div style="display: table-cell; width: 50%; vertical-align: top;">
                            <img src="{{ asset('frontend/images/pngs/logo-color.png') }}" alt="Logo"
                                style="width: 280px; margin-bottom: 10px;">
                            <p style="margin-bottom: 0;">Berkeley School of Business, Arts & Sciences</p>
                            <p style="margin-bottom: 0;">Berkeley Square, Mayfair, London, W1J, UK</p>
                            <p style="margin-bottom: 0;">Mob: +44 7306 279111</p>
                            <p>Email: {{ invoice_header_email() }}</p>
                        </div>
                        <div style="display: table-cell; width: 50%; vertical-align: bottom; text-align: right;">
                            <h1 style="margin-top: 0;">Invoice</h1>
                            <p style="margin: 0;"><strong>Invoice#</strong>
                                INV-{{ str_pad($payments->id, 6, '0', STR_PAD_LEFT) }}</p>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom: 15px;">
                        <div class="col-xs-4 pull-right text-right">
                            <p><strong>Balance
                                    Due:</strong><br>AED {{ $installments->sum('remaining_amount') }}
                            </p>
                        </div>
                    </div>

                    <div class="invoice-section" style="display: table; width: 100%; margin-bottom: 15px;">
                        <div style="display: table-cell; width: 50%; vertical-align: top;">
                            <h4 style="margin: 0px">Student Details:</h4>
                            <p style="margin: 0px"><strong>Name:</strong> {{ $user->name }}</p>
                            <p style="margin: 0px"><strong>Email:</strong> {{ $user->email }}</p>
                            <p style="margin: 0px"><strong>Phone:</strong> {{ $user->mobile_number }}</p>
                            <p style="margin: 0px"><strong>Address:</strong> {{ $user->address }}</p>
                            <p style="margin: 0px"><strong>City/Country:</strong> {{ $user->city }}, {{ $user->country }}</p>
                        </div>
                        <div style="display: table-cell; width: 50%; vertical-align: bottom; text-align: right;">
                            <p style="margin: 0px"><strong>Invoice Date:</strong>
                                {{ \Carbon\Carbon::parse($payments->created_at)->format('d M Y') ?? 'N/A' }}</p>
                            <p style="margin: 0px"><strong>Due Date:</strong>
                                {{ \Carbon\Carbon::parse($installments->last()->due_date)->format('d M Y') ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Training Program & Description</th>
                                <th>Qty</th>
                                <th>Tax</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $installment = $installments->first();
                                $paymentType = $installment->payment_method ?? '';
                                $price = $payments->price;
                                $taxPercentage = $coursefee->tax_percentage ?? 0;
                                $taxAmount = ($price * $taxPercentage) / 100;
                                $totalAmount = $price + $taxAmount;
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
                                    <ul style="list-style: none; margin: 5px 0 0 15px; padding: 0;">
                                        @if(!empty($coursefee->package_feature))
                                            @foreach ($coursefee->package_feature as $feature)
                                                <li>{{ $feature }}</li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </td>
                                <td>1.00</td>
                                <td>{{ number_format($taxPercentage, 2) }}%</td>
                                <td>AED {{ $payments->price }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="summary">
                        <p><strong>Sub Total:</strong> AED {{ number_format($price, 2) }}</p>
                        <p><strong>Tax ({{ number_format($taxPercentage, 2) }}%):</strong>
                            AED {{ number_format($taxAmount, 2) }}</p>
                        <p><strong>Total:</strong> AED {{ number_format($totalAmount, 2) }}</p>
                    </div>

                    @php
                        $words = null;
                        if (class_exists('NumberFormatter')) {
                            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
                            $words = ucfirst($formatter->format($totalAmount));
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
                                            <td>AED {{ number_format($installment->remaining_amount + $installment->paid_amount, 2) }}</td>
                                            <td>{{ $installment->paid_date ?? 'N/A' }}</td>
                                            <td>{{ ucfirst($installment->status) }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr>
                                    <td colspan="2" class="text-right"><strong>Total Paid</strong></td>
                                    <td colspan="1">
                                        <strong>AED {{ number_format($installments->sum('paid_amount'), 2) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    @endif

                    <div class="invoice-section" style="margin-top: 30px;">
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