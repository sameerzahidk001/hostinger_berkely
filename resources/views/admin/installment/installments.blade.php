@extends('admin.layout.app')

@section('title', 'Installments')

@push('style')
    <link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox">
                <div class="ibox-title">
                    <h5>Installments</h5>
                </div>
            </div>

            <div class="ibox">
                <div class="ibox-title">
                    <h5>Installments List</h5>
                </div>
                <div class="ibox-content">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Installment Name</th>
                                <th>Due Date</th>
                                <th>Remaining Amount</th>
                                <th>Paid Amount</th>
                                <th>Installment No.</th>
                                <th>User</th>
                                <th>Course Name</th>
                                <th>Package Name</th> <!-- ✅ Added Package Name -->
                                <th>Status</th>
                                <th>Pay</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($installments as $installment)
                            <tr>
                                <td>{{ $installment->name }}</td>
                                <td>{{ $installment->due_date ?? 'N/A' }}</td>  <!-- ✅ Show Due Date -->
                                <td>${{ $installment->remaining_amount }}</td>
                <td>${{ $installment->paid_amount }}</td>
                <td>{{ $installment->installment_number }}</td>
                <td>{{ $installment->user->name ?? 'N/A' }}</td>
                <td>{{ $installment->payment->course->title ?? 'N/A' }}</td>
                <td>{{ $installment->payment->courseFee->package_name ?? 'N/A' }}</td> <!-- ✅ Added Package Name -->

                                <td>
                                    <span class="badge badge-{{ $installment->status == 'paid' ? 'success' : 'warning' }}">
                                        {{ ucfirst($installment->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($installment->status == 'pending')
                                        <button class="btn btn-success btn-sm pay-installment"
                                                data-id="{{ $installment->id }}"
                                                data-amount="{{ $installment->remaining_amount }}">
                                            <i class="fa fa-credit-card"></i> Pay Now
                                        </button>
                                    @else
                                        <span class="text-success">Paid</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Method Modal -->
<div class="modal fade" id="paymentMethodModal" tabindex="-1" role="dialog" aria-labelledby="paymentMethodModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Payment Method</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Choose a payment method to continue:</p>
                <button class="btn btn-primary btn-block pay-stripe">
                    <i class="fa fa-credit-card"></i> Pay with Stripe
                </button>
                <button class="btn btn-info btn-block pay-paypal">
                    <i class="fa fa-paypal"></i> Pay with PayPal
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
    <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>

    <script>
   $(document).ready(function () {
    let selectedInstallmentId = null;
    let selectedAmount = null;

    $('.pay-installment').click(function () {
        selectedInstallmentId = $(this).data('id');
        selectedAmount = $(this).data('amount');
        $('#paymentMethodModal').modal('show');
    });

    $('.pay-stripe').click(function () {
        processStripePayment(selectedInstallmentId, selectedAmount);
    });

    $('.pay-paypal').click(function () {
        processPayPalPayment(selectedInstallmentId, selectedAmount);
    });

    function processStripePayment(installmentId, amount) {
        let stripeToken = 'your_stripe_token'; // Replace with actual Stripe token generation

        fetch(`/admin/installments/pay/${installmentId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                amount: amount,
                method: 'stripe',
                token: stripeToken
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload(); // Refresh page after payment
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function processPayPalPayment(installmentId, amount) {
        fetch(`/admin/installments/pay/${installmentId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                amount: amount,
                method: 'paypal'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.approval_url; // Redirect to PayPal
            } else {
                alert(data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
});
    </script>
@endpush
