<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\UserActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    public function index(Request $request, UserActivityLogService $logs)
    {
        $seeAll = $this->canSeeAllUsersHistory();

        if (! $logs->tableExists()) {
            $activities = new LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url()]);
            $filterUsers = collect();

            return view('user.history.index', compact('activities', 'seeAll', 'filterUsers'));
        }

        $query = UserActivityLog::query()
            ->with(['user', 'admin'])
            ->orderByDesc('created_at');

        if (! $seeAll) {
            $query->where('user_id', Auth::id());
        } elseif ($request->filled('user_id')) {
            $uid = (int) $request->input('user_id');
            $query->where(function ($q) use ($uid) {
                $q->where('user_id', $uid)->orWhere('admin_id', $uid);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $query->whereRaw("NOT (
            (LOWER(COALESCE(action,'')) LIKE '%profile%' OR LOWER(COALESCE(item,'')) LIKE '%profile%')
            AND (
                LOWER(CONCAT(COALESCE(action,''),' ',COALESCE(item,''))) LIKE '%loaded%'
                OR LOWER(CONCAT(COALESCE(action,''),' ',COALESCE(item,''))) LIKE '%updated%'
                OR LOWER(CONCAT(COALESCE(action,''),' ',COALESCE(item,''))) LIKE '%opened%'
            )
        )");

        $activities = $query->paginate(20)->withQueryString();
        $filterUsers = $seeAll
            ? User::query()->orderBy('name')->limit(500)->get(['id', 'name', 'email'])
            : collect();

        return view('user.history.index', compact('activities', 'seeAll', 'filterUsers'));
    }

    protected function canSeeAllUsersHistory(): bool
    {
        if (Auth::guard('admin')->check()) {
            return true;
        }
        if (function_exists('panel_sees_all_dashboards') && panel_sees_all_dashboards()) {
            return true;
        }
        if (function_exists('is_musa_shaikh_user') && is_musa_shaikh_user()) {
            return true;
        }
        $role = function_exists('normalize_panel_role') ? normalize_panel_role(panel_role_name()) : null;

        return in_array($role, ['admin', 'content_writer', 'accountant'], true);
    }
}
