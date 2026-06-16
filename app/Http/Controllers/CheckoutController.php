<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Payment;
use App\Models\CartItem;
use App\Models\Installment;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('cart.index')->with('warning', 'Your cart is empty!');
        }

        $user = Auth::user();
        $carts = CartItem::forUser(Auth::id());

        if ($carts->isEmpty()) {
            return redirect()->route('cart.index')->with('warning', 'Your cart is empty!');
        }

        $total = $carts->sum(function ($item) {
            $fee = $item->courseFee;
            if (!$fee) {
                return 0;
            }

            return format_package_price($fee->price, $fee->currency ?? 'AED', $item->quantity)['settling_aed'];
        });

        return view('checkout.index', compact('user', 'carts', 'total'));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'cart_id'   => 'required|array',
            'cart_id.*' => 'required|exists:cart_items,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        foreach ($validated['cart_id'] as $value) {
            $cart = CartItem::with('courseFee')
                ->where('id', $value)
                ->where('user_id', Auth::id())
                ->first();

            if (!$cart || !$cart->courseFee) {
                continue;
            }
            $fee = $cart->courseFee;
            $displayCurrency = package_currency_code($fee->currency ?? 'AED');
            $settlingAmount = package_settling_amount($fee->price, $displayCurrency) * $cart->quantity;
            $lastPayment = Payment::latest('id')->first();

            $payment = new Payment();
            $payment->user_id = $cart->user_id;
            $payment->course_id = $fee->courses_id;
            $payment->package_id = $cart->course_fee_package_id;
            $payment->price = $settlingAmount;
            $payment->currency = $displayCurrency;
            $payment->payment_method = 'checkout';
            $payment->total_installment = 1;
            $payment->terms_conditions = $lastPayment->terms_conditions ?? null;
            $payment->status = 'Active';
            $payment->source = 'checkout';
            $payment->save();

            $installment = new Installment();
            $installment->name = 'Installment 1';
            $installment->duration_months = '1';
            $installment->remaining_amount = $payment->price;
            $installment->paid_amount = 0;
            $installment->installment_number = 1;
            $installment->due_date = Carbon::now()->addDays(10);
            $installment->payment_id = $payment->id;
            $installment->user_id = $payment->user_id;
            $installment->status = 'pending';
            $installment->save();

            app(InvoiceService::class)->sendInvoiceEmail($payment);

            $cart->delete();
        }

        return redirect()->route('user.home')->with('success', 'Checkout complete. Your invoice has been emailed and is available in your dashboard.');
    }
}