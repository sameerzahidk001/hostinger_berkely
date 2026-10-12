<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Admin;
use App\Models\User;
use App\Models\Country;
use App\Services\ContentDashboardReportService;
use App\Services\PanelActivityService;
use App\Services\UserActivityLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;

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

        if ($role === 'instructor' || normalize_role_key($role) === 'instructor') {
            return redirect()->route('user.home');
        }

        if ($role === 'content_writer') {
            return $this->activityDashboard($request, [
                'userId' => audit_user_id(),
                'showMyStats' => true,
                'showSiteStats' => true,
                'includePayments' => false,
                'showUserColumn' => true,
                'showUserFilter' => false,
                'showSessionColumn' => true,
                'showContentReports' => true,
                'showContentReportUserFilter' => false,
                'contentReportActorId' => audit_user_id(),
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

        $reportActor = $this->resolveContentReportActor($request);

        return $this->activityDashboard($request, [
            'userId' => $request->filled('user_id') ? (int) $request->query('user_id') : null,
            'roleFilter' => $request->filled('role') ? $request->query('role') : null,
            'showMyStats' => false,
            'showSiteStats' => true,
            // Invoice widgets belong on the accountant dashboard (coming separately).
            'showInvoiceStats' => false,
            'includePayments' => true,
            'showUserColumn' => true,
            'showUserFilter' => true,
            'showStudentTable' => true,
            'showSessionColumn' => true,
            'showContentReports' => true,
            'showContentReportUserFilter' => true,
            'contentReportActorId' => $reportActor['id'],
            'contentReportActorName' => $reportActor['name'],
            'contentReportActorKey' => $reportActor['key'],
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
        $today = now()->toDateString();
        $dateFrom = $request->filled('date_from') ? (string) $request->query('date_from') : $today;
        $dateTo = $request->filled('date_to') ? (string) $request->query('date_to') : $today;

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

        $showContentReports = (bool) ($options['showContentReports'] ?? false)
            && class_exists(ContentDashboardReportService::class);
        $contentReportActorId = $options['contentReportActorId'] ?? audit_user_id();
        $contentReportActorName = $options['contentReportActorName']
            ?? $this->contentReportActorDisplayName((int) ($contentReportActorId ?: 0));
        $contentReportActorKey = $options['contentReportActorKey']
            ?? $this->defaultContentReportActorKey();
        $showContentReportUserFilter = (bool) ($options['showContentReportUserFilter'] ?? false);
        $contentReports = null;
        $contentReportMetricLabels = [
            'pages' => [],
            'courses' => [],
            'seo' => [],
        ];

        if ($showContentReports) {
            $reportService = app(ContentDashboardReportService::class);
            $contentReports = $reportService->reportBundle(
                $contentReportActorId ? (int) $contentReportActorId : null,
                $dateFrom,
                $dateTo
            );
            $contentReportMetricLabels = [
                'pages' => $reportService->metricLabels('pages', $contentReportActorName),
                'courses' => $reportService->metricLabels('courses', $contentReportActorName),
                'seo' => $reportService->metricLabels('seo', $contentReportActorName),
            ];
        }

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
            'showContentReports' => $showContentReports,
            'showContentReportUserFilter' => $showContentReportUserFilter,
            'contentReports' => $contentReports,
            'contentReportActorId' => $contentReportActorId,
            'contentReportActorName' => $contentReportActorName,
            'contentReportActorKey' => $contentReportActorKey,
            'contentReportMetricLabels' => $contentReportMetricLabels,
            'contentReportUsers' => $showContentReportUserFilter ? $this->contentReportFilterUsers() : collect(),
            'filterUsers' => ($options['showUserFilter'] ?? false) ? $service->filterUsers() : collect(),
            'studentFilterUsers' => $showStudentTable ? $logService->studentFilterUsers() : collect(),
            'activityTitle' => $options['activityTitle'] ?? 'Activity history',
            'studentActivityTitle' => $options['studentActivityTitle'] ?? 'Student activity history',
            'activityDateFrom' => $dateFrom,
            'activityDateTo' => $dateTo,
        ]);
    }

    public function contentReportDetails(Request $request)
    {
        $module = (string) $request->query('module', 'pages');
        $metric = (string) $request->query('metric', 'total');
        $actor = $this->resolveContentReportActor($request);
        $actorId = $actor['id'];
        $rows = app(ContentDashboardReportService::class)->detailRows(
            $module,
            $metric,
            $actorId,
            $request->query('date_from'),
            $request->query('date_to'),
            $request->query('q')
        );

        $labels = app(ContentDashboardReportService::class)->metricLabels($module, $actor['name']);

        return response()->json([
            'module' => $module,
            'metric' => $metric,
            'label' => $labels[$metric] ?? ucfirst(str_replace('_', ' ', $metric)),
            'count' => $rows->count(),
            'rows' => $rows->values(),
        ]);
    }

    public function contentReportExport(Request $request)
    {
        $module = (string) $request->query('module', 'pages');
        $metric = (string) $request->query('metric', 'total');
        $format = strtolower((string) $request->query('format', 'excel'));
        $actor = $this->resolveContentReportActor($request);
        $actorId = $actor['id'];
        $service = app(ContentDashboardReportService::class);
        $labels = $service->metricLabels($module, $actor['name']);
        $label = $labels[$metric] ?? ucfirst(str_replace('_', ' ', $metric));
        $rows = $service->detailRows(
            $module,
            $metric,
            $actorId,
            $request->query('date_from'),
            $request->query('date_to'),
            $request->query('q')
        );
        $username = panel_profile_name() ?: 'User';
        $filenameBase = 'content-report-' . $module . '-' . $metric . '_' . now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.dashboard.report-pdf', [
                'module' => ucfirst($module),
                'label' => $label,
                'username' => $username,
                'generatedAt' => now()->format('Y-m-d H:i:s'),
                'dateFrom' => $request->query('date_from'),
                'dateTo' => $request->query('date_to'),
                'rows' => $rows,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filenameBase . '.pdf');
        }

        $headers = ['#', 'Title', 'Status', 'Type', 'SEO Status', 'SEO Score', 'Created By', 'Created Date', 'Updated By', 'Updated Date', 'URL'];
        $callback = function () use ($rows, $headers, $username, $module, $label) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', strtoupper($module) . ' -> ' . strtoupper($label)]);
            fputcsv($handle, ['Generated by', $username]);
            fputcsv($handle, ['Generated at', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);
            fputcsv($handle, $headers);

            $n = 0;
            foreach ($rows as $row) {
                $n++;
                $createdBy = trim((string) ($row['created_by'] ?? ''));
                $updatedBy = trim((string) ($row['updated_by'] ?? ''));
                if ($createdBy === '' || $createdBy === '-' || $createdBy === '—') {
                    $createdBy = 'Admin';
                }
                if ($updatedBy === '' || $updatedBy === '-' || $updatedBy === '—') {
                    $updatedBy = 'Admin';
                }
                fputcsv($handle, [
                    $n,
                    $row['title'] ?? '',
                    $row['status'] ?? '',
                    $row['type'] ?? '',
                    $row['seo_status'] ?? '',
                    $row['seo_score'] ?? '',
                    $createdBy,
                    $row['created_at'] ?? '',
                    $updatedBy,
                    $row['updated_at'] ?? '',
                    $row['url'] ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filenameBase . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function resolveContentReportActorId(Request $request): ?int
    {
        return $this->resolveContentReportActor($request)['id'];
    }

    /**
     * @return array{id: ?int, name: string, key: string}
     */
    private function resolveContentReportActor(Request $request): array
    {
        $role = normalize_panel_role(panel_role_name());

        if ($role === 'content_writer') {
            $id = audit_user_id();

            return [
                'id' => $id,
                'name' => $this->contentReportActorDisplayName((int) ($id ?: 0)),
                'key' => $this->defaultContentReportActorKey(),
            ];
        }

        // Dedicated content-report filter (does not rewrite activity filter user_id).
        if ($request->filled('report_user')) {
            $reportUser = (string) $request->query('report_user');
            if ($reportUser === 'all') {
                return [
                    'id' => null,
                    'name' => 'All Users',
                    'key' => 'all',
                ];
            }

            return $this->parseContentReportActorKey($reportUser);
        }

        // Backward compatible: activity User filter still works for reports.
        if ($request->filled('user_id')) {
            $id = (int) $request->query('user_id');

            return [
                'id' => $id,
                'name' => $this->contentReportActorDisplayName($id, 'user'),
                'key' => 'user:' . $id,
            ];
        }

        // Admin/staff default: All Users (content writers stay scoped above).
        return [
            'id' => null,
            'name' => 'All Users',
            'key' => 'all',
        ];
    }

    private function defaultContentReportActorKey(): string
    {
        if (Auth::guard('admin')->check()) {
            return 'admin:' . Auth::guard('admin')->id();
        }

        $id = Auth::id();

        return $id ? ('user:' . $id) : '';
    }

    /**
     * @return array{id: ?int, name: string, key: string}
     */
    private function parseContentReportActorKey(string $key): array
    {
        if (preg_match('/^(admin|user):(\d+)$/', $key, $m)) {
            $type = $m[1];
            $id = (int) $m[2];

            return [
                'id' => $id,
                'name' => $this->contentReportActorDisplayName($id, $type),
                'key' => $type . ':' . $id,
            ];
        }

        $id = audit_user_id();

        return [
            'id' => $id,
            'name' => $this->contentReportActorDisplayName((int) ($id ?: 0)),
            'key' => $this->defaultContentReportActorKey(),
        ];
    }

    private function contentReportActorDisplayName(int $id, ?string $preferType = null): string
    {
        if ($id <= 0) {
            return 'Me';
        }

        if ($preferType === 'admin' || ($preferType === null && Auth::guard('admin')->check() && (int) Auth::guard('admin')->id() === $id)) {
            $admin = Admin::find($id);
            if ($admin) {
                $name = trim((string) ($admin->username ?: $admin->email ?: 'Admin'));

                return $name !== '' ? $name : 'Admin';
            }
        }

        if ($preferType === 'user' || $preferType === null) {
            $user = User::find($id);
            if ($user) {
                $name = trim((string) ($user->name ?: $user->email ?: 'User'));

                return $name !== '' ? $name : 'User';
            }
        }

        $admin = Admin::find($id);
        if ($admin) {
            return trim((string) ($admin->username ?: $admin->email ?: 'Admin')) ?: 'Admin';
        }

        return 'Admin';
    }

    private function contentReportFilterUsers(): Collection
    {
        $rows = collect([
            [
                'key' => 'all',
                'label' => 'All Users',
            ],
        ]);

        // Live admins table uses username (not name) — never select name.
        Admin::query()->orderBy('username')->get(['id', 'username', 'email'])->each(function (Admin $admin) use ($rows) {
            $label = trim((string) ($admin->username ?: $admin->email ?: 'Admin'));
            $rows->push([
                'key' => 'admin:' . $admin->id,
                'label' => $label . ' (Admin)',
            ]);
        });

        User::query()
            ->with('roles')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', [
                    'content_writer', 'Content Writer', 'librarian', 'content-writer',
                    'instructor', 'Instructor',
                ]);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->each(function (User $user) use ($rows) {
                $role = $user->roles->first()?->name;
                $label = trim((string) ($user->name ?: $user->email ?: 'User'));
                if ($role) {
                    $label .= ' (' . role_display_name($role) . ')';
                }
                $rows->push([
                    'key' => 'user:' . $user->id,
                    'label' => $label,
                ]);
            });

        return $rows;
    }

    public function exportActivity(Request $request)
    {
        @set_time_limit(120);
        @ini_set('memory_limit', '512M');

        $role = normalize_panel_role(panel_role_name());
        $service = app(PanelActivityService::class);
        $logService = app(UserActivityLogService::class);

        $userId = $request->filled('user_id') ? (int) $request->query('user_id') : null;
        $roleFilter = $request->filled('role') ? $request->query('role') : null;
        $today = now()->toDateString();
        $dateFrom = $request->filled('date_from') ? (string) $request->query('date_from') : $today;
        $dateTo = $request->filled('date_to') ? (string) $request->query('date_to') : $today;
        $exportType = $request->query('type', 'staff');
        $includePayments = $role !== 'content_writer';
        $restrictToUserIds = null;
        $paymentsOnly = false;
        $format = strtolower((string) $request->query('format', 'excel'));
        // Unbounded exports previously gateway-timed-out; cap newest rows (CSV more, PDF less).
        $maxRows = $format === 'pdf' ? 1000 : 8000;

        if ($role === 'content_writer') {
            $userId = audit_user_id();
        } elseif ($roleFilter === 'accountant') {
            $paymentsOnly = true;
        } elseif ($roleFilter) {
            $restrictToUserIds = $service->userIdsForRole($roleFilter);
        }

        if ($exportType === 'student') {
            $rows = $logService->buildFeed(
                'student',
                $request->filled('student_user_id') ? (int) $request->query('student_user_id') : null,
                $dateFrom,
                $dateTo,
                $maxRows
            );
            $filename = 'student-activity-history';
            $showUserColumn = true;
        } else {
            $activities = $service->buildFeed(
                $userId,
                $dateFrom,
                $dateTo,
                $includePayments && ! $paymentsOnly,
                $restrictToUserIds,
                $paymentsOnly,
                $maxRows
            );

            $logs = $logService->buildFeed('staff', $userId, $dateFrom, $dateTo, $maxRows);

            if ($restrictToUserIds !== null) {
                $logs = $logs->filter(function (array $row) use ($restrictToUserIds) {
                    return ! empty($row['actor_id']) && in_array((int) $row['actor_id'], $restrictToUserIds, true);
                });
            }

            // Keep legacy panel activities even when logs exist.
            // On some servers role/user filtering can hide log rows (admin_id vs user_id),
            // which previously made "Page Updated" / "Course Updated" disappear entirely.

            $rows = $activities
                ->merge($logs)
                ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
                ->unique(function (array $row) {
                    return implode('|', [
                        $row['action'] ?? '',
                        $row['item'] ?? '',
                        $row['actor_id'] ?? '',
                        $row['occurred_at']?->format('Y-m-d H:i') ?? '',
                    ]);
                })
                ->take($maxRows)
                ->values();

            $filename = 'dashboard-activity-history';
            $showUserColumn = true;
        }

        $username = panel_profile_name() ?: 'User';
        $title = $exportType === 'student' ? 'Student activity history' : 'Staff & panel user activity';
        $fileStamp = $filename . '_' . now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.dashboard.activity-pdf', [
                'title' => $title,
                'username' => $username,
                'generatedAt' => now()->format('Y-m-d H:i:s'),
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'rows' => $rows,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($fileStamp . '.pdf');
        }

        $headers = ['Date & Time', 'User', 'Activity', 'Description', 'Session', 'URL'];
        $callback = function () use ($rows, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $activity) {
                fputcsv($handle, [
                    $activity['occurred_at']->format('Y-m-d H:i:s'),
                    $activity['user_name'] ?? '—',
                    activity_action_label($activity['action'] ?? ''),
                    $activity['item'] ?? '',
                    $activity['session_id'] ?? '',
                    $activity['url'] ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileStamp . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function profile()
    {
        if (!panel_profile_user()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::guard('admin')->check() ? null : Auth::user();
        if ($user && $user->roles()->where('name', 'instructor')->exists()) {
            $user->loadMissing('roles');
            return view('admin.profile', [
                'showInstructorForm' => true,
                'user' => $user,
                'countries' => Country::all(),
            ]);
        }

        return view('admin.profile', ['showInstructorForm' => false]);
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

        $mailError = send_user_profile_update_email($user->fresh());
        $successMessage = 'Profile updated successfully!';
        if ($mailError) {
            $successMessage .= ' However, there was an issue sending the email: ' . $mailError;
        }

        return redirect()->back()->with('success', $successMessage);
    }

    public function logout(Request $request){
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            record_user_activity(
                'Admin Log out',
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
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(public_login_url());
    }
}
