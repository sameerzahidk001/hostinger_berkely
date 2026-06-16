<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserBehaviorController extends Controller
{
    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'event_type' => 'required|string',
            'data' => 'required|array',
            'url' => 'required|url',
            'session_id' => 'required|string',
        ]);

        // Find the associated page view
        $pageView = DB::table('page_views')
            ->where('ip_address', $request->ip())
            ->where('url', $request->url)
            ->first();

        if ($pageView) {
            // Check if there is an existing user behavior entry for the same page_view_id and event_type
            $existingBehavior = DB::table('user_behavior')
                ->where('page_view_id', $pageView->id)
                ->where('event_type', $request->event_type)
                ->first();

            if ($existingBehavior) {
                // Update the existing entry
                DB::table('user_behavior')
                    ->where('id', $existingBehavior->id)
                    ->update([
                        'data' => json_encode($request->data),
                        'updated_at' => now(),
                    ]);
            } else {
                // Insert new user behavior data linked to the page view
                DB::table('user_behavior')->insert([
                    'page_view_id' => $pageView->id,
                    'event_type' => $request->event_type,
                    'data' => json_encode($request->data),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            return response()->json(['error' => 'No page view found for this event'], 404);
        }

        return response()->json(['success' => true]);
    }

}
