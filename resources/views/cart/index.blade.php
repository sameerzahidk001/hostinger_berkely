@extends(auth()->check() ? 'user.layout.app' : 'layouts.app')

@section('content')
@php
    $isPanel = auth()->check();
@endphp
@if(auth()->check())
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Your Cart @if(cart_item_count() > 0)<span class="label label-primary">{{ cart_item_count() }}</span>@endif</h2>
    </div>
</div>
<div class="wrapper wrapper-content">
@else
    <div class="bg-gray-100 py-10 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto bg-white shadow-xl rounded-xl p-6 md:p-8">
@endif
            <h1 class="text-2xl font-bold text-gray-800 mb-6 @if(auth()->check()) hidden @endif">Your Cart</h1>

            @if($carts->isEmpty())
                @if($isPanel)
                    <div class="ibox">
                        <div class="ibox-content text-center" style="padding: 48px 24px;">
                            <i class="fa fa-shopping-cart fa-3x text-muted" style="margin-bottom: 16px;"></i>
                            <h3 style="margin-bottom: 8px;">Your cart is empty</h3>
                            <p class="text-muted" style="margin-bottom: 16px;">Browse our courses and find something special for you</p>
                            <a href="{{ url('/courses') }}" class="btn btn-primary">Explore Courses</a>
                        </div>
                    </div>
                @else
                <div class="text-center py-16 px-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h2 class="mt-4 text-lg font-medium text-gray-900">Your cart is empty</h2>
                    <p class="mt-2 text-gray-600 mb-6">Browse our courses and find something special for you</p>
                    <a href="{{ url('/') }}"
                        class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-white bg-[#bc1904] hover:bg-[#a21503] font-medium transition-colors duration-200">
                        Start Shopping
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 -mr-1 h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </a>
                </div>
                @endif
            @else
                @if($isPanel)
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>Cart Items</h5>
                        </div>
                        <div class="ibox-content">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-gray-800">
                @endif
                        <thead>
                            <tr class="{{ $isPanel ? '' : 'border-b bg-gray-50 text-left text-sm font-semibold text-gray-600' }}">
                                <th class="{{ $isPanel ? '' : 'p-4' }}">Package</th>
                                <th class="{{ $isPanel ? '' : 'p-4' }}">Features</th>
                                <th class="{{ $isPanel ? 'text-center' : 'p-4 text-center' }}">Price</th>
                                <th class="{{ $isPanel ? 'text-center' : 'p-4 text-center' }}">Quantity</th>
                                <th class="{{ $isPanel ? 'text-center' : 'p-4 text-center' }}">Subtotal</th>
                                <th class="{{ $isPanel ? 'text-center' : 'p-4 text-center' }}">Action</th>
                            </tr>
                        </thead>
                        <tbody class="{{ $isPanel ? '' : 'divide-y divide-gray-200' }}">
                            @php $total = 0; @endphp
                            @foreach($carts as $item)
                                @php
                                    $package = $item->courseFee;
                                @endphp
                                @if(!$package)
                                    @continue
                                @endif
                                @php
                                    $unitPriceAed = format_package_price($package->price, $package->currency ?? 'AED');
                                    $linePrice = format_package_price($package->price, $package->currency ?? 'AED', $item->quantity);
                                    $subtotal = $linePrice['settling_aed'];
                                    $total += $subtotal;
                                @endphp
                                <tr class="{{ $isPanel ? '' : 'hover:bg-gray-50 transition' }}">
                                    <td>
                                        @if($isPanel)
                                            <div>
                                                <strong>{{ $package->package_name }}</strong>
                                                @if(!empty($package->short_description))
                                                    <div class="text-muted" style="margin-top: 4px;">{{ $package->short_description }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-800">{{ $package->package_name }}
                                                    </h3>
                                                    <p class="mt-1 text-gray-600">{{ $package->short_description ?? '' }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="{{ $isPanel ? '' : 'p-4 font-medium' }}">
                                        @if($package->package_feature)
                                            <ul class="{{ $isPanel ? '' : 'space-y-3 text-left' }}">
                                                @foreach($package->package_feature as $data)
                                                    @if($isPanel)
                                                        <li>{{ $data }}</li>
                                                    @else
                                                        <li class="flex items-start space-x-2">
                                                            <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-5 h-5 mt-0.5"
                                                                alt="">
                                                            <span class="text-gray-700 leading-snug">{{ $data }}</span>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                    <td class="{{ $isPanel ? 'text-center' : 'p-4 text-center font-medium' }}">
                                        {{ $unitPriceAed['display'] }}
                                        @if($unitPriceAed['show_settling_note'])
                                            <div class="text-muted" style="font-size:12px;margin-top:4px;">{{ $unitPriceAed['settling_note'] }}</div>
                                        @endif
                                    </td>
                                    <td class="{{ $isPanel ? 'text-center' : 'p-4 text-center' }}">
                                            <form action="{{ route('cart.update', $item->id) }}" method="POST"
                                                class="{{ $isPanel ? 'form-inline' : 'flex items-center gap-2' }}" style="{{ $isPanel ? 'display:flex;justify-content:center;align-items:center;gap:8px;' : '' }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1"
                                                    class="{{ $isPanel ? 'form-control input-sm' : 'w-16 text-center border rounded-md p-1 focus:ring-[#bc1904] focus:border-[#bc1904]' }}" style="{{ $isPanel ? 'width:80px;' : '' }}">
                                                <button type="submit"
                                                    class="{{ $isPanel ? 'btn btn-primary btn-sm' : 'bg-[#bc1904] text-white px-3 py-1 rounded-md hover:bg-[#a21503] transition' }}">
                                                    Update
                                                </button>
                                            </form>
                                    </td>
                                    <td class="{{ $isPanel ? 'text-center' : 'p-4 text-center font-medium' }}">
                                        {{ $linePrice['display'] }}
                                        @if($linePrice['show_settling_note'])
                                            <div class="text-muted" style="font-size:12px;margin-top:4px;">{{ $linePrice['settling_note'] }}</div>
                                        @endif
                                    </td>
                                    <td class="{{ $isPanel ? 'text-center' : 'p-4 text-center' }}">
                                        <form action="{{ route('cart.destroy', $item->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="{{ $isPanel ? 'btn btn-danger btn-sm' : 'text-red-500 hover:text-red-700 font-medium transition' }}">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @if($isPanel)
                            </div>
                        </div>
                    </div>
                @else
                </div>
                @endif

                <!-- Cart Summary -->
                @if($isPanel)
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-md-6">
                            <div class="well well-sm" style="margin-bottom: 0;">
                                <strong>Total Items:</strong> {{ $carts->sum('quantity') }}
                                <span style="margin-left: 12px;"><strong>Grand Total:</strong> AED {{ number_format($total, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="{{ url('/') }}" class="btn btn-default">Continue Shopping</a>
                            @auth
                                <a href="{{ route('checkout.index') }}" class="btn btn-primary">Proceed to Checkout</a>
                            @else
                                <button type="button" data-toggle="modal" data-target="#checkoutAuthModal" class="btn btn-primary">
                                    Proceed to Checkout
                                </button>
                            @endauth
                        </div>
                    </div>
                @else
                    <div class="mt-8 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="text-gray-600">
                            <p class="text-lg">Total Items: {{ $carts->sum('quantity') }}</p>
                            <p class="text-xl font-bold text-gray-800 mt-2">Grand Total: AED {{ number_format($total, 2) }}</p>
                        </div>
                        <div class="flex gap-4">
                            <a href="{{ url('/') }}"
                                class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                Continue Shopping
                            </a>
                            @auth
                            <a href="{{ route('checkout.index') }}"
                                class="px-6 py-3 rounded-lg bg-[#bc1904] text-white font-semibold hover:bg-[#a21503] transition">
                                Proceed to Checkout
                            </a>
                            @else
                            <button type="button" data-toggle="modal" data-target="#checkoutAuthModal"
                                class="px-6 py-3 rounded-lg bg-[#bc1904] text-white font-semibold hover:bg-[#a21503] transition">
                                Proceed to Checkout
                            </button>
                            @endauth
                        </div>
                    </div>
                @endif
            @endif
@if(auth()->check())
</div>
</div>
@else
        </div>
    </div>

    <div class="modal fade" id="checkoutAuthModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content p-6">
                <h3 class="text-xl font-bold mb-4">Create a student account to pay and complete the package</h3>
                <ul class="mb-4 list-disc pl-5">
                    @foreach($carts as $item)
                        <li>{{ $item->courseFee->package_name ?? 'Package' }}</li>
                    @endforeach
                </ul>
                <div class="flex gap-3">
                    <a href="{{ route('register') }}?redirect_to_cart=1" class="btn btn-primary">Register as a New student</a>
                    <a href="{{ route('login') }}?redirect_to_cart=1" class="btn btn-default">Sign in if you are an existing student</a>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection