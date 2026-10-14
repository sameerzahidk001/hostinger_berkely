<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Payment;
use App\Models\CartItem;
use App\Models\Installment;
use App\Services\InvoiceService;
use App\Services\PageFeeStructureService;
use App\Services\PaymentCrmDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('cart.index')->with('warning', 'Your cart is empty!');
        }

        app(PageFeeStructureService::class)->ensureCommerceColumns();

        $user = Auth::user();
        $carts = CartItem::forUser(Auth::id());

        if ($carts->isEmpty()) {
            return redirect()->route('cart.index')->with('warning', 'Your cart is empty!');
        }

        $displayTotals = [];
        foreach ($carts as $item) {
            $fee = $item->feePackage();
            if (!$fee) {
                continue;
            }

            $linePrice = format_package_price($fee->price, $fee->currency ?? 'AED', $item->quantity);
            $currency = $linePrice['currency'];
            $displayTotals[$currency] = ($displayTotals[$currency] ?? 0) + ((float) $fee->price * $item->quantity);
        }

        return view('checkout.index', compact('user', 'carts', 'displayTotals'));
    }

    public function store(Request $request)
    {
        app(PageFeeStructureService::class)->ensureCommerceColumns();

        $validator = Validator::make($request->all(), [
            'cart_id'   => 'required|array',
            'cart_id.*' => 'required|exists:cart_items,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        foreach ($validated['cart_id'] as $value) {
            $cart = CartItem::with([
                    'courseFee.course',
                    'pageFeePackage.category.service.page',
                ])
                ->where('id', $value)
                ->where('user_id', Auth::id())
                ->first();

            if (!$cart) {
                continue;
            }

            if ($cart->isPageFee() && $cart->pageFeePackage) {
                $this->checkoutPageFee($cart, $request);
            } elseif ($cart->courseFee) {
                $this->checkoutCourseFee($cart, $request);
            }
        }

        return redirect()->route('user.home')->with('success', 'Checkout complete. Your invoice has been emailed and is available in your dashboard.');
    }

    private function checkoutCourseFee(CartItem $cart, Request $request): void
    {
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

        $this->createPendingInstallment($payment);
        app(InvoiceService::class)->sendInvoiceEmail($payment);
        PaymentCrmDispatcher::syncPayment($payment->fresh(['installments', 'user', 'course', 'courseFee']));

        $courseName = $fee->course?->title ?? 'Course #' . $fee->courses_id;
        $amountLabel = format_payment_amount($payment)['display'];
        record_user_activity(
            'Invoice Created',
            'Created invoice for ' . $courseName . ' (' . $amountLabel . ')',
            route('user.home'),
            'student',
            Auth::id(),
            null,
            $request
        );

        $cart->delete();
    }

    private function checkoutPageFee(CartItem $cart, Request $request): void
    {
        $fee = $cart->pageFeePackage;
        $category = $fee->category;
        $service = $category?->service;
        $page = $service?->page;

        $displayCurrency = package_currency_code($fee->currency ?? 'AED');
        $settlingAmount = package_settling_amount($fee->price, $displayCurrency) * $cart->quantity;
        $lastPayment = Payment::latest('id')->first();

        $payment = new Payment();
        $payment->user_id = $cart->user_id;
        $payment->course_id = null;
        $payment->package_id = null;
        if (Schema::hasColumn('payments', 'page_id')) {
            $payment->page_id = $page?->id;
            $payment->page_fee_service_id = $service?->id;
            $payment->page_fee_category_id = $category?->id;
            $payment->page_fee_package_id = $fee->id;
        }
        $payment->price = $settlingAmount;
        $payment->currency = $displayCurrency;
        $payment->payment_method = 'checkout';
        $payment->total_installment = 1;
        $payment->terms_conditions = $lastPayment->terms_conditions ?? null;
        $payment->status = 'Active';
        $payment->source = 'checkout';
        $payment->save();

        $this->createPendingInstallment($payment);
        app(InvoiceService::class)->sendInvoiceEmail($payment);
        PaymentCrmDispatcher::syncPayment($payment->fresh([
            'installments', 'user', 'page', 'pageFeeService', 'pageFeePackage',
        ]));

        $label = ($page?->page_name ?? 'Page') . ' / ' . ($fee->package_name ?? 'Package');
        $amountLabel = format_payment_amount($payment)['display'];
        record_user_activity(
            'Invoice Created',
            'Created invoice for ' . $label . ' (' . $amountLabel . ')',
            route('user.home'),
            'student',
            Auth::id(),
            null,
            $request
        );

        $cart->delete();
    }

    private function createPendingInstallment(Payment $payment): void
    {
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
    }
}
