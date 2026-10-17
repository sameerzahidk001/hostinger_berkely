<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ActivityGapCommentService
{
    public function ensure(): void
    {
        if (Schema::hasTable('activity_gap_comments')) {
            return;
        }
        try {
            Schema::create('activity_gap_comments', function ($table) {
                $table->id();
                $table->string('gap_key', 64)->unique();
                $table->string('scope', 20)->index();
                $table->unsignedInteger('from_ts');
                $table->unsignedInteger('to_ts');
                $table->text('comment');
                $table->unsignedBigInteger('updated_by_user_id')->nullable();
                $table->unsignedBigInteger('updated_by_admin_id')->nullable();
                $table->timestamps();
            });
        } catch (\Throwable $e) {
            // Hostinger may already have created it on another request.
        }
    }

    public function key(string $scope, int $fromTs, int $toTs): string
    {
        $a = min($fromTs, $toTs);
        $b = max($fromTs, $toTs);

        return sha1($scope . '|' . $a . '|' . $b);
    }

    /** @return array<string, string> */
    public function commentsForFeed(string $scope, $rows): array
    {
        $this->ensure();
        if (! Schema::hasTable('activity_gap_comments')) {
            return [];
        }
        $keys = $this->gapKeys($scope, $rows);
        if ($keys === []) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('activity_gap_comments')
            ->whereIn('gap_key', $keys)
            ->pluck('comment', 'gap_key')
            ->map(fn ($c) => (string) $c)
            ->all();
    }

    public function save(string $scope, int $fromTs, int $toTs, string $comment): void
    {
        $this->ensure();
        if (! Schema::hasTable('activity_gap_comments')) {
            return;
        }
        $key = $this->key($scope, $fromTs, $toTs);
        $a = min($fromTs, $toTs);
        $b = max($fromTs, $toTs);
        $payload = [
            'scope' => $scope,
            'from_ts' => $a,
            'to_ts' => $b,
            'comment' => $comment,
            'updated_at' => now(),
        ];
        if (Auth::guard('admin')->check()) {
            $payload['updated_by_admin_id'] = Auth::guard('admin')->id();
            $payload['updated_by_user_id'] = null;
        } else {
            $payload['updated_by_user_id'] = Auth::id();
            $payload['updated_by_admin_id'] = null;
        }

        $existing = \Illuminate\Support\Facades\DB::table('activity_gap_comments')->where('gap_key', $key)->first();
        if ($existing) {
            \Illuminate\Support\Facades\DB::table('activity_gap_comments')->where('gap_key', $key)->update($payload);
            return;
        }
        $payload['gap_key'] = $key;
        $payload['created_at'] = now();
        \Illuminate\Support\Facades\DB::table('activity_gap_comments')->insert($payload);
    }

    /** @return list<string> */
    public function gapKeys(string $scope, $rows): array
    {
        $keys = [];
        $prev = null;
        foreach (Collection::make($rows) as $row) {
            $cur = $row['occurred_at'] ?? null;
            if ($prev && $cur && abs($cur->getTimestamp() - $prev->getTimestamp()) > 600) {
                $keys[] = $this->key($scope, $prev->getTimestamp(), $cur->getTimestamp());
            }
            $prev = $cur ?: $prev;
        }

        return $keys;
    }
}
