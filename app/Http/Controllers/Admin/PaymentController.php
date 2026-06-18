<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\InstallmentRequest;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\Installment;
use App\Models\CourseFee;
use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use App\Models\Email;
use App\Mail\UserMail;
use App\Services\InvoiceService;

class PaymentController extends Controller
{
    public function index()
    {
        $installments = Installment::get();
        $payments = Payment::with('user', 'course', 'courseFee', 'installments', 'installment_request')->orderByDesc('created_at')->get();
        // dd($payments->toArray());

        return view('admin.payments.index', compact('payments', 'installments'));
    }

    public function receipt()
    {
        $installments = Installment::where('status', 'paid')->orderByDesc('created_at')->get();
        return view('admin.payments.receipt-list', compact('installments'));
    }

    public function create()
    {
        $users = User::with('roles')->whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })->orderByDesc('created_at')->get();
        $courses = Course::orderByDesc('created_at')->get();
        $packages = CourseFee::whereHas('course', function ($query) {
            $query->where('status', 1);
        })->get();

        // dd($packages->toArray());

        return view('admin.payments.create', compact('users', 'courses', 'packages'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student' => 'required|exists:users,id',
            'course' => 'required|exists:courses,id',
            'package' => 'required|exists:course_fees,id',
            'total_amount' => 'required|numeric|min:1',
            'due_date' => 'required|array|min:1',
            'due_date.*' => 'required|date',
            'amount' => 'required|array|min:1',
            'amount.*' => 'required|numeric|min:1',
        ]);

        // Custom validation for matching total
        $validator->after(function ($validator) use ($request) {
            $expectedTotal = floatval($request->total_amount);
            $sumOfAmounts = collect($request->amount)->sum(function ($val) {
                return floatval($val);
            });

            if (round($sumOfAmounts, 2) !== round($expectedTotal, 2)) {
                $validator->errors()->add('amount', 'The sum of all installment amounts must equal the total amount.');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // dd($request->toArray());

        try {
            $package = CourseFee::findOrFail($request->package);
            $displayCurrency = package_currency_code($package->currency);
            $totalSettling = package_settling_amount($request->total_amount, $displayCurrency);

            $installmentAmounts = collect($request->amount)->map(function ($val) use ($displayCurrency) {
                return package_settling_amount($val, $displayCurrency);
            });

            if (round($installmentAmounts->sum(), 2) !== round($totalSettling, 2)) {
                return redirect()->back()
                    ->withErrors(['amount' => 'Installment amounts must equal the total amount.'])
                    ->withInput();
            }

            $payment = new Payment();
            $payment->user_id = $request->student;
            $payment->course_id = $request->course;
            $payment->package_id = $request->package;
            $payment->price = $totalSettling;
            $payment->currency = $displayCurrency;
            $payment->payment_method = 'invoice';
            $payment->total_installment = count($request->amount);
            $payment->terms_conditions = $request->terms_conditions;
            $payment->status = 'Inactive';
            $payment->source = 'admin';
            $payment->save();

            foreach ($installmentAmounts as $i => $amount) {
                Installment::create([
                    'name' => 'Installment ' . ($i + 1),
                    'duration_months' => $i + 1,
                    'remaining_amount' => $amount,
                    'paid_amount' => 0,
                    'installment_number' => $i + 1,
                    'due_date' => $request->due_date[$i],
                    'payment_id' => $payment->id,
                    'user_id' => $request->student,
                    'status' => 'pending',
                ]);
            }

            return redirect()->route('admin.payments.index')->with('success', 'Invoice created in inactive mode. Click Send to email the student.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('fail', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $payment = Payment::with('installments', 'user', 'course', 'courseFee')->findOrFail($id);
        $users = User::with('roles')->whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })->get();
        $courses = Course::where('status', 1)->get();
        $packages = CourseFee::whereHas('course', function ($query) {
            $query->where('status', 1);
        })->get();

        return view('admin.payments.edit', compact('payment', 'users', 'courses', 'packages'));
    }

    public function show($id)
    {
        return redirect()->route('admin.payments.edit', $id);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'student' => 'required|exists:users,id',
            'course' => 'required|exists:courses,id',
            'package' => 'required|exists:course_fees,id',
            'total_amount' => 'required|numeric|min:1',
            'due_date' => 'required|array|min:1',
            'due_date.*' => 'required|date',
            'amount' => 'required|array|min:1',
            'amount.*' => 'required|numeric|min:1',
        ]);

        $validator->after(function ($validator) use ($request) {
            $expectedTotal = floatval($request->total_amount);
            $sumOfAmounts = collect($request->amount)->sum(function ($val) {
                return floatval($val);
            });

            if (round($sumOfAmounts, 2) !== round($expectedTotal, 2)) {
                $validator->errors()->add('amount', 'The sum of all installment amounts must equal the total amount.');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $package = CourseFee::findOrFail($request->package);
            $displayCurrency = package_currency_code($package->currency);
            $totalSettling = package_settling_amount($request->total_amount, $displayCurrency);

            $installmentAmounts = collect($request->amount)->map(function ($val) use ($displayCurrency) {
                return package_settling_amount($val, $displayCurrency);
            });

            $payment = Payment::findOrFail($id);
            $payment->user_id = $request->student;
            $payment->course_id = $request->course;
            $payment->package_id = $request->package;
            $payment->price = $totalSettling;
            $payment->currency = $displayCurrency;
            $payment->total_installment = count($request->amount);
            $payment->terms_conditions = $request->terms_conditions;
            $payment->save();

            Installment::where('payment_id', $payment->id)->delete();

            foreach ($installmentAmounts as $i => $amount) {
                Installment::create([
                    'name' => 'Installment ' . ($i + 1),
                    'duration_months' => $i + 1,
                    'remaining_amount' => $amount,
                    'paid_amount' => 0,
                    'installment_number' => $i + 1,
                    'due_date' => $request->due_date[$i],
                    'payment_id' => $payment->id,
                    'user_id' => $request->student,
                    'status' => 'pending',
                ]);
            }

            DB::commit();
            return redirect()->route('admin.payments.index')->with('success', 'Payment updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('fail', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $payment = Payment::findOrFail($id);
            Installment::where('payment_id', $id)->delete();
            $payment->delete();

            DB::commit();
            return redirect()->route('admin.payments.index')->with('success', 'Payment deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('fail', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function getPaymentDetails(Request $request)
    {
        $user = User::find($request->user_id);
        $course = Course::find($request->course_id);
        $payment = Payment::where('user_id', $user->id)->where('course_id', $course->id)->first();

        if ($payment) {
            return response()->json([
                'success' => true,
                'student_name' => $user->name,
                'course_name' => $course->name,
                'amount_due' => number_format($payment->amount, 2)
            ]);
        }

        return response()->json(['success' => false]);
    }

    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|exists:payments,id',
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'package_id' => 'required|exists:course_fees,id',
            'total_amount' => 'required|numeric|min:1',
            'installment_dates' => 'required|array|min:1',
            'installment_dates.*' => 'required|date|after:today',
            'installment_amounts' => 'required|array|min:1',
            'installment_amounts.*' => 'required|numeric|min:1',
        ]);

        // Custom validation for matching total
        $validator->after(function ($validator) use ($request) {
            $expectedTotal = floatval($request->total_amount);
            $sumOfAmounts = collect($request->installment_amounts)->sum(function ($val) {
                return floatval($val);
            });

            if (round($sumOfAmounts, 2) !== round($expectedTotal, 2)) {
                $validator->errors()->add('amount', 'The sum of all installment amounts must equal the total amount.');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $payment = Payment::findOrFail($request->payment_id);
        $payment->total_installment = $request->installments ?? 1;
        $payment->terms_conditions = $request->terms_conditions;
        $payment->save();

        // dd($payment->toArray());

        // Step 2: Generate Installments Based on Payment Plan
        $installment_amounts = $request->installment_amounts;
        $installment_dates = $request->installment_dates;
        for ($i = 0; $i < $payment->total_installment; $i++) {
            Installment::create([
                'name' => "Installment " . ($i + 1),
                'duration_months' => $i + 1,
                'remaining_amount' => $installment_amounts[$i],
                'paid_amount' => 0,
                'installment_number' => $i + 1,
                'due_date' => $installment_dates[$i], // Ensure Correct Format
                'payment_id' => $payment->id,
                'user_id' => $request->student_id,
                'status' => 'pending'
            ]);
        }

        return redirect()->back()->with('success', 'Payment and Installments created successfully.');
    }

    public function manualPay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'installment_id' => 'required|exists:installments,id',
            'amount' => 'required|numeric|min:0.01',
            'paid_date' => 'nullable|date',
            'payment_type' => 'nullable|string|in:bank,cash,card,cheque',
            'notes' => 'nullable|string',
            'is_edit' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $isEdit  = (bool) ($data['is_edit'] ?? false);

        try {
            $installment = DB::transaction(function () use ($data, $isEdit) {

                $installment = Installment::with(['payment.courseFee'])
                    ->whereKey($data['installment_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $payment = $installment->payment;
                $displayAmount = round((float) $data['amount'], 2);
                $amount = payment_aed_from_display_amount($payment, $displayAmount);
                $paid = round((float) $installment->paid_amount, 2);
                $remaining = round((float) $installment->remaining_amount, 2);
                $total = round($paid + $remaining, 2);
                $currency = payment_display_currency($payment);

                if ($total <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'This installment is already fully paid.',
                    ]);
                }

                if ($isEdit) {
                    $totalDisplay = payment_display_amount_from_aed($payment, $total);

                    if ($displayAmount > $totalDisplay) {
                        throw ValidationException::withMessages([
                            'amount' => 'You cannot set paid amount more than the total installment amount (' . $currency . ' ' . number_format($totalDisplay, 2) . ').',
                        ]);
                    }

                    $installment->paid_amount = $amount;
                    $installment->remaining_amount = round($total - $amount, 2);
                } else {
                    $remainingDisplay = payment_display_amount_from_aed($payment, $remaining);

                    if ($displayAmount > $remainingDisplay) {
                        throw ValidationException::withMessages([
                            'amount' => 'You cannot pay more than the remaining amount (' . $currency . ' ' . number_format($remainingDisplay, 2) . ').',
                        ]);
                    }

                    $installment->paid_amount = round($paid + $amount, 2);
                    $installment->remaining_amount = round($remaining - $amount, 2);
                }

                if (isset($data['paid_date'])) $installment->paid_date = $data['paid_date'];
                if (isset($data['payment_type'])) $installment->payment_method = $data['payment_type'];
                if (isset($data['notes'])) $installment->notes = $data['notes'];

                if ($installment->remaining_amount <= 0) {
                    $installment->remaining_amount = 0.00;
                    $installment->status = 'paid';
                } elseif ($installment->paid_amount > 0) {
                    $installment->status = 'partial';
                } else {
                    $installment->status = 'pending';
                }

                $installment->save();

                return $installment->fresh();
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $emailTemplate = Email::where('name', 'fees-paid')->first();
        if ($emailTemplate) {
            $installment->loadMissing('payment.courseFee');
            $paidDisplay = format_payment_aed_amount($installment->payment, (float) $installment->paid_amount);

            $emailBody = str_replace(
                ['{name}', '{email}', '{fees-paid}', '{fees-installment}'],
                [
                    $installment->user->name,
                    $installment->user->email,
                    $paidDisplay,
                    $installment->installment_number
                ],
                $emailTemplate->body
            );

            $ccEmails = $emailTemplate->cc ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
            $bccEmails = $emailTemplate->bcc ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

            // Tip: usually payer ko email
            Mail::to($installment->user->email)
                ->cc($ccEmails)
                ->bcc($bccEmails)
                ->send(
                    new UserMail(
                        $installment->user,
                        $emailTemplate->subject,
                        normalize_payment_email_body($emailBody)
                    )
                );
        }

        return response()->json([
            'success' => true,
            'message' => $installment->status === 'paid' ? 'Installment fully paid.' : 'Payment applied.',
            'remaining' => $installment->remaining_amount,
            'status' => $installment->status,
        ]);
    }

    public function manualPayDelete($id)
    {
        $installment = Installment::findOrFail($id);

        $paid = round((float) $installment->paid_amount, 2);
        $remaining = round((float) $installment->remaining_amount, 2);
        $total = round($paid + $remaining, 2);

        $installment->remaining_amount = $total;
        $installment->paid_amount = 0;
        $installment->paid_date = null;
        $installment->payment_method = null;
        $installment->notes = null;
        $installment->status = 'pending';
        $installment->save();

        return redirect()->back()->with('success', 'Receipt deleted successfully!');
    }

    public function sendInvoice($id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === 'Active') {
            return redirect()->back()->with('warning', 'This invoice is already active.');
        }

        $sent = app(InvoiceService::class)->activateAndSend($payment);

        if (!$sent) {
            return redirect()->back()->with(
                'fail',
                'Invoice could not be emailed. Check Admin → Emails for a "Send Invoice" template and verify mail settings in .env.'
            );
        }

        return redirect()->back()->with('success', 'Invoice emailed to the student and activated.');
    }

    public function sendReceipt($id)
    {
        $installment = Installment::with(['payment.course', 'payment.courseFee', 'user'])->findOrFail($id);

        $pdf = PDF::loadView('admin.payments.receipt-pdf', compact('installment'))
            ->setPaper('a4');

        $filename = 'RC-' . str_pad($installment->id, 6, '0', STR_PAD_LEFT) . '.pdf';

        $emailTemplate = Email::where('name', 'send-receipt')->first();

        if ($emailTemplate) {
            $user = $installment->user;

            $emailBody = str_replace(
                ['{name}', '{email}', '{role}'],
                [$user->name, $user->email, ucfirst($user->roles->first()->name ?? '')],
                $emailTemplate->body
            );

            $ccEmails = !empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
            $bccEmails = !empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

            Mail::to($user->email)
                ->cc($ccEmails)
                ->bcc($bccEmails)
                ->send(
                    (new UserMail($user, $emailTemplate->subject, $emailBody))
                        ->attachData($pdf->output(), $filename, ['mime' => 'application/pdf'])
                );
        }

        return redirect()->back()->with('success', 'Receipt sent successfully.');
    }

    public function updateStatus(Request $request)
    {
        $payment = Payment::findOrFail($request->payment_id);
        $payment->status = $request->status;
        $payment->save();

        return response()->json(['success' => true]);
    }
}