@extends('user.layout.app')
@section('title', 'Pay with Stripe')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Pay with Stripe</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Home</a></li>
            <li><a href="{{ route('user.payments') }}">Payments</a></li>
            <li class="active"><strong>USD checkout</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="ibox">
        <div class="ibox-title">
            <h5>{{ $installment->payment?->course?->title ?: 'Course fee' }}</h5>
        </div>
        <div class="ibox-content">
            <p class="text-muted" style="margin-bottom:16px;">
                Complete the card payment below. You stay in the student portal.
            </p>
            <div id="stripe-embedded-checkout"></div>
            <p style="margin-top:16px;">
                <a href="{{ route('user.payments') }}" class="btn btn-white">Cancel</a>
            </p>
        </div>
    </div>
</div>
@endsection
@push('script')
<script src="https://js.stripe.com/v3/"></script>
<script>
(function () {
    var stripe = Stripe(@json($publishableKey));
    stripe.initEmbeddedCheckout({ clientSecret: @json($clientSecret) }).then(function (checkout) {
        checkout.mount('#stripe-embedded-checkout');
    }).catch(function (err) {
        alert((err && err.message) ? err.message : 'Stripe could not be loaded.');
    });
})();
</script>
@endpush
