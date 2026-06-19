<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //Auth::logout(); // Logs out the authenticated user

        //$request->session()->invalidate(); // Invalidate the session
        //$request->session()->regenerateToken(); // Regenerate CSRF token

        //return redirect('/'); 
        //return redirect()->route('login');
        return view('user.home');
    }

    public function logout()
    {
        $request = request();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $user = Auth::user();

        if ($user) {
            record_user_activity(
                'User Logout',
                'Session ended',
                public_login_url(),
                activity_audience_for_user($user),
                $user->id,
                null,
                $request,
                $sessionId
            );
        }

        Auth::logout();

        // Redirect to a specified route after logout
        return redirect()->route('login');
    }
}
