<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        Auth::logout();

        return redirect()->route('login');
    }
}
