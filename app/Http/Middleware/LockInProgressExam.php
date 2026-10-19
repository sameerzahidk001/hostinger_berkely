<?php

namespace App\Http\Middleware;

use App\Models\LmsAssessmentAttempt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LockInProgressExam
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user || ! Schema::hasTable('lms_assessment_attempts')) {
            return $next($request);
        }

        $attempt = LmsAssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->orderByDesc('id')
            ->first();
        if (! $attempt) {
            return $next($request);
        }

        if ($request->routeIs([
            'user.assessments.take',
            'user.assessments.save',
            'user.assessments.resource',
            'user.assessments.start',
            'user.logout',
            'session.ping',
        ])) {
            return $next($request);
        }

        return redirect()
            ->route('user.assessments.take', $attempt->id)
            ->with('fail', 'Finish your test before opening other pages.');
    }
}
