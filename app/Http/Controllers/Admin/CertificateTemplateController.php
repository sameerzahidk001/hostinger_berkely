<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CertificateTemplateController extends Controller
{
    public function index()
    {
        CertificateTemplate::ensureTable();
        $templates = CertificateTemplate::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('admin.certificates.templates.index', compact('templates'));
    }

    public function create()
    {
        CertificateTemplate::ensureTable();
        $defaults = CertificateTemplate::berkeleyDefaults();
        $defaults['is_default'] = false;
        $defaults['name'] = 'Berkeley Certificate of Completion';

        return view('admin.certificates.templates.form', [
            'template' => new CertificateTemplate($defaults),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        CertificateTemplate::ensureTable();
        $data = $this->validated($request);
        $template = CertificateTemplate::query()->create($data);
        $this->storeImages($request, $template);
        if (! empty($data['is_default'])) {
            $template->markAsDefault();
        } elseif (! CertificateTemplate::query()->where('is_default', true)->exists()) {
            $template->markAsDefault();
        }

        return redirect()
            ->route('admin.certificates.templates.index')
            ->with('success', 'Certificate template created.');
    }

    public function edit(int $id)
    {
        CertificateTemplate::ensureTable();
        $template = CertificateTemplate::query()->findOrFail($id);

        return view('admin.certificates.templates.form', [
            'template' => $template,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, int $id)
    {
        CertificateTemplate::ensureTable();
        $template = CertificateTemplate::query()->findOrFail($id);
        $data = $this->validated($request);
        $template->fill($data);
        $template->save();
        $this->storeImages($request, $template);
        if (! empty($data['is_default'])) {
            $template->markAsDefault();
        }

        return redirect()
            ->route('admin.certificates.templates.index')
            ->with('success', 'Certificate template updated.');
    }

    public function destroy(int $id)
    {
        CertificateTemplate::ensureTable();
        $template = CertificateTemplate::query()->findOrFail($id);
        if ($template->is_default) {
            return back()->with('fail', 'Cannot delete the default template. Set another default first.');
        }
        $template->delete();

        return back()->with('success', 'Template deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'heading_line' => 'nullable|string|max:255',
            'body_text' => 'nullable|string|max:2000',
            'school_name' => 'nullable|string|max:255',
            'orientation' => 'required|in:landscape,portrait',
            'primary_color' => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'signer_left_name' => 'nullable|string|max:255',
            'signer_left_title' => 'nullable|string|max:255',
            'signer_right_name' => 'nullable|string|max:255',
            'signer_right_title' => 'nullable|string|max:255',
            'footer_org_line' => 'nullable|string|max:500',
            'footer_email' => 'nullable|string|max:255',
            'footer_website' => 'nullable|string|max:255',
            'verify_heading' => 'nullable|string|max:255',
            'default_duration' => 'nullable|string|max:255',
            'default_cpd_hours' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'background_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'logo_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:500',
            'seal_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:500',
            'signer_left_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:500',
            'signer_right_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:500',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);
        $data['eyebrow'] = 'CERTIFICATE';
        $data['title'] = 'OF COMPLETION';
        unset(
            $data['background_image'],
            $data['logo_image'],
            $data['seal_image'],
            $data['signer_left_image'],
            $data['signer_right_image']
        );

        return $data;
    }

    private function storeImages(Request $request, CertificateTemplate $template): void
    {
        $map = [
            'background_image' => 'background_path',
            'logo_image' => 'logo_path',
            'seal_image' => 'seal_path',
            'signer_left_image' => 'signer_left_signature',
            'signer_right_image' => 'signer_right_signature',
        ];
        $dirRel = 'certificates/templates/' . $template->id;
        $dirAbs = public_path($dirRel);
        if (! File::isDirectory($dirAbs)) {
            File::makeDirectory($dirAbs, 0755, true);
        }

        $changed = false;
        foreach ($map as $input => $column) {
            $file = $request->file($input);
            if (! $file) {
                continue;
            }
            $ext = strtolower((string) $file->getClientOriginalExtension()) ?: 'png';
            $name = $column . '-' . Str::random(6) . '.' . $ext;
            $file->move($dirAbs, $name);
            $template->{$column} = $dirRel . '/' . $name;
            $changed = true;
        }
        if ($changed) {
            $template->save();
        }
    }
}
