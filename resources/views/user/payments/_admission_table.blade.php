<div class="ibox lms-admission-ibox">
    <div class="ibox-title">
        <h5>Admission information</h5>
    </div>
    <div class="ibox-content">
        <div class="lms-admission-wrap">
            <table class="table table-striped table-bordered table-hover lms-admission-table" id="lms-admission-table">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Invoice Issued Date</th>
                        <th>Course Name</th>
                        <th>Package Name</th>
                        <th>Invoice Amount</th>
                        <th>Due Date</th>
                        <th>Due Amount</th>
                        <th>Payment Status</th>
                        <th>Receipt No</th>
                        <th>Paid Date</th>
                        <th>Paid Amount</th>
                        <th class="col-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $shownPaymentIds = [];
                    @endphp
                    @foreach ($data['installments'] ?? [] as $installment)
                        @if($installment->payment && $installment->payment->status === 'Active')
                            @php
                                $payment = $installment->payment;
                                $invoiceAmount = format_payment_amount($payment);
                                $issuedAt = $payment->created_at ?? $installment->created_at;
                            @endphp
                            <tr>
                                <td data-label="Invoice No">
                                    INV-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
                                    <div class="text-muted" style="font-size:12px;margin-top:2px;">Payment Plan {{ $installment->installment_number }}/{{ $payment->total_installment }}</div>
                                </td>
                                <td data-label="Invoice Issued Date" data-order="{{ $issuedAt ? \Carbon\Carbon::parse($issuedAt)->timestamp : 0 }}">
                                    {{ $issuedAt ? \Carbon\Carbon::parse($issuedAt)->format('d-M-Y') : 'N/A' }}
                                </td>
                                <td data-label="Course Name">@include('partials.course_link', ['course' => $payment->course ?? null, 'fallback' => 'N/A'])</td>
                                <td data-label="Package Name">{{ $payment->courseFee->package_name ?? 'N/A' }}</td>
                                <td data-label="Invoice Amount">{{ $invoiceAmount['display'] }}</td>
                                <td data-label="Due Date">{{ $installment->due_date ? \Carbon\Carbon::parse($installment->due_date)->format('d-M-Y') : 'N/A' }}</td>
                                <td data-label="Due Amount">{{ format_payment_aed_amount($payment, (float) ($installment->paid_amount + $installment->remaining_amount)) }}</td>
                                <td data-label="Payment Status">
                                    <span class="badge badge-{{ $installment->status == 'paid' ? 'success' : 'warning' }}">
                                        {{ ucfirst($installment->status) }}
                                    </span>
                                </td>
                                <td data-label="Receipt No">
                                    @if ($installment->status === 'paid')
                                        RC-{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td data-label="Paid Date">{{ $installment->paid_date ?? 'N/A' }}</td>
                                <td data-label="Paid Amount">{{ format_payment_aed_amount($payment, (float) ($installment->paid_amount ?? 0)) }}</td>
                                <td class="col-action" data-label="Action">
                                    <div class="lms-admission-actions">
                                    @if ($installment->status == 'pending')
                                        <button type="button" class="btn btn-primary btn-sm payNowBtn"
                                            data-installment-id="{{ $installment->id }}">
                                            <i class="fa fa-credit-card"></i> Pay Now
                                        </button>
                                    @endif

                                    @if ($installment->status == 'paid')
                                        <a href="{{ route('user.installments.receipt', $installment->id) }}"
                                            class="btn btn-primary btn-sm">
                                            <i class="fa fa-file-text-o"></i> Receipt
                                        </a>
                                    @endif

                                    @php
                                        $paymentId = $installment->payment_id ?? null;
                                    @endphp

                                    @if ($paymentId && !in_array($paymentId, $shownPaymentIds))
                                        <a href="{{ route('user.installments.invoice', $paymentId) }}"
                                            class="btn btn-success btn-sm">
                                            <i class="fa fa-file-pdf-o"></i> Invoice
                                        </a>
                                        @php
                                            $shownPaymentIds[] = $paymentId;
                                        @endphp
                                    @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
