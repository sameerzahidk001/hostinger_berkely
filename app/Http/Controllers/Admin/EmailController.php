<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Email;

class EmailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $emails = Email::all();
        return view('admin.emails.index', compact('emails'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.emails.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:emails',
            'subject' => 'required',
            'cc' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $emails = explode(',', $value);
                    foreach ($emails as $email) {
                        $email = trim($email);
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The {$attribute} field contains an invalid email: {$email}");
                        }
                    }
                }
            ],
            'bcc' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $emails = explode(',', $value);
                    foreach ($emails as $email) {
                        $email = trim($email);
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The {$attribute} field contains an invalid email: {$email}");
                        }
                    }
                }
            ],
            'body' => 'required',
        ]);

        Email::create($request->all());

        return redirect()->route('admin.emails.index')->with('success', 'Email Template Created Successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $email = Email::findOrFail($id);
        return view('admin.emails.edit', compact('email'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $email = Email::findOrFail($id);
        return view('admin.emails.edit', compact('email'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'cc' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $emails = explode(',', $value);
                    foreach ($emails as $email) {
                        $email = trim($email);
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The {$attribute} field contains an invalid email: {$email}");
                        }
                    }
                }
            ],
            'bcc' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $emails = explode(',', $value);
                    foreach ($emails as $email) {
                        $email = trim($email);
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The {$attribute} field contains an invalid email: {$email}");
                        }
                    }
                }
            ],
            'subject' => 'required',
            'body' => 'required',
        ]);

        $email = Email::findOrFail($id);
        $email->update($request->all());

        return redirect()->route('admin.emails.index')->with('success', 'Email Template Updated Successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $email = Email::findOrFail($id);
        $email->delete();
        return redirect()->route('admin.emails.index')->with('success', 'Email Template deleted Successfully.');
    }
}
