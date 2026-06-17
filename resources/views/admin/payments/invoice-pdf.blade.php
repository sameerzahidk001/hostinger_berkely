<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice PDF</title>
    <style>
        @page {
            margin: 15mm;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.6;
        }

        .invoice-container {
            background: #fff;
        }

        .row {
            display: block;
            width: 100%;
        }

        .clearfix {
            display: table;
            width: 100%;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        .left,
        .right {
            display: table-cell;
            float: none;
            vertical-align: top;
        }

        .left {
            width: 60%;
        }

        .right {
            width: 40%;
            vertical-align: bottom;
            text-align: right;
        }

        .meta-row {
            display: table;
            width: 100%;
            margin: 6px 0 12px;
        }
        .meta-row .col {
            display: table-cell;
            width: 33.33%;
            vertical-align: middle;
        }

        h1,
        h4 {
            margin: 0 0 8px 0;
        }

        .muted {
            color: #666;
        }

        .mb-0 {
            margin-bottom: 0px;
        }

        .mb-5 {
            margin-bottom: 5px;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        .mb-15 {
            margin-bottom: 15px;
        }

        .mb-20 {
            margin-bottom: 20px;
        }

        .mb-25 {
            margin-bottom: 25px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
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
            vertical-align: top;
        }

        th {
            background: #000435;
            color: #fff;
        }

        ul {
            margin: 5px 0 0 18px;
            padding: 0;
        }

        .summary {
            width: 45%;
            margin-left: auto;
            font-size: 13px;
            text-align: right;
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

        .footer {
            border-top: 1px solid #eee;
            padding-top: 8px;
            margin-top: 12px;
            font-size: 9px;
            color: #777;
            text-align: center;
        }

        /* Logo image in PDF – keep it small */
        .logo {
            width: 220px;
        }
    </style>
</head>

<body>
    @php
        // Safely prepare frequently used values
        $invoiceNo = 'INV-' . str_pad($payments->id, 6, '0', STR_PAD_LEFT);

        // Dates
        $invoiceDate = $payments->created_at ? \Carbon\Carbon::parse($payments->created_at)->format('d M Y') : 'N/A';
        $lastInstallment = $installments->sortBy('due_date')->last();
        $dueDate = ($lastInstallment && $lastInstallment->due_date) ? \Carbon\Carbon::parse($lastInstallment->due_date)->format('d M Y') : 'N/A';

        // Money
        $displayCurrency = package_currency_code($payments->currency ?? ($coursefee->currency ?? 'AED'));
        $settlingAed = (float) ($payments->price ?? 0);
        $displayAmount = $displayCurrency === 'AED'
            ? $settlingAed
            : (float) ($coursefee->price ?? $settlingAed);

        $price = $settlingAed;
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
        $totalAmount = $summaryTotal;
        $balanceDue = (float) $installments->sum('remaining_amount');

        $money = $displayCurrency === 'AED'
            ? fn($n) => 'AED ' . number_format((float) $n, 2)
            : fn($n) => 'USD ' . number_format((float) $n, 2);

        $lineAmount = $displayCurrency === 'AED' ? $settlingAed : $displayAmount;

        $words = null;
        if (class_exists('NumberFormatter')) {
            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            $words = ucfirst($formatter->format($displayCurrency === 'AED' ? $totalAmount : $displayAmount));
        }
      @endphp

    <div class="invoice-container">

        {{-- Header --}}
        <div class="clearfix mb-15">
            <div class="left" style="width:55%;">
                {{-- Use public_path so DomPDF can find the image without remote HTTP --}}
                @php $logoPath = public_path('frontend/images/pngs/logo-color.png'); @endphp
                @if (file_exists($logoPath))
                    <img class="logo mb-10" src="{{ $logoPath }}" alt="Logo">
                @endif
                <div class="muted">
                    <div class="mb-0">Berkeley School of Business, Arts & Sciences</div>
                    <div class="mb-0">Berkeley Square, Mayfair, London, W1J, UK</div>
                    <div class="mb-0">Mob: +44 7306 279111</div>
                    <div class="mb-0">Email: {{ invoice_header_email() }}</div>
                </div>
            </div>
            <div class="right text-right" style="width:45%;">
                <h1>Invoice</h1>
                <div><strong>{{ $invoiceNo }}</strong></div>
            </div>
        </div>

        <div class="meta-row mb-15">
            <div class="col"></div>
            <div class="col"></div>
            <div class="col text-right">
                <strong>Balance Due:</strong><br>
                @if($displayCurrency === 'AED')
                    {{ $money($balanceDue) }}
                @else
                    AED {{ number_format($balanceDue, 2) }}
                @endif
            </div>
        </div>


        {{-- Student / Bill-to --}}
        <div class="clearfix mb-20">
            <div class="left" style="width:55%;">
                <h4>Student Details</h4>
                <div class="mb-0"><strong>Name:</strong> {{ $user->name }}</div>
                <div class="mb-0"><strong>Email:</strong> {{ $user->email }}</div>
                <div class="mb-0"><strong>Phone:</strong> {{ $user->mobile_number }}</div>
                <div class="mb-0"><strong>Address:</strong> {{ $user->address }}</div>
                <div class="mb-0"><strong>City/Country:</strong> {{ $user->city }}, {{ $user->country }}</div>
            </div>
            <div class="right text-right" style="width: 45%">
                <div class="mb-0"><strong>Invoice Date:</strong> {{ $invoiceDate }}</div>
                <div class="mb-0"><strong>Due Date:</strong> {{ $dueDate }}</div>
            </div>
        </div>

        {{-- Line items --}}
        <table>
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Training Program & Description</th>
                    <th style="width:60px;">Qty</th>
                    <th style="width:90px;">Tax</th>
                    <th style="width:110px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        <strong>{{ $course->title ?? 'Course' }}</strong><br>
                        {{ $coursefee->package_name ?? '' }}<br>
                        @if (!empty($coursefee->short_description))
                            {{ $coursefee->short_description }}<br>
                        @endif
                        @if (!empty($coursefee->key_point))
                            {{ $coursefee->key_point }}<br>
                        @endif
                        @if (!empty($coursefee->package_includes))
                            {{ $coursefee->package_includes }}
                        @endif

                        @if (!empty($coursefee->package_feature) && is_array($coursefee->package_feature))
                            <ul>
                                @foreach ($coursefee->package_feature as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                    <td>1.00</td>
                    <td>{{ number_format($taxPercentage, 2) }}%</td>
                    <td>{{ $money($lineAmount) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="summary">
            <p><strong>Sub Total:</strong> {{ $money($summarySubtotal) }}</p>
            <p><strong>Tax ({{ number_format($taxPercentage, 2) }}%):</strong> {{ $money($taxAmount) }}</p>
            <p><strong>Total:</strong> {{ $money($summaryTotal) }}</p>
            @if($displayCurrency !== 'AED')
                <p><strong>Settling Amount (AED):</strong> AED {{ number_format($settlingAed, 2) }}</p>
            @endif
        </div>

        @if($words)
            <div class="words"><strong>Total in Words:</strong> {{ $words }}</div>
        @endif

        {{-- Installment schedule (if more than 1) --}}
        @if ($installments->count() > 1)
            <table class="mb-10" style="margin-top:10px;">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th style="width:110px;">Due Date</th>
                        <th>Due Amount</th>
                        <th style="width:125px;">Payment Date</th>
                        <th style="width:90px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($installments as $index => $inst)
                        @if($inst->user)
                            @php
                                $dueAmt = (float) ($inst->remaining_amount + $inst->paid_amount);
                                $dueDateRow = $inst->due_date ? \Carbon\Carbon::parse($inst->due_date)->format('d M Y') : 'N/A';
                                $paidDateRow = $inst->paid_date ?: 'N/A';
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $dueDateRow }}</td>
                                <td>{{ $money($dueAmt) }}</td>
                                <td>{{ $paidDateRow }}</td>
                                <td>{{ ucfirst($inst->status) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr>
                        <td colspan="2" class="text-right"><strong>Total Paid</strong></td>
                        <td colspan="3"><strong>{{ $money($installments->sum('paid_amount')) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Terms & Conditions --}}
        @if(!empty($payments->terms_conditions))
            <div class="mb-15">
                {!! $payments->terms_conditions !!}
            </div>
        @endif

        {{-- Footer --}}
        @include('partials.invoice-document-footer-pdf')

    </div>
</body>

</html>