<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceTermsTemplate;
use Illuminate\Http\Request;

class InvoiceTermsTemplateController extends Controller
{
    public function index()
    {
        InvoiceTermsTemplate::ensureTable();
        $templates = InvoiceTermsTemplate::query()->orderBy('name')->get();

        return view('admin.invoice-terms-templates.index', compact('templates'));
    }

    public function create()
    {
        InvoiceTermsTemplate::ensureTable();

        return view('admin.invoice-terms-templates.create');
    }

    public function store(Request $request)
    {
        InvoiceTermsTemplate::ensureTable();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'body' => 'nullable|string|max:100000',
            'is_active' => 'nullable|in:0,1',
        ]);

        $template = InvoiceTermsTemplate::create([
            'name' => $data['name'],
            'body' => $data['body'] ?? '',
            'is_active' => (string) ($data['is_active'] ?? '1') === '1',
        ]);

        return redirect()
            ->route('admin.invoice-terms-templates.index')
            ->with('success', $template->displayCode() . ' created. Assign it on fee packages from the dropdown.');
    }

    public function edit($id)
    {
        InvoiceTermsTemplate::ensureTable();
        $template = InvoiceTermsTemplate::findOrFail($id);

        return view('admin.invoice-terms-templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        InvoiceTermsTemplate::ensureTable();
        $template = InvoiceTermsTemplate::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'body' => 'nullable|string|max:100000',
            'is_active' => 'nullable|in:0,1',
        ]);

        $template->update([
            'name' => $data['name'],
            'body' => $data['body'] ?? '',
            'is_active' => (string) ($data['is_active'] ?? '1') === '1',
        ]);

        return redirect()
            ->route('admin.invoice-terms-templates.index')
            ->with('success', 'Invoice T&C template updated.');
    }

    public function destroy($id)
    {
        InvoiceTermsTemplate::ensureTable();
        $template = InvoiceTermsTemplate::findOrFail($id);
        $template->delete();

        return redirect()
            ->route('admin.invoice-terms-templates.index')
            ->with('success', 'Invoice T&C template deleted.');
    }
}
