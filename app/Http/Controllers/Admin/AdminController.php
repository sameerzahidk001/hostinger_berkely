<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Services\PanelActivityService;
use App\Services\UserActivityLogService;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
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
            $request->session()->regenerate();
            $admin = Auth::guard('admin')->user();
            record_user_activity(
                'Admin Login',
                'Session started via ' . admin_login_url(),
                admin_login_url(),
                'staff',
                null,
                $admin?->id,
                $request
            );

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
            return $this->activityDashboard($request, [
                'userId' => audit_user_id(),
                'showMyStats' => true,
                'showSiteStats' => true,
                'includePayments' => false,
                'showUserColumn' => true,
                'showUserFilter' => false,
                'showSessionColumn' => true,
                'logAudience' => 'staff',
                'activityTitle' => 'My activity history',
            ]);
        }

        if ($role === 'accountant') {
            return $this->activityDashboard($request, [
                'userId' => null,
                'roleFilter' => 'accountant',
                'showMyStats' => false,
                'showSiteStats' => false,
                'showInvoiceStats' => true,
                'includePayments' => true,
                'showUserColumn' => true,
                'showUserFilter' => false,
                'showSessionColumn' => true,
                'logAudience' => 'staff',
                'activityTitle' => 'Accountant activities',
            ]);
        }

        return $this->activityDashboard($request, [
            'userId' => $request->filled('user_id') ? (int) $request->query('user_id') : null,
            'roleFilter' => $request->filled('role') ? $request->query('role') : null,
            'showMyStats' => false,
            'showSiteStats' => true,
            'showInvoiceStats' => true,
            'includePayments' => true,
            'showUserColumn' => true,
            'showUserFilter' => true,
            'showStudentTable' => true,
            'showSessionColumn' => true,
            'logAudience' => 'staff',
            'activityTitle' => $this->adminActivityTitle($request),
            'studentActivityTitle' => 'Student activity history',
        ]);
    }

    private function adminActivityTitle(Request $request): string
    {
        if ($request->filled('user_id')) {
            $user = User::find($request->query('user_id'));

            return $user
                ? 'Activity history - ' . ($user->name ?? $user->email)
                : 'User activity history';
        }

        return match ($request->query('role')) {
            'content_writer' => 'Content writer activities',
            'accountant' => 'Payment activities',
            'instructor' => 'Instructor activities',
            default => 'Staff & panel user activity',
        };
    }

    private function activityDashboard(Request $request, array $options)
    {
        $service = app(PanelActivityService::class);
        $logService = app(UserActivityLogService::class);
        $userId = $options['userId'] ?? null;
        $roleFilter = $options['roleFilter'] ?? ($request->filled('role') ? $request->query('role') : null);
        $includePayments = (bool) ($options['includePayments'] ?? false);
        $logAudience = (string) ($options['logAudience'] ?? 'staff');
        $showStudentTable = (bool) ($options['showStudentTable'] ?? false);
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $restrictToUserIds = null;
        $paymentsOnly = false;

        if ($userId) {
            $restrictToUserIds = null;
        } elseif ($roleFilter === 'accountant') {
            $paymentsOnly = true;
        } elseif ($roleFilter) {
            $restrictToUserIds = $service->userIdsForRole($roleFilter);
        }

        $summary = $service->summary($userId, $dateFrom, $dateTo, $includePayments);
        $activities = $service->paginatedFeedWithLogs(
            $userId,
            $dateFrom,
            $dateTo,
            $includePayments && ! $paymentsOnly,
            $logAudience,
            15,
            (int) $request->query('page', 1),
            $request->url(),
            $request->query(),
            $restrictToUserIds,
            $paymentsOnly
        );

        $studentUserId = $request->filled('student_user_id')
            ? (int) $request->query('student_user_id')
            : null;

        $studentActivities = $showStudentTable
            ? $logService->paginatedFeed(
                'student',
                $studentUserId,
                $dateFrom,
                $dateTo,
                15,
                (int) $request->query('student_page', 1),
                $request->url(),
                $request->query()
            )
            : null;

        return view('admin.dashboard.panel', [
            'summary' => $summary,
            'activities' => $activities,
            'studentActivities' => $studentActivities,
            'activityLogsEnabled' => app(UserActivityLogService::class)->tableExists(),
            'includePayments' => $includePayments,
            'showMyStats' => (bool) ($options['showMyStats'] ?? false),
            'showSiteStats' => (bool) ($options['showSiteStats'] ?? false),
            'showInvoiceStats' => (bool) ($options['showInvoiceStats'] ?? false),
            'showUserColumn' => (bool) ($options['showUserColumn'] ?? false),
            'showUserFilter' => (bool) ($options['showUserFilter'] ?? false),
            'showStudentTable' => $showStudentTable,
            'showSessionColumn' => (bool) ($options['showSessionColumn'] ?? false),
            'filterUsers' => ($options['showUserFilter'] ?? false) ? $service->filterUsers() : collect(),
            'studentFilterUsers' => $showStudentTable ? $logService->studentFilterUsers() : collect(),
            'activityTitle' => $options['activityTitle'] ?? 'Activity history',
            'studentActivityTitle' => $options['studentActivityTitle'] ?? 'Student activity history',
        ]);
    }

    public function exportActivity(Request $request)
    {
        $role = normalize_panel_role(panel_role_name());
        $service = app(PanelActivityService::class);
        $logService = app(UserActivityLogService::class);

        $userId = $request->filled('user_id') ? (int) $request->query('user_id') : null;
        $roleFilter = $request->filled('role') ? $request->query('role') : null;
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $exportType = $request->query('type', 'staff');
        $includePayments = $role !== 'content_writer';
        $restrictToUserIds = null;
        $paymentsOnly = false;

        if ($role === 'content_writer') {
            $userId = audit_user_id();
        } elseif ($roleFilter === 'accountant') {
            $paymentsOnly = true;
        } elseif ($roleFilter) {
            $restrictToUserIds = $service->userIdsForRole($roleFilter);
        }

        if ($exportType === 'student') {
            $rows = $logService->buildFeed('student', $request->filled('student_user_id') ? (int) $request->query('student_user_id') : null, $dateFrom, $dateTo);
            $filename = 'student-activity-history';
            $showUserColumn = true;
        } else {
            $activities = $service->buildFeed(
                $userId,
                $dateFrom,
                $dateTo,
                $includePayments && ! $paymentsOnly,
                $restrictToUserIds,
                $paymentsOnly
            );

            $logs = $logService->buildFeed('staff', $userId, $dateFrom, $dateTo);

            if ($restrictToUserIds !== null) {
                $logs = $logs->filter(function (array $row) use ($restrictToUserIds) {
                    return ! empty($row['actor_id']) && in_array((int) $row['actor_id'], $restrictToUserIds, true);
                });
            }

            if ($logService->tableExists()) {
                $activities = $activities->filter(function (array $row) {
                    return ! in_array($row['action'], ['Page Updated', 'Course Updated'], true);
                });
            }

            $rows = $activities
                ->merge($logs)
                ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
                ->values();

            $filename = 'dashboard-activity-history';
            $showUserColumn = true;
        }

        $headers = ['Date & Time', 'Name', 'Activity', 'Item', 'Session', 'URL'];
        $callback = function () use ($rows, $headers, $showUserColumn) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $activity) {
                fputcsv($handle, [
                    $activity['occurred_at']->format('Y-m-d H:i:s'),
                    $activity['user_name'] ?? '—',
                    $activity['action'] ?? '',
                    $activity['item'] ?? '',
                    $activity['session_id'] ?? '',
                    $activity['url'] ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename . '_' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
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
        $imageRules = [
            'image_path' => 'nullable|string',
            'image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];

        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            $request->validate(array_merge([
                'username' => 'required|string|max:255|unique:admins,username,' . $admin->id,
                'email' => 'required|email|max:255|unique:admins,email,' . $admin->id,
                'password' => 'nullable|string|min:8',
            ], $imageRules));

            $admin->username = $request->username;
            $admin->email = $request->email;

            if ($request->filled('password')) {
                $admin->password = bcrypt($request->password);
            }

            apply_profile_image_from_request($admin, $request);

            $admin->save();
            Auth::guard('admin')->setUser($admin->fresh());

            return redirect()->back()->with('success', 'Profile updated successfully!');
        }

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('admin.login');
        }

        $request->validate(array_merge([
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ], $imageRules));

        $user->name = $request->username;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        apply_profile_image_from_request($user, $request);

        $user->save();
        Auth::setUser($user->fresh());

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    public function logout(Request $request){
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            record_user_activity(
                'Admin Logout',
                'Session ended',
                public_login_url(),
                'staff',
                null,
                $admin?->id,
                $request,
                $sessionId
            );
            Auth::guard('admin')->logout();
        }

        if (Auth::check()) {
            $user = Auth::user();
            $audience = activity_audience_for_user($user);
            $logoutAction = $audience === 'staff' ? 'Staff Logout' : 'User Logout';

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
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(public_login_url());
    }
}
