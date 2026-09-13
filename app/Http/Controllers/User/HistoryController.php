<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
        if (! $logs->tableExists()) {
            $activities = new LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url()]);

            return view('user.history.index', compact('activities'));
        }

        $query = UserActivityLog::query()
            ->where('audience', 'student')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $activities = $query->paginate(20)->withQueryString();

        return view('user.history.index', compact('activities'));
    }
}
