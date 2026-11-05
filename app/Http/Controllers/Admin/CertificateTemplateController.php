<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;

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

        return view('admin.certificates.templates.form', [
            'template' => new CertificateTemplate([
                'eyebrow' => 'Certificate of Completion',
                'title' => 'Digital Certificate',
                'school_name' => 'Berkeley School of Business, Arts & Sciences',
                'intro_line' => 'This is to certify that',
                'completion_line' => 'has successfully completed',
                'orientation' => 'landscape',
                'primary_color' => '#000435',
                'accent_color' => '#f8961f',
                'is_active' => true,
                'is_default' => false,
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        CertificateTemplate::ensureTable();
        $data = $this->validated($request);
        $template = CertificateTemplate::query()->create($data);
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
            'eyebrow' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'school_name' => 'nullable|string|max:255',
            'intro_line' => 'nullable|string|max:500',
            'completion_line' => 'nullable|string|max:500',
            'orientation' => 'required|in:landscape,portrait',
            'primary_color' => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);

        return $data;
    }
}
