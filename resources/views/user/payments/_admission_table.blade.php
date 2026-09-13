<div class="ibox">
    <div class="ibox-title">
        <h5>Admission information</h5>
    </div>
    <div class="ibox-content">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover dataTables-example">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Receipt No</th>
                        <th>Invoice Issued Date</th>
                        <th>Invoice Amount</th>
                        <th>Payment Plan</th>
                        <th>Due Date</th>
                        <th>Due Amount</th>
                        <th>Paid Amount</th>
                        <th>Paid Date</th>
                        <th>Course Name</th>
                        <th>Package Name</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $shownPaymentIds = [];
                    @endphp
                    @foreach ($data['installments'] ?? [] as $installment)
                        @if($installment->payment && $installment->payment->status === 'Active')
                            @php $invoiceAmount = format_payment_amount($installment->payment); @endphp
                            <tr>
                                <td>INV-{{ str_pad($installment->payment_id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    @if ($installment->status === 'paid')
                                        RC-{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td data-order="{{ \Carbon\Carbon::parse($installment->created_at)->timestamp }}">{{ \Carbon\Carbon::parse($installment->created_at)->format('d-M-Y') ?? 'N/A' }}
                                </td>
                                <td>
                                    {{ $invoiceAmount['display'] }}
                                </td>
                                <td>{{ $installment->installment_number }}/{{ $installment->payment->total_installment }}
                                </td>
                                <td>{{ $installment->due_date ? \Carbon\Carbon::parse($installment->due_date)->format('d-M-Y') : 'N/A' }}</td>
                                <td>{{ format_payment_aed_amount($installment->payment, (float) ($installment->paid_amount + $installment->remaining_amount)) }}</td>
                                <td>{{ format_payment_aed_amount($installment->payment, (float) ($installment->paid_amount ?? 0)) }}</td>
                                <td>{{ $installment->paid_date ?? 'N/A' }}</td>
                                <td>{{ $installment->payment->course->title ?? 'N/A' }}</td>
                                <td>{{ $installment->payment->courseFee->package_name ?? 'N/A' }}</td>
                                <td>
                                    <span
                                        class="badge badge-{{ $installment->status == 'paid' ? 'success' : 'warning' }}">
                                        {{ ucfirst($installment->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($installment->status == 'pending')
                                        <button type="button" class="btn btn-primary btn-sm payNowBtn"
                                            style="margin-right: 5px; margin-bottom: 5px;"
                                            data-installment-id="{{ $installment->id }}">
                                            <i class="fa fa-credit-card"></i> Pay Now
                                        </button>
                                    @endif

                                    @if ($installment->status == 'paid')
                                        <a href="{{ route('user.installments.receipt', $installment->id) }}"
                                            class="btn btn-primary btn-sm"
                                            style="margin-right: 5px; margin-bottom: 5px;">
                                            <i class="fa fa-file-text-o"></i> Receipt
                                        </a>
                                    @endif

                                    @php
                                        $paymentId = $installment->payment_id ?? null;
                                        $courseId = $installment->payment->course->id ?? null;
                                        $userId = $installment->user->id ?? null;
                                    @endphp

                                    @if ($paymentId && !in_array($paymentId, $shownPaymentIds))
                                        <a href="{{ route('user.installments.invoice', $paymentId) }}"
                                            class="btn btn-success btn-sm"
                                            style="display:inline-block; margin-right:5px; margin-bottom: 5px;">
                                            <i class="fa fa-file-pdf-o"></i> Invoice
                                        </a>
                                        @php
                                            $shownPaymentIds[] = $paymentId;
                                        @endphp
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
