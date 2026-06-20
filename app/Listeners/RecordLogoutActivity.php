<?php

namespace App\Listeners;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Auth\Events\Logout;

class RecordLogoutActivity
{
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (! $user) {
            return;
        }

        $request = request();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        if ($user instanceof Admin) {
            record_user_activity(
                'Admin Logout',
                'Session ended',
                admin_login_url(),
                'staff',
                null,
                $user->id,
                $request,
                $sessionId
            );

            return;
        }

        if ($user instanceof User) {
            $fromAdminPanel = $request->is('admin/*') || $request->is('admin');

            record_user_activity(
                'User Logout',
                $fromAdminPanel ? 'Session ended from admin panel' : 'Session ended',
                $fromAdminPanel ? admin_login_url() : public_login_url(),
                activity_audience_for_user($user),
                $user->id,
                null,
                $request,
                $sessionId
            );
        }
    }
}
