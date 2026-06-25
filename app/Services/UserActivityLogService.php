<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserActivityLog;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class UserActivityLogService
{
    public function tableExists(): bool
    {
        return Schema::hasTable('user_activity_logs');
    }

    public function log(
        string $action,
        string $audience,
        ?string $item = null,
        ?string $url = null,
        ?int $userId = null,
        ?int $adminId = null,
        ?string $ipAddress = null,
        ?string $sessionId = null,
        ?array $meta = null
    ): void {
        if (! $this->tableExists()) {
            return;
        }

        $sessionActions = [
            'Admin Login', 'Staff Login', 'User Login',
            'Admin Logout', 'Staff Logout', 'User Logout',
            'Admin Log out', 'Staff Log out', 'User Log out',
        ];

        if (in_array($action, $sessionActions, true)) {
            $duplicateQuery = UserActivityLog::query()
                ->where('action', $action)
                ->where('created_at', '>=', now()->subMinute());

            if ($userId) {
                $duplicateQuery->where('user_id', $userId);
            } elseif ($adminId) {
                $duplicateQuery->where('admin_id', $adminId);
            } elseif ($sessionId) {
                $duplicateQuery->where('session_id', $sessionId);
            }

            if ($duplicateQuery->exists()) {
                return;
            }
        }

        try {
            UserActivityLog::create([
                'user_id' => $userId,
                'admin_id' => $adminId,
                'audience' => $audience,
                'action' => $action,
                'item' => $item,
                'url' => $url,
                'ip_address' => $ipAddress,
                'session_id' => $sessionId,
                'meta' => $meta,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('user_activity_logs insert failed: ' . $e->getMessage());
        }
    }

    public function paginatedFeed(
        string $audience,
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo,
        int $perPage = 15,
        int $page = 1,
        string $path = '',
        array $query = []
    ): LengthAwarePaginator {
        $activities = $this->buildFeed($audience, $userId, $dateFrom, $dateTo);
        $total = $activities->count();
        $items = $activities->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $path, 'query' => $query, 'pageName' => $audience === 'student' ? 'student_page' : 'page']
        );
    }

    public function buildFeed(
        string $audience,
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo
    ): Collection {
        if (! $this->tableExists()) {
            return collect();
        }

        [$from, $to] = $this->parseDateRange($dateFrom, $dateTo);

        $query = UserActivityLog::query()
            ->with(['user', 'admin'])
            ->where('audience', $audience)
            ->orderByDesc('created_at');

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('admin_id', $userId);
            });
        }

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->get()->map(function (UserActivityLog $log) {
            return [
                'action' => str_replace(' Logout', ' Log out', (string) $log->action),
                'item' => $log->item ?? '',
                'url' => $log->url,
                'session_id' => $log->session_id,
                'actor_id' => $log->user_id ?? $log->admin_id,
                'user_name' => $this->resolveActorName($log),
                'occurred_at' => Carbon::parse($log->created_at),
            ];
        });
    }

    public function studentFilterUsers(): Collection
    {
        return User::query()
            ->with('roles')
            ->whereHas('roles', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['student']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function resolveActorName(UserActivityLog $log): string
    {
        if ($log->user) {
            return $log->user->name ?? $log->user->email ?? 'User #' . $log->user_id;
        }

        if ($log->admin) {
            return $log->admin->username ?? $log->admin->name ?? $log->admin->email ?? 'Admin #' . $log->admin_id;
        }

        return '-';
    }

    private function parseDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        return [$from, $to];
    }
}
