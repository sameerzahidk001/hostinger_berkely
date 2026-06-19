<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserActivityLog;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class UserActivityLogService
{
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
        if (! Schema::hasTable('user_activity_logs')) {
            return;
        }

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
        if (! Schema::hasTable('user_activity_logs')) {
            return collect();
        }

        [$from, $to] = $this->parseDateRange($dateFrom, $dateTo);

        $query = UserActivityLog::query()
            ->with(['user', 'admin'])
            ->where('audience', $audience)
            ->orderByDesc('created_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->get()->map(function (UserActivityLog $log) {
            return [
                'action' => $log->action,
                'item' => $log->item ?? '',
                'url' => $log->url,
                'session_id' => $log->session_id,
                'actor_id' => $log->user_id,
                'user_name' => $this->resolveActorName($log),
                'occurred_at' => Carbon::parse($log->created_at),
            ];
        });
    }

    public function studentFilterUsers(): Collection
    {
        return User::query()
            ->with('roles')
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
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
