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
use App\Services\PanelActivityService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

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
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.home');
        }

        if (Auth::check()) {
            $role = Auth::user()->roles()->value('name');

            if (is_restricted_panel_role($role)) {
                return redirect()->route('admin.home');
            }

            return redirect()->to(public_login_url());
        }

        return view('admin.auth.login');
    }

    public function adminAuth(Request $request){
        $request->validate([
            'email'=>'required',
            'password'=>'required',
        ]);

        if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password])) {
            return redirect()->route('admin.home');
        }

        $panelUser = User::where('email', $request->email)->first();
        if ($panelUser && Hash::check($request->password, $panelUser->password)) {
            Session::flash(
                'error-message',
                'This account must sign in at ' . public_login_url()
            );

            return back();
        }

        Session::flash('error-message','Invalid Email or Password');
        return back();
    }

    public function dashboard(Request $request)
    {
        $role = normalize_panel_role(panel_role_name());

        if ($role === 'content_writer') {
            return $this->panelDashboard($request, 'content_writer');
        }

        if ($role === 'accountant') {
            return $this->panelDashboard($request, 'accountant');
        }

        return $this->adminDashboard();
    }

    private function panelDashboard(Request $request, string $role)
    {
        $service = app(PanelActivityService::class);
        $userId = $role === 'content_writer' ? audit_user_id() : null;
        $includePayments = $role === 'accountant';
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $summary = $service->summary($userId, $dateFrom, $dateTo, $includePayments);
        $activities = $service->paginatedFeed(
            $userId,
            $dateFrom,
            $dateTo,
            $includePayments,
            15,
            (int) $request->query('page', 1),
            $request->url(),
            $request->query()
        );

        return view('admin.dashboard.panel', [
            'summary' => $summary,
            'activities' => $activities,
            'includePayments' => $includePayments,
            'showUserColumn' => $role === 'accountant',
            'showTotals' => $role === 'content_writer',
            'scopeLabel' => $role === 'content_writer' ? 'My' : 'All',
            'activityTitle' => $role === 'content_writer'
                ? 'My activity history'
                : 'All users activity history',
        ]);
    }

    private function adminDashboard()
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
        if (!panel_profile_user()) {
            return redirect()->route('admin.login');
        }

        return view('admin.profile');
    }

    public function profile_update(Request $request)
    {
        if (Auth::guard('admin')->check()) {
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

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        $user->name = $request->username;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    //todo: admin logout functionality
    public function logout(){
        $wasPanelUser = Auth::check()
            && is_restricted_panel_role(Auth::user()->roles()->value('name'));

        Auth::guard('admin')->logout();
        Auth::logout();

        return $wasPanelUser
            ? redirect()->to(public_login_url())
            : redirect()->route('admin.login');
    }
}
