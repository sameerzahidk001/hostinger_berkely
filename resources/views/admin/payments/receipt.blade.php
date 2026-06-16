@extends('admin.layout.app')

@section('title', 'Installment Receipt')

@section('content')
    <style>
        .receipt-box {
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

        .receipt-section {
            margin-bottom: 30px;
        }

        .receipt-section h4 {
            font-size: 18px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 12px;
            padding-bottom: 6px;
            color: #555;
        }

        .receipt-section p {
            margin-bottom: 8px;
        }

        .print-btn {
            text-align: center;
            margin-top: 30px;
        }

        .receipt-footer {
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 40px;
            font-size: 12px;
            text-align: center;
            color: #777;
        }

        @media print {
            @page {
                margin: 0;
            }

            html,
            body {
                height: 100%;
                margin: 0;
                padding: 0;
                overflow: hidden;
            }

            body * {
                visibility: hidden;
            }

            .receipt-box,
            .receipt-box * {
                visibility: visible;
            }

            .receipt-box {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="print-btn text-center" style="margin-top: 20px;">
            <button class="btn btn-primary" onclick="window.print();">
                <i class="fa fa-download"></i> Print Receipt
            </button>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div id="receipt-content" class="receipt-box">
                    <div style="display: table; width: 100%; margin-bottom: 30px;">
                        <div style="display: table-cell; width: 50%; vertical-align: top;">
                            <img src="{{ asset('frontend/images/pngs/logo-color.png') }}" alt="Logo"
                                style="width: 280px; margin-bottom: 10px;">
                            <p style="margin-bottom: 0;">Berkeley School of Business, Arts & Sciences</p>
                            <p style="margin-bottom: 0;">Berkeley Square, Mayfair, London, W1J, UK</p>
                            <p style="margin-bottom: 0;">Mob: +44 7306 279111</p>
                            <p>Email: training@berkeleyme.com</p>
                        </div>
                        <div style="display: table-cell; width: 50%; vertical-align: bottom; text-align: right;">
                            <h1 style="margin-top: 0;">Receipt</h1>
                            <p style="margin: 0;"><strong>Receipt#</strong> RC-{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }}</p>
                            <p style="margin: 0;"><strong>Invoice#</strong> INV-{{ str_pad($installment->payment->id, 6, '0', STR_PAD_LEFT) }}</p>
                            <p style="margin: 0;"><strong>Payment Plan</strong> {{ $installment->installment_number }}/{{ $installment->payment->total_installment }}</p>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom: 30px;">
                        <div class="col-xs-3 text-left">
                            <p><strong>Paid Amount:</strong><br>AED {{ $installment->paid_amount }}</p>
                        </div>
                        <div class="col-xs-3 text-center">
                            <p><strong>Due Date:</strong><br>{{ $installment->due_date ?? 'N/A' }}</p>
                        </div>
                        <div class="col-xs-3 text-center">
                            <p><strong>Paid Date:</strong><br>{{ $installment->paid_date ?? 'N/A' }}</p>
                        </div>
                        <div class="col-xs-3 text-right">
                            <p><strong>Balance Due:</strong><br>AED {{ $installment->remaining_amount }}</p>
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

                    <div class="receipt-footer">
                        <p style="margin-bottom: 0px;">
                            <strong>Liaison Office in Middle East:</strong> Berkeley Middle East Holding, Floor #25, Sheikh Rashid Tower,
                            Dubai World Trade Centre, Dubai, UAE.
                        </p>
                        <p style="margin-bottom: 0px;">
                            <strong>T:</strong> +971 (0) 58 555 5657 / +971 (0) 58 570 0006 &nbsp; | &nbsp;
                            <strong>E:</strong> Finance@berkeleyme.com &nbsp; | &nbsp;
                            <strong>W:</strong> www.eduberkeley.com
                        </p>
                        <p style="margin-bottom: 0px;">
                            <strong>Presence:</strong> USA &nbsp; | &nbsp; Canada &nbsp; | &nbsp; UK &nbsp; | &nbsp; UAE &nbsp; | &nbsp;
                            KSA &nbsp; | &nbsp; China &nbsp; | &nbsp; Africa
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

@endpush