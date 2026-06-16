<?php

namespace App\Http\Controllers\Admin;

use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
//use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\{Subject,Course,User,Admin,Instructor};
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdminController extends Controller
{
    //use AuthenticatesUsers;

    // /**
    //  * Where to redirect users after login.
    //  *
    //  * @var string
    //  */
    //protected $redirectTo = 'admin/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('guest')->except('logout');
    //     $this->middleware('guest:admin')->except('logout');
    // }
    //todo: admin login form
    public function login()
    {
        return view('admin.auth.login');
    }

    //todo: admin login functionality
    public function adminAuth(Request $request){
        //return $request;
        $request->validate([
            'email'=>'required',
            'password'=>'required',
        ]);

        if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password])) {
            return redirect()->route('admin.home');
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $role = Auth::user()->roles()->value('name');
            if (in_array($role, ['librarian', 'accountant'], true)) {
                return redirect()->route('admin.home');
            }

            Auth::logout();
        }

        Session::flash('error-message','Invalid Email or Password');
        return back();
    }

    public function dashboard()
    {   
        $data['courses_count'] = Course::count();
        $data['student_count'] = User::count();
        $data['instructor_count'] = Instructor::count();
        $data['admin_count'] = Admin::count();

        $data['user_browsers'] = PageView::select('browser','platform')
            ->distinct()
            ->limit(6)
            ->get();

        $data['latest_page_views'] = PageView::with('userBehaviors')->groupBy('url')
            ->latest() // Optional: Order by latest timestamp
            ->paginate(6);

        $data['total_page_views_count'] = PageView::groupBy('url')->count();

        $today = Carbon::today()->toDateString(); 
        $data['total_view_count_today'] = PageView::whereDate('created_at', $today)->count();

        $countries = PageView::select('country', DB::raw('count(*) as total'))
            ->groupBy('country')
            ->get();
        
            $labels = [];
            $dataValues = [];
            $backgroundColors = [];

            foreach ($countries as $country) {
                $labels[] = $country->country;
                $dataValues[] = $country->total;
            
                // Generate a random color
                $randomColor = sprintf('#%02X%02X%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
                $backgroundColors[] = $randomColor;
            }

            $data['countryLabels'] = $labels;
            $data['countryData'] = $dataValues;
            $data['backgroundColors'] = $backgroundColors;
            //$data['total_view_count_today'] = PageView::whereDate('created_at', $today)->count();

            // Set the date range: from 6 days ago to now (which includes today)
            $today = Carbon::now(); // Now includes today's complete records
            $sevenDaysAgo = Carbon::today()->subDays(6); // Start from midnight 6 days ago

            // Initialize an array to hold the view counts for the last 7 days
            $viewCounts = [];

            // Create a list of the last 7 days, and initialize view counts to 0
            for ($i = 0; $i <= 6; $i++) {
                $date = $sevenDaysAgo->copy()->addDays($i)->toDateString(); // Generate each date
                $viewCounts[$date] = 0; // Set default view count to 0
            }

            // Fetch the actual view counts from the database, grouped by date
            $viewCountsQuery = PageView::selectRaw('DATE(created_at) as date, SUM(view_count) as total_views')
                ->whereBetween('created_at', [$sevenDaysAgo, $today]) // Use now() for the end date to include today’s data
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();

            // Populate the $viewCounts array with the actual view counts
            foreach ($viewCountsQuery as $record) {
                $viewCounts[$record->date] = $record->total_views;
            }

            // Pass the data to the view
            $data['view_counts'] = collect($viewCounts)->map(function($value, $key) {
                return ['date' => $key, 'total_views' => $value];
            })->values(); // Convert associative array to a collection of objects

        
        return view('admin.home')->with($data);
    }

    public function profile()
    {
        return view('admin.profile');
    }
     public function profile_update(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'username' => 'required|string|max:255|unique:admins,username,' . $admin->id,
            'email' => 'required|email|max:255|unique:admins,email,' . $admin->id,
            'password' => 'nullable|string|min:8',
        ]);

        $admin->username = $request->username;
        $admin->email = $request->email;

        if ($request->filled('password')) {
$admin->password = bcrypt($request->password);
        }

        $admin->save();

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    //todo: admin logout functionality
    public function logout(){
        Auth::guard('admin')->logout();
        Auth::logout();
        return redirect()->route('admin.login');
    }
}
