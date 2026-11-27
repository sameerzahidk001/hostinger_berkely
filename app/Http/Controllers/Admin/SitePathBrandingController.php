<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\SitePathBranding;
use App\Services\PathSectionSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SitePathBrandingController extends Controller
{
    public function index()
    {
        SitePathBranding::ensureTable();
        $rows = SitePathBranding::query()->orderBy('path_prefix')->orderBy('id')->get();

        return view('admin.settings.path-branding.index', compact('rows'));
    }

    public function ensureDefaults(PathSectionSetupService $setup)
    {
        $setup->ensureDefaults();

        return redirect()->route('admin.path-branding.index')
            ->with('success', 'Journal + Events hubs ready (blank pages, menus, logos, path branding). Edit anytime under Pages / Menu / Path Branding.');
    }

    public function create()
    {
        SitePathBranding::ensureTable();
        $menuGroups = Menu::query()->whereNotNull('menu_group')->pluck('menu_group')->unique()->filter()->values();
        $row = new SitePathBranding([
            'is_active' => true,
            'path_prefix' => 'journal',
            'name' => 'Academic Journal',
            'home_url' => '/journal',
        ]);

        return view('admin.settings.path-branding.form', compact('row', 'menuGroups'));
    }

    public function store(Request $request)
    {
        SitePathBranding::ensureTable();
        $data = $this->validated($request);
        $data['path_prefix'] = SitePathBranding::normalizePrefix($data['path_prefix'] ?? '');

        if ($data['path_prefix'] === '') {
            return back()->withInput()->with('fail', 'Path prefix is required (example: journal).');
        }

        if (SitePathBranding::query()->where('path_prefix', $data['path_prefix'])->exists()) {
            return back()->withInput()->with('fail', 'That path prefix already has branding.');
        }

        if ($request->hasFile('logo_file')) {
            $data['logo'] = $this->storeLogo($request->file('logo_file'));
        }

        SitePathBranding::create($data);

        return redirect()->route('admin.path-branding.index')
            ->with('success', 'Path branding saved. Pages under /' . $data['path_prefix'] . ' will use this logo and menu.');
    }

    public function edit(string $id)
    {
        SitePathBranding::ensureTable();
        $row = SitePathBranding::query()->findOrFail($id);
        $menuGroups = Menu::query()->whereNotNull('menu_group')->pluck('menu_group')->unique()->filter()->values();

        return view('admin.settings.path-branding.form', compact('row', 'menuGroups'));
    }

    public function update(Request $request, string $id)
    {
        SitePathBranding::ensureTable();
        $row = SitePathBranding::query()->findOrFail($id);
        $data = $this->validated($request);
        $data['path_prefix'] = SitePathBranding::normalizePrefix($data['path_prefix'] ?? '');

        if ($data['path_prefix'] === '') {
            return back()->withInput()->with('fail', 'Path prefix is required (example: journal).');
        }

        $duplicate = SitePathBranding::query()
            ->where('path_prefix', $data['path_prefix'])
            ->where('id', '!=', $row->id)
            ->exists();
        if ($duplicate) {
            return back()->withInput()->with('fail', 'That path prefix already has branding.');
        }

        if ($request->hasFile('logo_file')) {
            $data['logo'] = $this->storeLogo($request->file('logo_file'));
        }

        $row->fill($data)->save();

        return redirect()->route('admin.path-branding.index')
            ->with('success', 'Path branding updated.');
    }

    public function destroy(string $id)
    {
        SitePathBranding::ensureTable();
        $row = SitePathBranding::query()->findOrFail($id);
        $row->delete();

        return redirect()->route('admin.path-branding.index')
            ->with('success', 'Path branding removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        SitePathBranding::ensureFooterColumns();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'path_prefix' => 'required|string|max:120',
            'menu_group' => 'nullable|string|max:120',
            'home_url' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:300',
            'custom_footer' => 'nullable|boolean',
            'footer_menu_group' => 'nullable|string|max:120',
            'footer_copyright' => 'nullable|string|max:255',
            'footer_bg' => 'nullable|string|max:32',
            'footer_text_color' => 'nullable|string|max:32',
            'footer_title_color' => 'nullable|string|max:32',
            'footer_social' => 'nullable|array',
            'footer_social.*' => 'nullable|string|max:500',
            'wa_label' => 'nullable|array',
            'wa_label.*' => 'nullable|string|max:80',
            'wa_url' => 'nullable|array',
            'wa_url.*' => 'nullable|string|max:500',
        ]);

        $data = $validator->validate();
        $data['is_active'] = $request->boolean('is_active');
        $data['custom_footer'] = $request->boolean('custom_footer');
        $data['menu_group'] = trim((string) ($data['menu_group'] ?? '')) ?: null;
        $data['home_url'] = trim((string) ($data['home_url'] ?? '')) ?: null;
        $data['footer_menu_group'] = trim((string) ($data['footer_menu_group'] ?? '')) ?: null;
        $data['footer_copyright'] = trim((string) ($data['footer_copyright'] ?? '')) ?: null;
        $data['footer_bg'] = trim((string) ($data['footer_bg'] ?? '')) ?: null;
        $data['footer_text_color'] = trim((string) ($data['footer_text_color'] ?? '')) ?: null;
        $data['footer_title_color'] = trim((string) ($data['footer_title_color'] ?? '')) ?: null;

        $socialIn = (array) $request->input('footer_social', []);
        $social = [];
        foreach (['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok'] as $platform) {
            $url = trim((string) ($socialIn[$platform . '_url'] ?? ''));
            if ($url !== '') {
                $social[$platform . '_url'] = $url;
            }
        }
        $data['footer_social'] = $social !== [] ? $social : null;

        $wa = [];
        $labels = (array) $request->input('wa_label', []);
        $urls = (array) $request->input('wa_url', []);
        $max = max(count($labels), count($urls), 3);
        for ($i = 0; $i < $max; $i++) {
            $url = trim((string) ($urls[$i] ?? ''));
            if ($url === '') {
                continue;
            }
            $wa[] = [
                'label' => trim((string) ($labels[$i] ?? '')) ?: ('WhatsApp ' . ($i + 1)),
                'url' => $url,
            ];
        }
        $data['footer_whatsapp'] = $wa !== [] ? $wa : null;

        unset($data['wa_label'], $data['wa_url']);

        return $data;
    }

    private function storeLogo($file): string
    {
        $fileName = generateFileName($file, 'path-brand-');
        $destination = public_path('images');
        if (! is_dir($destination)) {
            @mkdir($destination, 0775, true);
        }
        public_upload_move($file, $destination, $fileName);

        return $fileName;
    }
}
