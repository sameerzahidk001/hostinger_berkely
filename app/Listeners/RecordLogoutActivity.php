<?php

namespace App\Listeners;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Auth\Events\Logout;

class RecordLogoutActivity
{
    /** @var array<string, bool> */
    private static array $logged = [];

    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (! $user) {
            return;
        }

        $request = request();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $dedupeKey = ($sessionId ?? 'no-session') . '|' . $user::class . '|' . $user->getKey();

        if (isset(self::$logged[$dedupeKey])) {
            return;
        }

        self::$logged[$dedupeKey] = true;

        if ($user instanceof Admin) {
            record_user_activity(
                'Admin Log out',
                'Session ended',
                public_login_url(),
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
            $logoutAction = $audience === 'staff' ? 'Staff Log out' : 'User Log out';
            $item = $event->guard === 'admin'
                ? 'Session ended from admin panel'
                : 'Session ended';

            record_user_activity(
                $logoutAction,
                $item,
                public_login_url(),
                $audience,
                $user->id,
                null,
                $request,
                $sessionId
            );
        }
    }
}
