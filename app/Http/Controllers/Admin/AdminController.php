<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Admin;
use App\Models\User;
use App\Models\Country;
use App\Services\AccountantDashboardService;
use App\Services\ContentDashboardReportService;
use App\Services\InstructorsDashboardService;
use App\Services\ActivityGapCommentService;
use App\Services\PanelActivityService;
use App\Services\StudentsDashboardService;
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
                'showSessionColumn' => false,
                'showContentReports' => true,
                'showContentReportUserFilter' => false,
                'contentReportActorId' => audit_user_id(),
                'logAudience' => 'staff',
                'activityTitle' => $this->namedActivityTitle(panel_profile_name() ?: 'User'),
            ]);
        }

        if ($role === 'accountant') {
            return redirect()->route('admin.dashboard.accountant');
        }

        // Main Dashboard removed — Admin / Musa land on Staff Dashboard.
        if (panel_sees_all_dashboards()) {
            return redirect()->route('admin.dashboard.content-writers', $request->query());
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
            'showSessionColumn' => false,
            'showContentReports' => true,
            'showContentReportUserFilter' => true,
            'contentReportActorId' => $reportActor['id'],
            'contentReportActorName' => $reportActor['name'],
            'contentReportActorKey' => $reportActor['key'],
            'logAudience' => 'staff',
            'activityTitle' => $this->adminActivityTitle($request),
            'studentActivityTitle' => $this->studentActivityTitle($request),
            'panelBaseUrl' => route('admin.home'),
        ]);
    }

    public function contentWritersDashboard(Request $request)
    {
        $this->ensureDashboardAccess(['admin', 'content_writer']);

        $role = normalize_panel_role(panel_role_name());
        $isContentWriter = $role === 'content_writer';
        $isAdminView = panel_sees_all_dashboards();
        $baseUrl = route('admin.dashboard.content-writers');

        if ($isContentWriter && ! $isAdminView) {
            return $this->activityDashboard($request, [
                'userId' => audit_user_id(),
                'showMyStats' => true,
                'showSiteStats' => true,
                'includePayments' => false,
                'showUserColumn' => true,
                'showUserFilter' => false,
                'showSessionColumn' => false,
                'showContentReports' => true,
                'showContentReportUserFilter' => false,
                'contentReportActorId' => audit_user_id(),
                'logAudience' => 'staff',
                'activityTitle' => $this->namedActivityTitle(panel_profile_name() ?: 'Content Writer'),
                'panelBaseUrl' => $baseUrl,
                'pageTitle' => 'Staff Dashboard',
            ]);
        }

        // Admin / Musa viewing Staff Dashboard (site-wide content activity + reports).
        $reportActor = $this->resolveContentReportActor($request);

        return $this->activityDashboard($request, [
            'userId' => $request->filled('user_id') ? (int) $request->query('user_id') : null,
            'roleFilter' => $request->filled('role') ? $request->query('role') : 'content_writer',
            'showMyStats' => false,
            'showSiteStats' => true,
            'showInvoiceStats' => false,
            'includePayments' => false,
            'showUserColumn' => true,
            'showUserFilter' => $isAdminView,
            'showStudentTable' => false,
            'showSessionColumn' => false,
            'showContentReports' => true,
            'showContentReportUserFilter' => $isAdminView,
            'contentReportActorId' => $reportActor['id'],
            'contentReportActorName' => $reportActor['name'],
            'contentReportActorKey' => $reportActor['key'],
            'logAudience' => 'staff',
            'activityTitle' => $this->namedActivityTitle('Staff'),
            'userFilterEmptyLabel' => 'All content writers',
            'panelBaseUrl' => $baseUrl,
            'pageTitle' => 'Staff Dashboard',
        ]);
    }

    public function instructorsDashboard(Request $request)
    {
        $this->ensureDashboardAccess(['admin']);

        $baseUrl = route('admin.dashboard.instructors');
        // Custom range has no default dates — only applied when both are supplied (popup search).
        $overviewFrom = $request->filled('overview_date_from')
            ? (string) $request->query('overview_date_from')
            : null;
        $overviewTo = $request->filled('overview_date_to')
            ? (string) $request->query('overview_date_to')
            : null;
        $instructorService = app(InstructorsDashboardService::class);
        $instructorStats = $instructorService->overviewStats($overviewFrom, $overviewTo);
        $instructorDetails = $instructorService->overviewDetails($overviewFrom, $overviewTo);

        return $this->activityDashboard($request, [
            'userId' => $request->filled('user_id') ? (int) $request->query('user_id') : null,
            'roleFilter' => $request->filled('role') ? $request->query('role') : 'instructor',
            'showMyStats' => false,
            'showSiteStats' => false,
            'showInvoiceStats' => false,
            'includePayments' => false,
            'showUserColumn' => true,
            'showUserFilter' => true,
            'showStudentTable' => false,
            'showSessionColumn' => false,
            'showContentReports' => false,
            'showInstructorOverview' => true,
            'instructorOverviewStats' => $instructorStats,
            'instructorOverviewDetails' => $instructorDetails,
            'instructorOverviewDateFrom' => $overviewFrom,
            'instructorOverviewDateTo' => $overviewTo,
            'logAudience' => 'staff',
            'activityTitle' => $this->namedActivityTitle('Instructors'),
            'panelBaseUrl' => $baseUrl,
            'pageTitle' => 'Instructors Dashboard',
        ]);
    }

    public function instructorsPeople(Request $request)
    {
        $this->ensureDashboardAccess(['admin']);

        $from = $request->filled('date_from') ? (string) $request->query('date_from') : null;
        $to = $request->filled('date_to') ? (string) $request->query('date_to') : null;

        return response()->json(
            app(InstructorsDashboardService::class)->customRangePeople($from, $to)
        );
    }

    public function exportInstructorsPeople(Request $request)
    {
        $this->ensureDashboardAccess(['admin']);

        return $this->exportPeopleMetric(
            $request,
            app(InstructorsDashboardService::class),
            'instructors'
        );
    }

    public function studentsDashboard(Request $request)
    {
        $this->ensureDashboardAccess(['admin']);

        $baseUrl = route('admin.dashboard.students');
        $studentService = app(StudentsDashboardService::class);
        $studentStats = $studentService->overviewStats();
        $studentDetails = $studentService->overviewDetails();

        return $this->activityDashboard($request, [
            'userId' => null,
            'roleFilter' => null,
            'showMyStats' => false,
            'showSiteStats' => false,
            'showInvoiceStats' => false,
            'includePayments' => false,
            'showUserColumn' => true,
            'showUserFilter' => false,
            'showStudentTable' => true,
            'studentActivitiesOnly' => true,
            'showSessionColumn' => false,
            'showContentReports' => false,
            'showStudentOverview' => true,
            'studentOverviewStats' => $studentStats,
            'studentOverviewDetails' => $studentDetails,
            'logAudience' => 'student',
            'activityTitle' => $this->namedActivityTitle('Students'),
            'studentActivityTitle' => $this->namedActivityTitle('Students'),
            'panelBaseUrl' => $baseUrl,
            'pageTitle' => 'Students Dashboard',
        ]);
    }

    public function studentsPeople(Request $request)
    {
        $this->ensureDashboardAccess(['admin']);

        $from = $request->filled('date_from') ? (string) $request->query('date_from') : null;
        $to = $request->filled('date_to') ? (string) $request->query('date_to') : null;

        return response()->json(
            app(StudentsDashboardService::class)->customRangePeople($from, $to)
        );
    }

    public function exportStudentsPeople(Request $request)
    {
        $this->ensureDashboardAccess(['admin']);

        return $this->exportPeopleMetric(
            $request,
            app(StudentsDashboardService::class),
            'students'
        );
    }

    /**
     * @param  InstructorsDashboardService|StudentsDashboardService  $service
     */
    private function exportPeopleMetric(Request $request, $service, string $audience)
    {
        @set_time_limit(120);
        $metric = (string) $request->query('metric', 'reg_total');
        $from = $request->filled('date_from') ? (string) $request->query('date_from') : null;
        $to = $request->filled('date_to') ? (string) $request->query('date_to') : null;
        $format = strtolower((string) $request->query('format', 'excel'));
        $payload = $service->peopleForMetric($metric, $from, $to);
        $title = (string) ($payload['title'] ?? 'People');
        $people = $payload['people'] ?? [];
        $username = panel_profile_name() ?: 'User';
        $fileStamp = $audience . '-people_' . now()->format('Y-m-d');
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'gender', 'label' => 'Gender'],
            ['key' => 'country', 'label' => 'Country'],
            ['key' => 'city', 'label' => 'City'],
            ['key' => 'nationality', 'label' => 'Nationality'],
            ['key' => 'registered_at', 'label' => 'Registered Date'],
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.dashboard.report-pdf', [
                'module' => strtoupper($audience),
                'label' => $title,
                'username' => $username,
                'generatedAt' => now()->format('Y-m-d H:i:s'),
                'dateFrom' => $payload['date_from'] ?? $from,
                'dateTo' => $payload['date_to'] ?? $to,
                'rows' => $people,
                'columns' => $columns,
                'linkKey' => 'name',
            ])->setPaper('a4', 'landscape');

            return $pdf->download($fileStamp . '.pdf');
        }

        $callback = function () use ($people, $columns, $title, $username, $payload, $from, $to) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', $title]);
            fputcsv($handle, ['Generated by', $username]);
            fputcsv($handle, [
                'Date range',
                (($payload['date_from'] ?? $from) ?: '—') . ' - ' . (($payload['date_to'] ?? $to) ?: '—'),
            ]);
            fputcsv($handle, []);
            fputcsv($handle, array_merge(['#'], array_column($columns, 'label')));
            foreach ($people as $index => $person) {
                $line = [$index + 1];
                foreach ($columns as $col) {
                    $line[] = $person[$col['key']] ?? '—';
                }
                fputcsv($handle, $line);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $fileStamp . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function accountantDashboard(Request $request)
    {
        $this->ensureDashboardAccess(['admin', 'accountant']);

        $tab = (string) $request->query('tab', 'overview');
        if (! in_array($tab, ['overview', 'this_month', 'last_month', 'this_year', 'custom'], true)) {
            $tab = 'overview';
        }

        $dateFrom = $request->filled('date_from') ? (string) $request->query('date_from') : now()->startOfMonth()->toDateString();
        $dateTo = $request->filled('date_to') ? (string) $request->query('date_to') : now()->toDateString();

        $service = app(AccountantDashboardService::class);
        $stats = $service->overviewStats();
        [$from, $to] = $service->rangeForTab($tab, $dateFrom, $dateTo);
        $rows = $tab === 'overview' ? collect() : $service->salesRows($from, $to);
        $salesTotalAed = $tab === 'overview'
            ? 0.0
            : round((float) $rows->sum('amount_aed'), 2);

        $activityFrom = $request->filled('activity_date_from')
            ? (string) $request->query('activity_date_from')
            : now()->toDateString();
        $activityTo = $request->filled('activity_date_to')
            ? (string) $request->query('activity_date_to')
            : now()->toDateString();

        $panelActivity = app(PanelActivityService::class);
        $accountantUserIds = $panelActivity->userIdsForRole('accountant') ?? [];
        $activities = $panelActivity->paginatedFeedWithLogs(
            null,
            $activityFrom,
            $activityTo,
            true,
            'staff',
            15,
            (int) $request->query('page', 1),
            $request->url(),
            $request->query(),
            $accountantUserIds,
            true
        );

        return view('admin.dashboard.accountant', [
            'activeTab' => $tab,
            'stats' => $stats,
            'rows' => $rows,
            'salesTotalAed' => $salesTotalAed,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'activities' => $activities,
            'activityDateFrom' => $activityFrom,
            'activityDateTo' => $activityTo,
            'activityTitle' => $this->namedActivityTitle('Accountant'),
        ]);
    }

    public function exportAccountant(Request $request)
    {
        $this->ensureDashboardAccess(['admin', 'accountant']);
        @set_time_limit(120);

        $format = strtolower((string) $request->query('format', 'excel'));
        $scope = (string) $request->query('scope', 'sales');
        $tab = (string) $request->query('tab', 'this_month');
        if (! in_array($tab, ['overview', 'this_month', 'last_month', 'this_year', 'custom'], true)) {
            $tab = 'this_month';
        }

        $dateFrom = $request->filled('date_from') ? (string) $request->query('date_from') : now()->startOfMonth()->toDateString();
        $dateTo = $request->filled('date_to') ? (string) $request->query('date_to') : now()->toDateString();
        $activityFrom = $request->filled('activity_date_from')
            ? (string) $request->query('activity_date_from')
            : now()->toDateString();
        $activityTo = $request->filled('activity_date_to')
            ? (string) $request->query('activity_date_to')
            : now()->toDateString();

        $username = panel_profile_name() ?: 'User';
        $service = app(AccountantDashboardService::class);

        if ($scope === 'activity') {
            $panelActivity = app(PanelActivityService::class);
            $accountantUserIds = $panelActivity->userIdsForRole('accountant') ?? [];
            $rows = $panelActivity->paginatedFeedWithLogs(
                null,
                $activityFrom,
                $activityTo,
                true,
                'staff',
                5000,
                1,
                $request->url(),
                $request->query(),
                $accountantUserIds,
                true
            )->getCollection();

            $title = 'Accountant Activity Report';
            $fileStamp = 'accountant-activity_' . now()->format('Y-m-d');
            $headers = ['Date & Time', 'Name', 'Contact Number', 'Activity', 'Details', 'URL'];

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('admin.dashboard.activity-pdf', [
                    'title' => $title,
                    'username' => $username,
                    'generatedAt' => now()->format('Y-m-d H:i:s'),
                    'dateFrom' => $activityFrom,
                    'dateTo' => $activityTo,
                    'rows' => $rows,
                ])->setPaper('a4', 'landscape');

                return $pdf->download($fileStamp . '.pdf');
            }

            $callback = function () use ($rows, $headers, $title, $activityFrom, $activityTo, $username) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Report', $title]);
                fputcsv($handle, ['Report for', $activityFrom . ' - ' . $activityTo]);
                fputcsv($handle, ['Generated by', $username]);
                fputcsv($handle, []);
                fputcsv($handle, $headers);
                foreach ($rows as $activity) {
                    fputcsv($handle, [
                        ! empty($activity['occurred_at']) && is_object($activity['occurred_at'])
                            ? $activity['occurred_at']->format('Y-m-d H:i:s')
                            : '',
                        $activity['user_name'] ?? '—',
                        $activity['contact_number'] ?? '—',
                        activity_action_label($activity['action'] ?? ''),
                        $activity['item'] ?? '',
                        $activity['url'] ?? '',
                    ]);
                }
                fclose($handle);
            };

            return response()->streamDownload($callback, $fileStamp . '.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        // Overview export = summary stats; sales tabs = paid sales rows.
        if ($tab === 'overview' || $scope === 'overview') {
            $stats = $service->overviewStats();
            $title = 'Accountant Overview';
            $fileStamp = 'accountant-overview_' . now()->format('Y-m-d');
            $statRows = [
                ['All Invoices', $stats['invoice_total'] ?? 0],
                ['Unpaid Invoices', $stats['invoice_unpaid'] ?? 0],
                ['Partially Paid Invoices', $stats['invoice_partial'] ?? 0],
                ['All Receipts', $stats['receipts_total'] ?? 0],
                ['This Month Sales (AED)', number_format((float) ($stats['sales_this_month'] ?? 0), 2, '.', '')],
                ['Last Month Sales (AED)', number_format((float) ($stats['sales_last_month'] ?? 0), 2, '.', '')],
                ['This Year Sales (AED)', number_format((float) ($stats['sales_this_year'] ?? 0), 2, '.', '')],
            ];

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('admin.dashboard.report-pdf', [
                    'module' => 'Accountant',
                    'label' => $title,
                    'username' => $username,
                    'generatedAt' => now()->format('Y-m-d H:i:s'),
                    'dateFrom' => null,
                    'dateTo' => null,
                    'columns' => [
                        ['key' => 'metric', 'label' => 'Metric'],
                        ['key' => 'value', 'label' => 'Value'],
                    ],
                    'linkKey' => 'metric',
                    'rows' => collect($statRows)->map(fn ($r) => [
                        'metric' => $r[0],
                        'value' => $r[1],
                        'url' => '',
                    ])->all(),
                ])->setPaper('a4', 'portrait');

                return $pdf->download($fileStamp . '.pdf');
            }

            $callback = function () use ($statRows, $title, $username) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Report', $title]);
                fputcsv($handle, ['Generated by', $username]);
                fputcsv($handle, ['Generated at', now()->format('Y-m-d H:i:s')]);
                fputcsv($handle, []);
                fputcsv($handle, ['Metric', 'Value']);
                foreach ($statRows as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            };

            return response()->streamDownload($callback, $fileStamp . '.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        [$from, $to] = $service->rangeForTab($tab, $dateFrom, $dateTo);
        $rows = $service->salesRows($from, $to);
        $salesTotalAed = round((float) $rows->sum('amount_aed'), 2);
        $tabLabel = match ($tab) {
            'this_month' => 'This Month Sales',
            'last_month' => 'Last Month Sales',
            'this_year' => 'This Year Sales',
            'custom' => 'Custom Date Sales',
            default => 'Sales',
        };
        $title = 'Accountant - ' . $tabLabel;
        $fileStamp = 'accountant-sales-' . $tab . '_' . now()->format('Y-m-d');
        $rangeFrom = $from?->toDateString() ?: $dateFrom;
        $rangeTo = $to?->toDateString() ?: $dateTo;

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.dashboard.report-pdf', [
                'module' => 'Accountant',
                'label' => $tabLabel,
                'username' => $username,
                'generatedAt' => now()->format('Y-m-d H:i:s'),
                'dateFrom' => $rangeFrom,
                'dateTo' => $rangeTo,
                'columns' => [
                    ['key' => 'receipt_date', 'label' => 'Receipt Date'],
                    ['key' => 'receipt_no', 'label' => 'Receipt Number'],
                    ['key' => 'invoice_no', 'label' => 'Invoice No#'],
                    ['key' => 'student', 'label' => 'Student'],
                    ['key' => 'course', 'label' => 'Course'],
                    ['key' => 'package', 'label' => 'Package'],
                    ['key' => 'amount', 'label' => 'Amount (Paid)'],
                    ['key' => 'installments', 'label' => 'Installment'],
                    ['key' => 'status', 'label' => 'Payment Status'],
                ],
                'linkKey' => 'student',
                'rows' => $rows->all(),
            ])->setPaper('a4', 'landscape');

            return $pdf->download($fileStamp . '.pdf');
        }

        $callback = function () use ($rows, $title, $username, $rangeFrom, $rangeTo, $salesTotalAed) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', $title]);
            fputcsv($handle, ['Report for', $rangeFrom . ' - ' . $rangeTo]);
            fputcsv($handle, ['Generated by', $username]);
            fputcsv($handle, ['Generated at', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);
            fputcsv($handle, ['#', 'Receipt Date', 'Receipt Number', 'Invoice No#', 'Student', 'Course', 'Package', 'Amount (Paid)', 'Installment', 'Payment Status', 'URL']);
            $n = 0;
            foreach ($rows as $row) {
                $n++;
                fputcsv($handle, [
                    $n,
                    $row['receipt_date'] ?? ($row['created_at'] ?? ''),
                    $row['receipt_no'] ?? '',
                    $row['invoice_no'] ?? '',
                    $row['student'] ?? '',
                    $row['course'] ?? '',
                    $row['package'] ?? '',
                    $row['amount'] ?? '',
                    $row['installments'] ?? '',
                    $row['status'] ?? '',
                    $row['url'] ?? '',
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Total AED', number_format($salesTotalAed, 2, '.', '')]);
            fclose($handle);
        };

        return response()->streamDownload($callback, $fileStamp . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function ensureDashboardAccess(array $allowedRoles): void
    {
        if (panel_sees_all_dashboards()) {
            return;
        }

        $role = normalize_panel_role(panel_role_name());
        if ($role === null || $role === 'admin') {
            return;
        }
        if (! in_array($role, $allowedRoles, true)) {
            abort(403);
        }
    }

    private function namedActivityTitle(string $name): string
    {
        $name = trim($name) !== '' ? trim($name) : 'User';

        return $name . ' - ACTIVITY REPORT';
    }

    private function adminActivityTitle(Request $request): string
    {
        if ($request->filled('user_id')) {
            $user = User::find($request->query('user_id'));

            return $this->namedActivityTitle($user?->name ?? $user?->email ?? 'User');
        }

        return match ($request->query('role')) {
            'content_writer' => $this->namedActivityTitle('Staff'),
            'accountant' => $this->namedActivityTitle('Accountants'),
            'instructor' => $this->namedActivityTitle('Instructors'),
            default => $this->namedActivityTitle('Staff & Panel Users'),
        };
    }

    private function studentActivityTitle(Request $request): string
    {
        if ($request->filled('student_user_id')) {
            $user = User::find($request->query('student_user_id'));

            return $this->namedActivityTitle($user?->name ?? $user?->email ?? 'Student');
        }

        return $this->namedActivityTitle('Students');
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
        // null actor id means All Users — must not fall through ?? to the logged-in admin.
        $contentReportActorId = array_key_exists('contentReportActorId', $options)
            ? $options['contentReportActorId']
            : audit_user_id();
        $contentReportActorName = array_key_exists('contentReportActorName', $options)
            ? (string) $options['contentReportActorName']
            : $this->contentReportActorDisplayName((int) ($contentReportActorId ?: 0));
        $contentReportActorKey = array_key_exists('contentReportActorKey', $options)
            ? (string) $options['contentReportActorKey']
            : $this->defaultContentReportActorKey();
        $showContentReportUserFilter = (bool) ($options['showContentReportUserFilter'] ?? false);
        $contentReports = null;
        $contentReportMetricLabels = [
            'pages' => [],
            'courses' => [],
            'seo' => [],
            'training' => [],
            'fees' => [],
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
                'training' => $reportService->metricLabels('training', $contentReportActorName),
                'fees' => $reportService->metricLabels('fees', $contentReportActorName),
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
            'showStudentOverview' => (bool) ($options['showStudentOverview'] ?? false),
            'studentOverviewStats' => $options['studentOverviewStats'] ?? [],
            'studentOverviewDetails' => $options['studentOverviewDetails'] ?? [],
            'showInstructorOverview' => (bool) ($options['showInstructorOverview'] ?? false),
            'instructorOverviewStats' => $options['instructorOverviewStats'] ?? [],
            'instructorOverviewDetails' => $options['instructorOverviewDetails'] ?? [],
            'instructorOverviewDateFrom' => $options['instructorOverviewDateFrom'] ?? null,
            'instructorOverviewDateTo' => $options['instructorOverviewDateTo'] ?? null,
            'studentActivitiesOnly' => (bool) ($options['studentActivitiesOnly'] ?? false),
            'contentReports' => $contentReports,
            'contentReportActorId' => $contentReportActorId,
            'contentReportActorName' => $contentReportActorName,
            'contentReportActorKey' => $contentReportActorKey,
            'contentReportMetricLabels' => $contentReportMetricLabels,
            'contentReportUsers' => $showContentReportUserFilter ? $this->contentReportFilterUsers() : collect(),
            'filterUsers' => ($options['showUserFilter'] ?? false)
                ? $service->filterUsers(is_string($roleFilter) ? $roleFilter : null)
                : collect(),
            'userFilterEmptyLabel' => $options['userFilterEmptyLabel']
                ?? (($roleFilter === 'content_writer') ? 'All content writers' : 'None'),
            'activityRoleFilter' => is_string($roleFilter) ? $roleFilter : null,
            'studentFilterUsers' => $showStudentTable ? $logService->studentFilterUsers() : collect(),
            'activityTitle' => $options['activityTitle'] ?? 'Activity history',
            'studentActivityTitle' => $options['studentActivityTitle'] ?? 'Student activity history',
            'activityDateFrom' => $dateFrom,
            'activityDateTo' => $dateTo,
            'panelBaseUrl' => $options['panelBaseUrl'] ?? route('admin.home'),
            'pageTitle' => $options['pageTitle'] ?? 'Dashboard',
            'activityGapComments' => $this->activityGapCommentsFor($activities, $studentActivities),
            'activityTimezone' => config('app.timezone'),
            'activityTimezoneLabel' => now()->timezone(config('app.timezone'))->format('T P'),
        ]);
    }

    protected function activityGapCommentsFor($staffRows, $studentRows): array
    {
        $svc = app(ActivityGapCommentService::class);

        return $svc->commentsForFeed('staff', $staffRows)
            + $svc->commentsForFeed('student', $studentRows ?? []);
    }

    public function saveActivityGapComment(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|in:staff,student',
            'from_ts' => 'required|integer',
            'to_ts' => 'required|integer',
            'comment' => 'required|string|max:500',
        ]);
        app(ActivityGapCommentService::class)->save(
            $validated['scope'],
            (int) $validated['from_ts'],
            (int) $validated['to_ts'],
            trim($validated['comment'])
        );

        return back()->with('success', 'Gap comment saved.');
    }

    public function contentReportDetails(Request $request)
    {
        $module = (string) $request->query('module', 'pages');
        $metric = (string) $request->query('metric', 'total');
        $actor = $this->resolveContentReportActor($request);
        $actorId = $actor['id'];
        $service = app(ContentDashboardReportService::class);
        $labels = $service->metricLabels($module, $actor['name']);
        $label = $labels[$metric] ?? ucfirst(str_replace('_', ' ', $metric));
        $detailAction = 'Opened content report';
        if ($request->filled('q')) {
            $detailAction = 'Searched content report';
        } elseif (in_array($metric, ['created_in_range', 'updated_in_range'], true)
            && ($request->filled('date_from') || $request->filled('date_to'))) {
            $detailAction = 'Applied content report date range';
        }
        $this->logContentReportActivity(
            $detailAction,
            $this->contentReportModuleTitle($module) . ' -> ' . strtoupper($label),
            $request
        );
        $rows = $service->detailRows(
            $module,
            $metric,
            $actorId,
            $request->query('date_from'),
            $request->query('date_to'),
            $request->query('q')
        );

        return response()->json([
            'module' => $module,
            'metric' => $metric,
            'label' => $label,
            'columns' => $service->detailColumns($module, $metric),
            'count' => $rows->count(),
            'rows' => $rows->values(),
        ]);
    }

    public function contentReportActivity(Request $request)
    {
        $action = trim((string) $request->input('action', 'Opened content report'));
        $item = trim((string) $request->input('item', 'Website Content Reports'));
        $allowed = [
            'Opened content report tab',
            'Filtered content report user',
            'Opened content report',
            'Searched content report',
            'Applied content report date range',
        ];
        if (! in_array($action, $allowed, true)) {
            $action = 'Opened content report';
        }
        if ($item === '') {
            $item = 'Website Content Reports';
        }

        $this->logContentReportActivity($action, $item, $request);

        return response()->json(['ok' => true]);
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
        $this->logContentReportActivity(
            'Exported content report ' . strtoupper($format === 'pdf' ? 'PDF' : 'Excel'),
            $this->contentReportModuleTitle($module) . ' -> ' . strtoupper($label),
            $request
        );
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
        $columns = $service->detailColumns($module, $metric);
        $linkCol = collect($columns)->first(fn ($col) => in_array($col['key'], ['package_name', 'course', 'title'], true));
        $linkKey = is_array($linkCol) ? ($linkCol['key'] ?? 'title') : 'title';

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.dashboard.report-pdf', [
                'module' => $this->contentReportModuleTitle($module),
                'label' => $label,
                'username' => $username,
                'generatedAt' => now()->format('Y-m-d H:i:s'),
                'dateFrom' => $request->query('date_from'),
                'dateTo' => $request->query('date_to'),
                'columns' => $columns,
                'linkKey' => $linkKey,
                'rows' => $rows,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filenameBase . '.pdf');
        }

        $headers = array_merge(['#'], array_column($columns, 'label'), ['Admin URL', 'Visit Web']);
        $callback = function () use ($rows, $headers, $username, $module, $label, $columns, $linkKey) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', $this->contentReportModuleTitle($module) . ' -> ' . strtoupper($label)]);
            fputcsv($handle, ['Generated by', $username]);
            fputcsv($handle, ['Generated at', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);
            fputcsv($handle, $headers);

            $n = 0;
            foreach ($rows as $row) {
                $n++;
                $line = [$n];
                foreach ($columns as $col) {
                    $key = $col['key'];
                    $value = (string) ($row[$key] ?? '');
                    if (in_array($key, ['created_by', 'updated_by'], true)) {
                        $trimmed = trim($value);
                        if ($trimmed === '' || $trimmed === '-' || $trimmed === '—') {
                            $value = 'Admin';
                        }
                    }
                    if ($key === $linkKey) {
                        $url = trim((string) ($row['url'] ?? ''));
                        if ($url !== '' && $value !== '') {
                            $safeTitle = str_replace('"', '""', $value);
                            $safeUrl = str_replace('"', '""', $url);
                            $value = '=HYPERLINK("' . $safeUrl . '","' . $safeTitle . '")';
                        }
                    }
                    $line[] = $value;
                }
                $line[] = $row['url'] ?? '';
                $line[] = $row['web_url'] ?? '';
                fputcsv($handle, $line);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filenameBase . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function contentReportModuleTitle(string $module): string
    {
        return match (strtolower($module)) {
            'courses' => 'COURSES',
            'pages' => 'PAGES',
            'seo' => 'SEO ACTIVE',
            'training' => 'TRAINING CALENDAR',
            'fees' => 'FEE PACKAGE',
            default => strtoupper($module),
        };
    }

    private function logContentReportActivity(string $action, string $item, Request $request): void
    {
        try {
            $item = trim($item);
            if ($item !== '' && ! str_starts_with($item, 'Website Content Reports')) {
                $item = 'Website Content Reports → ' . $item;
            }

            $upper = strtoupper($item);
            // Training Calendar report clicks should open admin Training Calendar (Edit Page).
            // Visit Page (public agenda) is inferred when the activity feed is rendered.
            if (str_contains($upper, 'TRAINING CALENDAR')) {
                $url = route('admin.course-agendas.index');
            } else {
                $url = route('admin.dashboard.content-writers', array_filter([
                    'report_user' => $request->input('report_user', $request->query('report_user')),
                    'role' => 'content_writer',
                ], fn ($v) => $v !== null && $v !== ''));
            }

            record_panel_activity(
                $action,
                $item !== '' ? $item : 'Website Content Reports',
                $url,
                $request
            );
        } catch (\Throwable $e) {
            // Activity logging must never break report browsing.
        }
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

            // Prefer detailed log rows ("Title — Banner") over bare DB update rows.
            $rows = collect($logs->values()->all())
                ->concat(collect($activities->values()->all()))
                ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
                ->groupBy(function (array $row) {
                    $action = strtolower(trim((string) ($row['action'] ?? '')));
                    $item = trim((string) ($row['item'] ?? ''));
                    if (preg_match('/\b(updated|created)\b/i', $action)) {
                        $item = trim((string) preg_replace('/\s+[—–-]\s+.+$/u', '', $item));
                    }

                    return implode('|', [
                        $action,
                        strtolower($item),
                        (string) ($row['actor_id'] ?? ''),
                        $row['occurred_at']?->format('Y-m-d H:i') ?? '',
                    ]);
                })
                ->map(function ($group) {
                    $group = collect($group);
                    $preferred = $group
                        ->sortByDesc(fn (array $row) => strlen((string) ($row['item'] ?? '')))
                        ->first();
                    foreach ($group as $row) {
                        if (empty($preferred['visit_url']) && ! empty($row['visit_url'])) {
                            $preferred['visit_url'] = $row['visit_url'];
                        }
                        if (empty($preferred['edit_url']) && ! empty($row['edit_url'])) {
                            $preferred['edit_url'] = $row['edit_url'];
                        }
                        if (empty($preferred['url']) && ! empty($row['url'])) {
                            $preferred['url'] = $row['url'];
                        }
                    }

                    return $preferred;
                })
                ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
                ->take($maxRows)
                ->values();

            $filename = 'dashboard-activity-history';
            $showUserColumn = true;
        }

        $username = panel_profile_name() ?: 'User';
        if ($exportType === 'student') {
            $title = $this->studentActivityTitle($request);
        } else {
            $role = normalize_panel_role(panel_role_name());
            $title = match (true) {
                $role === 'content_writer' => $this->namedActivityTitle($username),
                $role === 'accountant' => $this->namedActivityTitle('Accountants'),
                default => $this->adminActivityTitle($request),
            };
        }
        $fileStamp = $filename . '_' . now()->format('Y-m-d');

        // Exports: morning → evening (ascending), time without seconds (hh:mm AM/PM).
        $rows = collect($rows)
            ->sortBy(fn (array $row) => optional($row['occurred_at'] ?? null)?->timestamp ?? 0)
            ->values();

        $exportScope = $exportType === 'student' ? 'student' : 'staff';
        $gapComments = app(ActivityGapCommentService::class)->commentsForFeed($exportScope, $rows);
        $tzName = config('app.timezone');
        $tzLabel = now()->timezone($tzName)->format('T P');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.dashboard.activity-pdf', [
                'title' => $title,
                'username' => $username,
                'generatedAt' => now()->format('M j, Y g:i A'),
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'rows' => $rows,
                'gapMinutes' => 10,
                'activityGapComments' => $gapComments,
                'exportScope' => $exportScope,
                'activityTimezone' => $tzName,
                'activityTimezoneLabel' => $tzLabel,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($fileStamp . '.pdf');
        }

        $headers = ['Date & Time', 'User', 'Activity', 'Description', 'Visit Page', 'Edit Page'];
        $callback = function () use ($rows, $headers, $title, $dateFrom, $dateTo, $username, $gapComments, $exportScope, $tzName, $tzLabel) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', $title]);
            fputcsv($handle, ['Report for', ($dateFrom ?: '—') . ' - ' . ($dateTo ?: '—')]);
            fputcsv($handle, ['Time zone', $tzName . ' (' . $tzLabel . ')']);
            fputcsv($handle, ['Generated by', $username]);
            fputcsv($handle, []);
            fputcsv($handle, $headers);

            $prevTs = null;
            foreach ($rows as $activity) {
                $visitUrl = $activity['visit_url'] ?? '';
                $editUrl = $activity['edit_url'] ?? '';
                $fallbackUrl = $activity['url'] ?? '';
                if ($editUrl === '' && $fallbackUrl !== '' && (
                    str_contains($fallbackUrl, '/course/')
                    || str_contains($fallbackUrl, '/pages/')
                    || str_contains($fallbackUrl, '/courses-pages-seo/')
                    || str_contains($fallbackUrl, '/seo/')
                    || str_contains($fallbackUrl, '/training-calendar/')
                    || str_contains($fallbackUrl, '/course-agendas/')
                    || str_contains($fallbackUrl, '/fee/')
                )) {
                    $editUrl = $fallbackUrl;
                }

                $ts = optional($activity['occurred_at'] ?? null)?->timestamp;
                if ($prevTs !== null && $ts !== null && abs($ts - $prevTs) > 10 * 60) {
                    $gk = app(ActivityGapCommentService::class)->key($exportScope, (int) $prevTs, (int) $ts);
                    $gapNote = $gapComments[$gk] ?? '';
                    fputcsv($handle, ['---------- 10+ min gap ----------' . ($gapNote !== '' ? (' — ' . $gapNote) : ''), '', '', '', '', '']);
                }
                if ($ts !== null) {
                    $prevTs = $ts;
                }

                $when = optional($activity['occurred_at'] ?? null);
                fputcsv($handle, [
                    $when ? $when->format('M j, Y g:i A') : '—',
                    $activity['user_name'] ?? '—',
                    activity_action_label($activity['action'] ?? ''),
                    $activity['item'] ?? '',
                    $visitUrl !== '' ? $visitUrl : '—',
                    $editUrl !== '' ? $editUrl : '—',
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
