<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        if (request()->boolean('redirect_to_cart')) {
            session(['redirect_to_cart' => true]);
        }

        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.home');
        }

        if (Auth::check()) {
            return redirect()->intended($this->redirectTo());
        }

        $this->issueLoginCaptcha();

        return view('auth.login');
    }

    /**
     * Issue a new alphanumeric login captcha code.
     */
    protected function issueLoginCaptcha(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        session(['login_captcha_answer' => $code]);
        session()->forget(['login_captcha_a', 'login_captcha_b']);

        return $code;
    }

    /**
     * Render captcha as a distorted image (not a math question).
     */
    public function captchaImage()
    {
        if (! session()->has('login_captcha_answer') || request()->boolean('refresh')) {
            $this->issueLoginCaptcha();
        }

        $code = (string) session('login_captcha_answer', 'ERROR');
        $width = 160;
        $height = 48;

        if (! function_exists('imagecreatetruecolor')) {
            return response($code, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        $img = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($img, 245, 247, 250);
        $textColor = imagecolorallocate($img, 20, 40, 80);
        $noise = imagecolorallocate($img, 180, 190, 210);
        imagefilledrectangle($img, 0, 0, $width, $height, $bg);

        for ($i = 0; $i < 60; $i++) {
            imagesetpixel($img, random_int(0, $width - 1), random_int(0, $height - 1), $noise);
        }
        for ($i = 0; $i < 4; $i++) {
            imageline(
                $img,
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height),
                $noise
            );
        }

        $font = 5;
        $charWidth = imagefontwidth($font);
        $charHeight = imagefontheight($font);
        $totalWidth = $charWidth * strlen($code);
        $x = (int) (($width - $totalWidth) / 2);
        $y = (int) (($height - $charHeight) / 2);
        imagestring($img, $font, $x, $y, $code, $textColor);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Handle user login manually (without AuthenticatesUsers).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {       
        // Validate user input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'captcha' => 'required|string|max:12',
        ]);

        $expectedCaptcha = strtoupper((string) session('login_captcha_answer', ''));
        $givenCaptcha = strtoupper(preg_replace('/\s+/', '', (string) $request->input('captcha', '')));
        if ($expectedCaptcha === '' || ! hash_equals($expectedCaptcha, $givenCaptcha)) {
            $this->issueLoginCaptcha();

            throw ValidationException::withMessages([
                'captcha' => 'Incorrect captcha. Please try again.',
            ]);
        }

        // Attempt authentication
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember') || $request->has('chk');

        $admin = Admin::where('email', $credentials['email'])->first();
        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            $this->issueLoginCaptcha();
            throw ValidationException::withMessages([
                'email' => 'Admin accounts must sign in at ' . admin_login_url(),
            ]);
        }

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            session()->forget(['login_captcha_answer', 'login_captcha_a', 'login_captcha_b']);

            $role = Auth::user()->roles()->value('name');
            if (is_admin_login_role($role)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => 'Admin accounts must sign in at ' . admin_login_url(),
                ]);
            }

            $user = Auth::user();
            $loginAction = activity_audience_for_user($user) === 'staff' ? 'Staff Login' : 'User Login';
            record_user_activity(
                $loginAction,
                'Session started via ' . public_login_url(),
                public_login_url(),
                activity_audience_for_user($user),
                $user?->id,
                null,
                $request
            );

            if (session()->has('intended_package_id')) {
                $packageId = session()->pull('intended_package_id');

                // Only add if not already in cart
                $cartItem = CartItem::firstOrCreate([
                    'user_id' => Auth::id(),
                    'course_fee_package_id' => $packageId,
                ], [
                    'quantity' => 0
                ]);

                $cartItem->increment('quantity');
                return redirect()->route('cart.index')->with('success', 'Item automatically added to cart after login.');
            }

            if ($request->boolean('redirect_to_cart') || session()->pull('redirect_to_cart')) {
                return redirect()->route('cart.index');
            }

            // Redirect based on role
            return redirect()->intended($this->redirectTo());
        }

        $this->issueLoginCaptcha();

        // Throw validation error if login fails
        throw ValidationException::withMessages([
            'email' => __('These credentials do not match our records.'),
        ]);
    }

    /**
     * Handle user logout.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        if (Auth::check()) {
            $user = Auth::user();
            $audience = activity_audience_for_user($user);
            $logoutAction = $audience === 'staff' ? 'Staff Log out' : 'User Log out';

            record_user_activity(
                $logoutAction,
                'Session ended',
                public_login_url(),
                $audience,
                $user->id,
                null,
                $request,
                $sessionId
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('status', 'You have been logged out.');
    }

    /**
     * Redirect users after login based on their role.
     *
     * @return string
     */
    protected function redirectTo()
    {
        $user = Auth::user();
        $role = $user?->roles()->value('name');

        if (is_restricted_panel_role($role)) {
            return route('admin.home');
        }

        if ($user && ($user->hasPermission('dashboard-read') || $user->roles()->where('name', 'instructor')->exists())) {
            return route('user.home');
        }

        return route('user.profile');
    }

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}