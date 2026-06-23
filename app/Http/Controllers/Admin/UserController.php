<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Email;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use App\Models\Payment;
use App\Models\Country;
use App\Models\Installment;
use Exception;
use App\Mail\UserMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str; // ✅ This fixes the "Class not found" error

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->query('type') === 'librarian') {
                return redirect()->route('users', ['type' => 'content-writer']);
            }

            $type = $request->query('type', Role::first()?->name);
            $roleIds = role_ids_for_list_type((string) $type);

            $usersQuery = User::query()->with('roles')->orderByDesc('created_at');

            if (! empty($roleIds)) {
                $usersQuery->whereHas('roles', function ($query) use ($roleIds) {
                    $query->whereIn('roles.id', $roleIds);
                });
            } else {
                $usersQuery->whereHas('roles', function ($query) use ($type) {
                    $query->where('name', $type);
                });
            }

            $normalizedType = normalize_role_key($type);

            return view('admin.user.index', [
                'type' => is_content_writer_role_key($type) ? 'content-writer' : $type,
                'users' => $usersQuery->get(),
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $contentWriterIds = content_writer_role_ids();
            $roles = Role::all()->reject(function ($role) use ($contentWriterIds) {
                if (! is_content_writer_role_key($role->name)) {
                    return false;
                }

                return count($contentWriterIds) > 1 && $role->id !== ($contentWriterIds[0] ?? $role->id);
            })->values();
            $countries = Country::all();
            return view('admin.user.create', compact('roles', 'countries'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|exists:roles,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'approved' => 'required|boolean',
            'mobile_number' => 'nullable|string|max:20',
            'gender' => 'nullable|in:Male,Female',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'image_path' => 'nullable|string',
            'local_file_input' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'short_description' => 'nullable|string|max:500',
            'long_description' => 'nullable|string',
            'experience' => 'nullable|string|max:255',
            'linkedin' => 'nullable|url|string|max:255',
            'education' => 'nullable|array',
            'education.*' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $role = Role::find($data['role']);
        $data['role'] = resolve_content_writer_role_id((int) $data['role']) ?? $data['role'];
        $role = Role::find($data['role']);

        if ($request->hasFile('local_file_input')) {
            $file = $request->file('local_file_input');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $slug = Str::slug($originalName) . '-' . time();
            $fileName = $slug . '.' . $extension;
            $destinationPath = public_path('images/profiles/');
            $file->move($destinationPath, $fileName);
            $image = 'images/profiles/' . $fileName;
        } elseif ($request->filled('image_path')) {
            $image = normalize_profile_image_path($request->image_path);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'approved' => $data['approved'],
            'image' => $image ?? 'images/profiles/user.png',
            'mobile_number' => $data['mobile_number'] ?? null,
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'address' => $data['address'] ?? null,
            'post_code' => $data['post_code'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'experience' => strtolower($role->name) === 'instructor' ? ($data['experience'] ?? null) : null,
            'short_description' => $data['short_description'] ?? null,
            'long_description' => $data['long_description'] ?? null,
            'linkedin' => $data['linkedin'] ?? null,
            'education' => strtolower($role->name) === 'instructor' ? json_encode($data['education'] ?? []) : null,
        ]);

        $user->roles()->sync([$data['role']]);

        // Send email if template exists
        $emailTemplate = Email::where('name', 'user-registration')->first();
        $mailError = null;

        if ($emailTemplate) {
            try {
                $emailBody = str_replace(
                    ['{name}', '{email}', '{password}', '{role}'],
                    [$user->name, $user->email, $request->password, role_display_name($role->name)],
                    $emailTemplate->body
                );

                $cc = $emailTemplate->cc ? array_map('trim', explode(',', $emailTemplate->cc)) : [];
                $bcc = $emailTemplate->bcc ? array_map('trim', explode(',', $emailTemplate->bcc)) : [];

                Mail::to($user->email)
                    ->cc($cc)
                    ->bcc($bcc)
                    ->send(new UserMail($user, $emailTemplate->subject, $emailBody));
            } catch (\Exception $e) {
                $mailError = "Email sending failed: " . $e->getMessage();
            }
        }

        $successMessage = 'User created successfully!';
        if ($mailError) {
            $successMessage .= ' But failed to send email: ' . $mailError;
        }

        return redirect()->route('users', ['type' => user_list_type_param($role->name)])->with('success', $successMessage);
    }



    public function edit($id)
    {
        try {
            $user = User::findOrFail($id); // Find the user by ID
            $roles = Role::all(); // Get all roles for the dropdown
            $countries = Country::all();
            return view('admin.user.edit', compact('user', 'roles', 'countries')); // Return the edit view with the user and roles data
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $mailError = null;

        if ($request->filled('password')) {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8',
            ]);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $user->password = Hash::make($request->input('password'));
            $user->save();

            $emailTemplate = Email::where('name', 'admin-user-password-changed')->first();
            $placeholders = ['{name}', '{email}', '{password}'];
            $values = [$user->name, $user->email, $request->password];
        } else {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'approved' => 'nullable|boolean',
                'image_path' => 'nullable|string',
                'local_file_input' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'linkedin' => 'nullable|url|string|max:255',
                'short_description' => 'nullable|string|max:500',
                'long_description' => 'nullable|string',
                'experience' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date',
            ]);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            apply_profile_image_from_request($user, $request, 'local_file_input');

            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->approved = $request->boolean('approved');
            $user->mobile_number = $request->input('mobile_number');
            $user->gender = $request->input('gender');
            $user->date_of_birth = $request->input('date_of_birth');
            $user->nationality = $request->input('nationality');
            $user->address = $request->input('address');
            $user->post_code = $request->input('post_code');
            $user->city = $request->input('city');
            $user->country = $request->input('country');
            $user->short_description = $request->input('short_description');
            $user->long_description = $request->input('long_description');
            $user->experience = $request->input('experience');
            $user->linkedin = $request->input('linkedin');
            $user->education = json_encode($request->input('education', []));
            $user->save();

            $emailTemplate = Email::where('name', 'admin-user-update')->first();
            $placeholders = ['{name}', '{email}'];
            $values = [$user->name, $user->email];
        }

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

        $roleName = optional($user->roles()->first())->name ?? 'user';
        return redirect()->route('users', ['type' => user_list_type_param($roleName)])->with('success', $successMessage);
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            session()->flash('success', 'Record deleted successfully!');
            return redirect()->back();
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    public function emailVerification($id)
    {

        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            return redirect($this->redirectTo())->with('message', 'Email already verified.');
        }

        // Generate the new email verification link
        $verificationLink = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Fetch email template from database
        $emailTemplate = Email::where('name', 'email-verification')->first();

        if ($emailTemplate) {
            // Replace placeholders in the email body
            $emailBody = str_replace(
                ['{name}', '{email}', '{email-verification}'],
                [$user->name, $user->email, $verificationLink],
                $emailTemplate->body
            );

            $ccEmails = !empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
            $bccEmails = !empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

            Mail::to($user->email)
                ->cc($ccEmails)
                ->bcc($bccEmails)
                ->send(new UserMail($user, $emailTemplate->subject, $emailBody));
        }

        return back()->with('message', 'A new verification link has been generated. Please check your inbox or manually send it.');
    }

    public function status(Request $request)
    {
        $user = User::findOrFail($request->input('id'));

        $user->approved = $request->input('status');
        $user->save();

        $mailError = null;
        $emailTemplate = null;

        // Determine the email template based on status
        if ($user->approved == 0) {
            $emailTemplate = Email::where('name', 'user-suspend')->first();
        } elseif ($user->approved == 1) {
            $emailTemplate = Email::where('name', 'user-active')->first();
        }

        // Send email if template exists
        if ($emailTemplate) {
            try {
                $emailBody = str_replace(
                    ['{name}', '{email}'],
                    [$user->name, $user->email],
                    $emailTemplate->body
                );

                $ccEmails = !empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
                $bccEmails = !empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

                Mail::to($user->email)
                    ->cc($ccEmails)
                    ->bcc($bccEmails)
                    ->send(new UserMail($user, $emailTemplate->subject, $emailBody));
            } catch (\Exception $e) {
                $mailError = "Email sending failed: " . $e->getMessage();
            }
        }

        // Prepare response message
        $successMessage = 'User status updated successfully!';
        if ($mailError) {
            $successMessage .= ' However, there was an issue sending the email: ' . $mailError;
        }

        return response()->json(['success' => $successMessage], 200);
    }

    public function updateShowOnWeb(Request $request)
    {
        $user = User::findOrFail($request->input('id'));

        $user->is_on_web = $request->input('status');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_on_web
                ? 'User is now visible on website'
                : 'User hidden from website',
        ]);
    }
}