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
            $audience = activity_audience_for_user($user);
            $logoutAction = $audience === 'staff' ? 'Staff Logout' : 'User Logout';
            $item = $event->guard === 'admin'
                ? 'Session ended from admin panel'
                : 'Session ended';

            record_user_activity(
                $logoutAction,
                $item,
                $event->guard === 'admin' ? admin_login_url() : public_login_url(),
                $audience,
                $user->id,
                null,
                $request,
                $sessionId
            );
        }
    }
}
