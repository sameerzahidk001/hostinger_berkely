<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseFeePackages;
use App\Models\CourseFee;
use App\Models\Installment;
use App\Models\CartItem;
use App\Models\InstallmentRequest;
use App\Models\Country;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Akaunting\Money\Money;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function index()
    {
        $carts = collect();

        if (auth()->check()) {
            $carts = CartItem::forUser(Auth::id());
        }

        return view('cart.index', compact('carts'));
    }

    public function create(Request $request)
    {
        if (!Auth::check()) {
            session(['intended_package_id' => $request->input('package_id')]);

            return redirect()->route('login')
                ->with('warning', 'Please login to continue.');
        }

        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:course_fees,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $cartItem = CartItem::firstOrCreate([
            'user_id' => Auth::id(),
            'course_fee_package_id' => $validated['package_id'],
        ], [
            'quantity' => 0
        ]);

        $cartItem->increment('quantity');

        return redirect()->route('cart.index')->with('success', 'Item added to cart!');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $cartItem = CartItem::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $cartItem->update([
            'quantity' => $request->quantity
        ]);

        return redirect()->back()->with('success', 'Cart updated successfully!');
    }


    public function destroy ($id)
    {
        $cart = CartItem::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        $cart->delete();

        return redirect()->back()->with('success', 'Item removed.');
    }    

    // Convert guest cart to user cart after login
    public function migrateGuestCartToUser()
    {
        if (auth()->check()) {
            $userId = Auth::id();
            $guestCart = session()->get('guest_cart', []);

            // Migrate items from guest cart to user cart
            foreach ($guestCart as $packageId => $item) {
                CartItem::create([
                    'user_id' => $userId,
                    'course_fee_package_id' => $packageId,
                    'quantity' => $item['quantity'],
                ]);
            }

            // Remove the guest cart from the session after migration
            session()->forget('guest_cart');
        }

        return redirect()->route('cart.index');
    }

    // Complete checkout and remove cart items
    public function complete(Request $request)
    {
        $userId = Auth::id();
        $purchasedPackages = CartItem::with('CourseFeePackages') // Assuming you have a relationship in CartItem
                                    ->where('user_id', $userId)
                                    ->get();

        // Empty the cart after purchase
        CartItem::where('user_id', $userId)->delete();

        return view('cart.checkout_complete', compact('purchasedPackages'));
    }

    // Handle installment request
    public function installmentRequest(Request $request)
    {
        $request->validate([
            'installments' => 'required|array',
            'package_ids' => 'required|array',
        ]);
        
        $userId = auth()->id();

        foreach ($request->package_ids as $index => $packageId) {
            $installmentValue = $request->installments[$index];

            $feepackage = CourseFee::find($packageId);
            $displayCurrency = package_currency_code($feepackage->currency ?? 'AED');
            $settlingAmount = package_settling_amount($feepackage->price, $displayCurrency);

            $payment = new Payment();
            $payment->user_id = $userId;
            $payment->course_id = $feepackage->courses_id;
            $payment->price = $settlingAmount;
            $payment->currency = $displayCurrency;
            $payment->package_id = $packageId;
            $payment->payment_method = 'checkout';
            $payment->total_installment = 1;
            $payment->status = $installmentValue == '1' ? 'Active' : 'Inactive';
            $payment->source = 'checkout';
            $payment->save();

            if ($installmentValue == '1') {

                Installment::create([
                    'name' => "Single Payment",
                    'duration_months' => 1,
                    'remaining_amount' => $payment->price,
                    'paid_amount' => 0,
                    'installment_number' => 1,
                    'due_date' => now()->addDay()->toDateString(),
                    'payment_id' => $payment->id,
                    'user_id' => $userId,
                    'status' => 'pending'
                ]);

                app(\App\Services\InvoiceService::class)->sendInvoiceEmail($payment);
            } else {

                InstallmentRequest::create([
                    'user_id' => $userId,
                    'package_id' => $packageId,
                    'payment_id' => $payment->id,
                    'installments_requested' => $installmentValue === 'custom' ? 0 : $installmentValue,
                    'request_type' => $installmentValue === 'custom' ? 'custom' : 'standard',
                ]);
            }

            // Delete the related cart item for this package and user
            CartItem::where('user_id', $userId)
                ->where('course_fee_package_id', $packageId)
                ->delete();
        }

        return redirect()->route('user.home')->with('success', 'Installment request submitted successfully.');
    }
}