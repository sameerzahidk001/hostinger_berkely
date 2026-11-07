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
        CertificateTemplate::ensureDefaultBackgroundFile();
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

    public function previewImage(int $id, string $slot)
    {
        CertificateTemplate::ensureTable();
        CertificateTemplate::ensureDefaultBackgroundFile();
        $template = CertificateTemplate::query()->findOrFail($id);
        $rel = $template->slotPath($slot);
        $abs = $template->absolutePublicFile($rel);
        if (! $abs) {
            abort(404);
        }

        $raw = @file_get_contents($abs);
        if ($raw === false || $raw === '') {
            abort(404);
        }

        $mime = 'image/png';
        $ext = strtolower((string) pathinfo($abs, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg'], true)) {
            $mime = 'image/jpeg';
        } elseif ($ext === 'webp') {
            $mime = 'image/webp';
        } elseif ($ext === 'gif') {
            $mime = 'image/gif';
        }

        if ($slot === 'background' && function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
            $src = @imagecreatefromstring($raw);
            if ($src) {
                $w = imagesx($src);
                $h = imagesy($src);
                $maxW = 900;
                $maxH = 640;
                $scale = min(1, $maxW / max(1, $w), $maxH / max(1, $h));
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));
                $dst = imagecreatetruecolor($nw, $nh);
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                ob_start();
                imagejpeg($dst, null, 84);
                $jpeg = ob_get_clean();
                imagedestroy($src);
                imagedestroy($dst);
                if (is_string($jpeg) && $jpeg !== '') {
                    $raw = $jpeg;
                    $mime = 'image/jpeg';
                }
            }
        }

        return response($raw, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=120',
        ]);
    }

    public function edit(int $id)
    {
        CertificateTemplate::ensureTable();
        CertificateTemplate::ensureDefaultBackgroundFile();
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
        $this->removeImages($request, $template);
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

    private function removeImages(Request $request, CertificateTemplate $template): void
    {
        $map = [
            'remove_background_image' => 'background_path',
            'remove_logo_image' => 'logo_path',
            'remove_seal_image' => 'seal_path',
            'remove_signer_left_image' => 'signer_left_signature',
            'remove_signer_right_image' => 'signer_right_signature',
        ];

        $changed = false;
        foreach ($map as $input => $column) {
            if (! $request->boolean($input)) {
                continue;
            }
            $path = trim((string) $template->{$column});
            if ($path !== '' && ! str_contains($path, 'certificates/assets/')) {
                $abs = public_path(ltrim(str_replace('\\', '/', $path), '/'));
                if (is_file($abs)) {
                    File::delete($abs);
                }
            }
            $template->{$column} = null;
            $changed = true;
        }

        if ($changed) {
            $template->save();
        }
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
            $ext = strtolower((string) $file->getClientOriginalExtension() ?: 'png');
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'png';
            }
            $tmpName = $column . '-' . Str::random(6) . '-src.' . $ext;
            $file->move($dirAbs, $tmpName);
            $tmpAbs = $dirAbs . DIRECTORY_SEPARATOR . $tmpName;
            if ($column === 'background_path') {
                // Keep the artwork opaque. Transparency knockout turns cream
                // certificate paper into a broken/empty preview.
                $name = $column . '-' . Str::random(6) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                $destAbs = $dirAbs . DIRECTORY_SEPARATOR . $name;
                File::move($tmpAbs, $destAbs);
            } else {
                $name = $column . '-' . Str::random(6) . '.png';
                $destAbs = $dirAbs . DIRECTORY_SEPARATOR . $name;
                $wrote = \App\Services\CertificateIssuerService::writeTransparentPng($tmpAbs, $destAbs);
                if ($wrote && is_file($tmpAbs) && realpath($tmpAbs) !== realpath($destAbs)) {
                    File::delete($tmpAbs);
                }
                if (! $wrote) {
                    $name = $tmpName;
                }
            }
            $template->{$column} = $dirRel . '/' . $name;
            $changed = true;
        }
        if ($changed) {
            $template->save();
        }
    }
}
