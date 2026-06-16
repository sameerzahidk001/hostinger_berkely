<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\UserMail;
use App\Models\Email;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class VerificationController extends Controller
{
    public function verify(Request $request, $id, $hash) {

        $user = User::findOrFail($id);

        // Check if the hash is valid
        if (! hash_equals(sha1($user->email), $hash)) {
            abort(403, 'Invalid verification link.');
        }

        // Check if the email is already verified
        if ($user->hasVerifiedEmail()) {
            return redirect($this->redirectTo())->with('message', 'Email already verified.');
        }

        // Mark email as verified
        $user->markEmailAsVerified();
        event(new Verified($user));

        Log::info("User verified: " . $user->email);

        return redirect($this->redirectTo())->with('success', 'Email verified successfully!');
    }

    /**
     * Where to redirect users after verification.
     *
     * @return string
     */
    protected function redirectTo()
    {
        $user = Auth::user();

        if ($user && $user->hasPermission('dashboard-read')) {
            return route('user.home');
        }

        return route('user.profile');
    }

    /**
     * Show the email verification page or redirect if already verified.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function show()
    {
        return Auth::user()->hasVerifiedEmail()
            ? redirect($this->redirectTo())->with('message', 'Email already verified.')
            : view('auth.verify'); // Ensure this view exists
    }

    public function resend(Request $request)
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return redirect($this->redirectTo())->with('message', 'Email already verified.');
        }

        // Check if the email was sent within the last 60 seconds
        $lastSentTime = session('verification_email_sent_at');

        if ($lastSentTime && now()->diffInSeconds($lastSentTime) < 60) {
            return back()->with('error', 'Please check your inbox or spam folder. You can request another verification email after ' . (60 - now()->diffInSeconds($lastSentTime)) . ' seconds.');
        }

        // Store the current timestamp in session
        session(['verification_email_sent_at' => now()]);

        // Generate the new email verification link
        $verificationLink = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Fetch email template from database
        $emailTemplate = Email::where('name', 'email-verification')->first();

        if ($emailTemplate) {
            $emailBody = str_replace(
                ['{name}', '{email}', '{email-verification}'],
                [$user->name, $user->email, $verificationLink],
                $emailTemplate->body
            );

            $ccEmails = !empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
            $bccEmails = !empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

            Mail::to($user->email)
                ->cc($ccEmails)
                ->bcc($bccEmails)
                ->send(new UserMail($user, $emailTemplate->subject, $emailBody));
        }

        return back()->with('message', 'A new verification link has been sent. Please check your inbox.');
    }

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }
}