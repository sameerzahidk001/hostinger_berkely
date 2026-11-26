<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SitePathBranding;
use App\Services\PathSectionSetupService;

class EventsHubController extends Controller
{
    /**
     * Lightweight Events admin hub (path branding parity). Full Events product follows Journal stability.
     */
    public function index(PathSectionSetupService $setup)
    {
        // Ensure Events path branding + blank pages exist without touching LMS data.
        try {
            $setup->ensureDefaults();
        } catch (\Throwable $e) {
            // ignore — page still renders
        }

        $branding = null;
        if (SitePathBranding::ensureTable()) {
            $branding = SitePathBranding::query()
                ->where('path_prefix', 'events')
                ->first();
        }

        return view('admin.events.hub', compact('branding'));
    }
}
