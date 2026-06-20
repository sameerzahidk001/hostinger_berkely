<?php

namespace App\Http\Controllers\User;

use App\Models\Email;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Mail\UserMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $countries = Country::all();

        return view('user.profile', compact('user', 'countries'));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image_path' => 'nullable|string',
            'image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'mobile_number' => 'required|string|max:20',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'address' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:20',
            'nationality' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'experience' => 'nullable|string',
            'short_description' => 'nullable|string',
            'linkedin' => 'nullable|url|string|max:255',
            'education' => 'nullable|array',
            'education.*' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validatedData = $validator->validated();
        $user = Auth::user();

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $slug = Str::slug($originalName) . '-' . time();
            $fileName = $slug . '.' . $extension;
            $destinationPath = public_path('images/profiles/');

            if (! is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $fileName);
            $validatedData['image'] = 'images/profiles/' . $fileName;
        } elseif ($request->filled('image_path')) {
            $validatedData['image'] = str_replace('\\', '/', $request->image_path);
        }

        $user->update($validatedData);

        $mailError = null;
        $emailTemplate = Email::where('name', 'user-update')->first();
        $placeholders = ['{name}', '{email}'];
        $values = [$user->name, $user->email];

        if ($emailTemplate) {
            try {

                $emailBody = str_replace($placeholders, $values, $emailTemplate->body);

                $cc = !empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
                $bcc = !empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

                Mail::to($user->email)->cc($cc)->bcc($bcc)
                    ->send(new UserMail($user, $emailTemplate->subject, $emailBody));
            } catch (\Throwable $e) {
                $mailError = "Email sending failed: " . $e->getMessage();
            }
        }

        $successMessage = 'User updated successfully!';
        if ($mailError)
            $successMessage .= ' However, there was an issue sending the email: ' . $mailError;

        return redirect()->back()->with('success', 'Your profile updated successfully!');
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.'])->withInput();
        }

        $rawPassword = $request->input('password');
        $user->password = Hash::make($rawPassword);
        $user->save();

        $mailError = null;
        $emailTemplate = Email::where('name', 'password-changed')->first();

        if ($emailTemplate) {
            try {
                $placeholders = ['{name}', '{email}', '{password}'];
                $values = [$user->name, $user->email, $rawPassword];

                $emailBody = str_replace($placeholders, $values, $emailTemplate->body);

                $cc = !empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
                $bcc = !empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

                Mail::to($user->email)->cc($cc)->bcc($bcc)
                    ->send(new UserMail($user, $emailTemplate->subject, $emailBody));
            } catch (\Throwable $e) {
                $mailError = "Email sending failed: " . $e->getMessage();
            }
        }

        $msg = 'Password updated successfully.';
        if ($mailError)
            $msg .= ' However, there was an issue sending the email: ' . $mailError;

        return back()->with('success', $msg);
    }

}