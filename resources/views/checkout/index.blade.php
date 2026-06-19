@extends('layouts.app')

@section('content')
    <div class="bg-gray-50 pt-28 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-900">Complete Your Order</h1>
                <p class="mt-2 text-sm text-gray-600">Review your details and confirm your purchase</p>
            </div>

            <div class="bg-white rounded-xl overflow-hidden">
                <div class="md:flex">
                    <!-- Billing & User Details -->
                    <div class="md:w-2/3 p-8 border-r border-gray-100">
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="text-xl font-semibold text-gray-900">Your Information</h2>
                            <a href="{{ route('user.profile') }}" target="_blank"
                               class="text-[#000435] hover:text-[#000435] text-sm font-medium flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Profile
                            </a>
                        </div>

                        <!-- User Information -->
                        <div class="mb-10">
                            <h3 class="text-md font-medium text-gray-700 mb-4 pb-2 border-b border-gray-100">Personal Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Full Name</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $user->email }}</p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Gender</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $user->gender ?? 'Not provided' }}</p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Date of Birth</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $user->date_of_birth ?? 'Not provided' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Billing Address -->
                        <div class="mb-6">
                            <h3 class="text-md font-medium text-gray-700 mb-4 pb-2 border-b border-gray-100">Billing Address</h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Street Address</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $user->address ?? 'Not provided' }}</p>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">City</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $user->city ?? 'Not provided' }}</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Postal Code</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $user->post_code ?? 'Not provided' }}</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Nationality</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $user->nationality ?? 'Not provided' }}</p>
                                    </div>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Country</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $user->country ?? 'Not provided' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="md:w-1/3 p-8 bg-gray-50">
                        <form id="checkout-form" method="POST" action="{{ route('checkout.store') }}">
                            @csrf
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Summary</h2>

                            <div class="space-y-4 mb-6">
                                @foreach($carts as $item)
                                    @if(!$item->courseFee)
                                        @continue
                                    @endif
                                    @php
                                        $linePrice = format_package_price($item->courseFee->price, $item->courseFee->currency ?? 'AED', $item->quantity);
                                    @endphp
                                    <div class="flex justify-between items-start pb-4 border-b border-gray-200">
                                        <div>
                                            <input type="hidden" name="cart_id[]" value="{{ $item->id }}">
                                            <h4 class="text-sm font-medium text-gray-900">{{ $item->courseFee->package_name }}</h4>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900 whitespace-nowrap">{{ $linePrice['display'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="py-4 border-t border-gray-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-base font-bold text-gray-900">Total</span>
                                    <div class="text-right">
                                        @foreach($displayTotals as $currency => $amount)
                                            <span class="text-lg font-bold text-gray-900">{{ $currency }} {{ number_format($amount, 2) }}</span>
                                            @if(!$loop->last)
                                                <br>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <button type="submit"
                                class="w-full mt-6 bg-[#bc1904] hover:bg-[#a21503] transition-colors duration-200 border border-transparent rounded-lg py-3 px-4 flex items-center justify-center text-base font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#bc1904] shadow-sm">
                                Complete Order
                            </button>

                            <p class="mt-4 text-center text-sm text-gray-500">
                                or
                                <a href="{{ route('cart.index') }}"
                                    class="text-[#000435] font-medium hover:text-[#000435]">Continue Shopping</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
