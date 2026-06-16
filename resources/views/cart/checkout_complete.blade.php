@extends('layouts.app')

@section('content')
<div class="container mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">Payment Successful!</h1>

    <p class="mb-4">Thank you for your purchase.</p>

    @foreach($purchasedPackages as $item)
        <div class="p-4 mb-4 border rounded bg-white">
            <h3 class="text-lg font-semibold">{{ $item['name'] }}</h3>
            <p>Paid: {{ $item['currency'] }}{{ $item['price'] }}</p>
            <a href="{{ route('installment.quote', $item['id']) }}" class="btn btn-warning mt-2">Request Installment Plan</a>
        </div>
    @endforeach

    <a href="{{ url('/') }}" class="btn mt-4">Back to Home</a>
</div>
@endsection
