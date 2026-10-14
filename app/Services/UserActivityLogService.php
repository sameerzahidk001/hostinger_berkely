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
        ?string $dateTo,
        ?int $limit = null
    ): Collection {
        if (! $this->tableExists()) {
            return collect();
        }

        [$from, $to] = $this->parseDateRange($dateFrom, $dateTo);

        $query = UserActivityLog::query()
            ->with(['user.roles', 'admin'])
            ->where('audience', $audience)
            ->orderByDesc('created_at');

        // Student history must not list instructors/staff even if older rows were mis-tagged.
        if ($audience === 'student') {
            $query->whereDoesntHave('user.roles', function ($rq) {
                $rq->whereRaw('LOWER(name) in (?, ?, ?, ?, ?, ?)', [
                    'instructor',
                    'admin',
                    'content_writer',
                    'content-writer',
                    'librarian',
                    'accountant',
                ]);
            });
        }

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

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        // Support Collection of arrays — Eloquent\Collection::merge() would call getKey().
        return collect($query->get()->map(function (UserActivityLog $log) {
            $url = $log->url;
            $isContentEdit = is_string($url) && (
                str_contains($url, '/course/')
                || str_contains($url, '/pages/')
                || str_contains($url, '/courses-pages-seo/')
                || str_contains($url, '/seo/')
                || str_contains($url, '/training-calendar/')
                || str_contains($url, '/course-agendas/')
            );

            $row = [
                'action' => activity_action_label($log->action),
                'item' => $log->item ?? '',
                'url' => $url,
                'edit_url' => $isContentEdit ? $url : null,
                'visit_url' => null,
                'session_id' => $log->session_id,
                'actor_id' => $log->user_id ?? $log->admin_id,
                'user_name' => $this->resolveActorName($log),
                'contact_number' => $this->resolveActorContact($log),
                'occurred_at' => Carbon::parse($log->created_at),
            ];

            return $this->withInferredContentLinks($row);
        })->all());
    }

    /**
     * Content-report / training-calendar log rows only store one URL — infer Visit/Edit for Activities.
     */
    private function withInferredContentLinks(array $row): array
    {
        $item = strtoupper((string) ($row['item'] ?? ''));
        $action = strtolower((string) ($row['action'] ?? ''));
        $mentionsTraining = str_contains($item, 'TRAINING CALENDAR')
            || str_contains($action, 'training calendar');

        if (! $mentionsTraining) {
            return $row;
        }

        if (empty($row['visit_url'])) {
            // Public training calendar / related course listing on the website.
            try {
                $row['visit_url'] = route('agenda_search');
            } catch (\Throwable $e) {
                $row['visit_url'] = url('/agenda_search');
            }
        }

        if (empty($row['edit_url'])) {
            $url = (string) ($row['url'] ?? '');
            if ($url !== '' && (
                str_contains($url, '/training-calendar/')
                || str_contains($url, '/course-agendas/')
            )) {
                $row['edit_url'] = $url;
            } else {
                try {
                    $row['edit_url'] = route('admin.course-agendas.index');
                } catch (\Throwable $e) {
                    $row['edit_url'] = $url !== '' ? $url : null;
                }
            }
        }

        if (empty($row['url'])) {
            $row['url'] = $row['edit_url'];
        } elseif (! empty($row['edit_url'])) {
            $current = (string) $row['url'];
            // Older content-report logs pointed at admin home — prefer Training Calendar edit link.
            if ($current === ''
                || str_contains($current, '/admin/home')
                || str_contains($current, '/dashboard/content-writers')
            ) {
                $row['url'] = $row['edit_url'];
            }
        }

        return $row;
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
            return panel_actor_display_name(
                $log->user->name ?? null,
                $log->user->email ?? null,
                $log->user->username ?? null
            );
        }

        if ($log->admin) {
            return panel_actor_display_name($log->admin->name ?? null, $log->admin->email ?? null);
        }

        if ($log->user_id) {
            $user = User::query()->find($log->user_id);
            if ($user) {
                return panel_actor_display_name($user->name ?? null, $user->email ?? null, $user->username ?? null);
            }
        }

        if ($log->admin_id) {
            $admin = Admin::query()->find($log->admin_id);
            if ($admin) {
                return panel_actor_display_name($admin->name ?? null, $admin->email ?? null);
            }
        }

        return '-';
    }

    private function resolveActorContact(UserActivityLog $log): string
    {
        $mobile = null;
        if ($log->user) {
            $mobile = $log->user->mobile_number ?? null;
        } elseif ($log->user_id) {
            $mobile = User::query()->whereKey($log->user_id)->value('mobile_number');
        }

        $mobile = trim((string) $mobile);

        return $mobile !== '' ? $mobile : '—';
    }

    private function parseDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        return [$from, $to];
    }
}
