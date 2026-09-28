@php
    $accountUrl = auth()->check() ? route('user.home') : route('login');
    $cartCount = cart_item_count();
@endphp
<a href="{{ $accountUrl }}" class="header-quick-icon" title="My Account" aria-label="My Account">
    <span class="header-quick-icon-box">
        <i class="fa fa-user" aria-hidden="true"></i>
    </span>
    <span class="header-quick-icon-label">My Account</span>
</a>
<a href="{{ route('cart.index') }}" class="header-quick-icon" title="Cart" aria-label="Cart">
    <span class="header-quick-icon-box">
        <i class="fa fa-shopping-cart" aria-hidden="true"></i>
        @if($cartCount > 0)
            <span class="header-quick-icon-badge">{{ $cartCount }}</span>
        @endif
    </span>
    <span class="header-quick-icon-label">Cart</span>
</a>
