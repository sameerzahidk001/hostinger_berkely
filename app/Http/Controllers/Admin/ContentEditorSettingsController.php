<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContentEditorSettingsService;
use Illuminate\Http\Request;

class ContentEditorSettingsController extends Controller
{
    public function edit(ContentEditorSettingsService $settings)
    {
        $settings->ensureStorage();

        return view('admin.settings.content-editor', [
            'tableMissing' => false,
            'storageMode' => $settings->tableExists() ? 'database' : 'file',
            'limits' => $settings->limits(),
            'ai' => $settings->aiConfig(),
        ]);
    }

    public function update(Request $request, ContentEditorSettingsService $settings)
    {
        $settings->ensureStorage();

        $request->validate([
            'limits' => 'nullable|array',
            'ai_enabled' => 'nullable|in:0,1',
            'ai_api_key' => 'nullable|string|max:500',
            'ai_base_url' => 'nullable|url|max:255',
            'ai_model' => 'nullable|string|max:100',
            'ai_timeout' => 'nullable|integer|min:10|max:180',
        ]);

        $settings->saveLimits($request->input('limits', []));
        $settings->saveAiConfig([
            'enabled' => (string) $request->input('ai_enabled', '0') === '1',
            'api_key' => $request->input('ai_api_key'),
            'base_url' => $request->input('ai_base_url') ?: config('content_editor.ai.base_url'),
            'model' => $request->input('ai_model') ?: config('content_editor.ai.model'),
            'timeout' => (int) ($request->input('ai_timeout') ?: 60),
        ]);

        return redirect()->back()->with('success', 'Content editor settings saved.');
    }
}
