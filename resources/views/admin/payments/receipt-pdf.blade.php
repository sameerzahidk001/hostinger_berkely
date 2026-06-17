<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Receipt PDF</title>
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

        /* Dompdf-safe two-column header */
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
            width: 55%;
        }

        .right {
            width: 45%;
            vertical-align: bottom;
            text-align: right;
        }

        /* 3-col meta row */
        .meta-row {
            display: table;
            width: 100%;
            margin: 6px 0 12px;
        }

        .meta-row .col {
            display: table-cell;
            width: 25%;
            vertical-align: middle;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        h1,
        h4 {
            margin: 0 0 8px 0;
        }

        .muted {
            color: #666;
        }

        .mb-0 {
            margin-bottom: 0
        }

        .mb-5 {
            margin-bottom: 5px
        }

        .mb-10 {
            margin-bottom: 10px
        }

        .mb-15 {
            margin-bottom: 15px
        }

        .mb-20 {
            margin-bottom: 20px
        }

        /* Sections */
        .section {
            margin-bottom: 20px;
        }

        .section h4 {
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 10px;
            padding-bottom: 6px;
            color: #555;
        }

        .footer {
            border-top: 1px solid #eee;
            padding-top: 8px;
            margin-top: 12px;
            font-size: 9px;
            color: #777;
            text-align: center;
        }

        .logo {
            width: 220px;
        }
    </style>
</head>

<body>
    @php
        use Carbon\Carbon;

        // Ids & labels
        $rcNo = 'RC-' . str_pad($installment->id ?? 0, 6, '0', STR_PAD_LEFT);
        $invNo = 'INV-' . str_pad(optional($installment->payment)->id ?? 0, 6, '0', STR_PAD_LEFT);
        $plan = ($installment->installment_number ?? 0) . '/' . (optional($installment->payment)->total_installment ?? 0);

        // Dates
        $dueDate = $installment->due_date ? Carbon::parse($installment->due_date)->format('d M Y') : 'N/A';
        $paidDate = $installment->paid_date ? Carbon::parse($installment->paid_date)->format('d M Y') : 'N/A';

        // Amounts
        $paidAmountAed = (float) ($installment->paid_amount ?? 0);
        $balanceDueAed = (float) ($installment->remaining_amount ?? 0);
        $payment = $installment->payment;
        $displayCurrency = payment_display_currency($payment);
        $paidAmount = payment_display_amount_from_aed($payment, $paidAmountAed);
        $balanceDue = payment_display_amount_from_aed($payment, $balanceDueAed);

        // Helpers
        $money = fn($n) => $displayCurrency . ' ' . number_format((float) $n, 2);

        // Logo path (local path for Dompdf)
        $logoPath = public_path('frontend/images/pngs/logo-color.png');
    @endphp

    <div class="invoice-container">

        {{-- Header --}}
        <div class="clearfix mb-15">
            <div class="left">
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
            <div class="right">
                <h1>Receipt</h1>
                <div class="mb-0"><strong>Receipt#</strong> {{ $rcNo }}</div>
                <div class="mb-0"><strong>Invoice#</strong> {{ $invNo }}</div>
                <div class="mb-0"><strong>Payment Plan</strong> {{ $plan }}</div>
            </div>
        </div>

        {{-- Meta row: Paid | Due/Paid Dates | Balance Due (right) --}}
        <div class="meta-row mb-15">
            <div class="col">
                <strong>Paid Amount:</strong><br>{{ $money($paidAmount) }}
            </div>
            <div class="col text-center">
                <strong>Due Date:</strong><br>{{ $dueDate }}
            </div>
            <div class="col text-center">
                <strong>Paid Date:</strong><br>{{ $paidDate }}
            </div>
            <div class="col text-right">
                <strong>Balance Due:</strong><br>{{ $money($balanceDue) }}
            </div>
        </div>

        {{-- Student Details --}}
        <div class="section">
            <h4>Student Details</h4>
            <div class="mb-0"><strong>Name:</strong> {{ optional($installment->user)->name ?? 'N/A' }}</div>
            <div class="mb-0"><strong>Email:</strong> {{ optional($installment->user)->email ?? 'N/A' }}</div>
            <div class="mb-0"><strong>Phone:</strong> {{ optional($installment->user)->mobile_number ?? 'N/A' }}</div>
            <div class="mb-0"><strong>Address:</strong> {{ optional($installment->user)->address ?? 'N/A' }}</div>
            @php
                $city = optional($installment->user)->city;
                $country = optional($installment->user)->country;
            @endphp
            @if($city || $country)
                <div class="mb-0"><strong>City/Country:</strong>
                    {{ trim(($city ? $city : '') . ($country ? ', ' . $country : ''), ', ') }}</div>
            @endif
        </div>

        {{-- Course Details --}}
        <div class="section">
            <h4>Course Details</h4>
            <div class="mb-0"><strong>Course Title:</strong>
                {{ optional(optional($installment->payment)->course)->title ?? 'N/A' }}</div>
            <div class="mb-0"><strong>Package Name:</strong>
                {{ optional(optional($installment->payment)->courseFee)->package_name ?? 'N/A' }}</div>
        </div>

        {{-- Footer --}}
        @include('partials.invoice-document-footer-pdf')

    </div>
</body>

</html>