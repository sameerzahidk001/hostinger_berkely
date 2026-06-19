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

        return view('auth.login');
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
        ]);

        // Attempt authentication
        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        $admin = Admin::where('email', $credentials['email'])->first();
        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            throw ValidationException::withMessages([
                'email' => 'Admin accounts must sign in at ' . admin_login_url(),
            ]);
        }

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $role = Auth::user()->roles()->value('name');
            if (is_admin_login_role($role)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => 'Admin accounts must sign in at ' . admin_login_url(),
                ]);
            }

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

        if ($user && $user->hasPermission('dashboard-read')) {
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