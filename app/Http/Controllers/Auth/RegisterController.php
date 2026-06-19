<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use GuzzleHttp\Client;
use App\Models\Email;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use App\Mail\UserMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/user';

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'role' => [
                'required',
                'exists:roles,id',
                function ($attribute, $value, $fail) {
                    $allowedRoles = Role::whereIn('name', ['student', 'instructor'])->pluck('id')->toArray();
                    if (!in_array($value, $allowedRoles)) {
                        $fail('The selected role is invalid.');
                    }
                }
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'mobile_number' => ['required', 'regex:/^\+?[1-9]\d{0,2}\d{6,14}$/'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);
    }

    protected function create(Request $request)
    {
        $userIp = $request->ip();
        $location_details = ['country' => '', 'ip' => $userIp];

        try {
            $client = new Client(['timeout' => 3]);
            $response = $client->get("https://ipinfo.io/{$userIp}/json");
            $location_details = json_decode($response->getBody(), true) ?: $location_details;
        } catch (\Throwable $e) {
            // Local/dev fallback when geolocation API is unavailable.
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile_number' => $request->mobile_number,
            'password' => Hash::make($request->password),
            'country' => $location_details['country'] ?? '',
            'approved' => 1,
            'ip_address' => $location_details['ip'] ?? $userIp,
        ]);

        $user->roles()->sync([$request->role]);

        $emailTemplate = Email::where('name', 'user-registration')->first();

        if ($emailTemplate) {
            // Replace placeholders in the email body
            $emailBody = str_replace(
                ['{name}', '{email}', '{password}', '{role}'],
                [$user->name, $user->email, $request->password, ucfirst($user->roles->first()->name)],
                $emailTemplate->body
            );

            $ccEmails = !empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
            $bccEmails = !empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

            Mail::to($user->email)
                ->cc($ccEmails)
                ->bcc($bccEmails)
                ->send(new UserMail($user, $emailTemplate->subject, $emailBody));
        }

        return $user;
    }

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();
        $user = $this->create($request);

        $roleName = $user->roles()->value('name');
        record_user_activity(
            'Registration',
            'New account registered as ' . ucfirst($roleName ?? 'user'),
            route('register'),
            activity_audience_for_role($roleName),
            $user->id,
            null,
            $request
        );

        Auth::login($user);

        if (session()->has('intended_package_id')) {
            $packageId = session()->pull('intended_package_id');

            // Only add if not already in cart
            $cartItem = CartItem::firstOrCreate([
                'user_id' => Auth::id(),
                'course_fee_package_id' => $packageId,
            ], [
                'quantity' => 0
            ]);

            $cartItem->increment('quantity');
            return redirect()->route('cart.index')->with('success', 'Item automatically added to cart after login.');
        }

        return redirect()->route('verification.notice');
    }

    public function showRegistrationForm()
    {
        $roles = Role::whereIn('name', ['student', 'instructor'])->get();
        return view('auth.register', compact('roles'));
    }
}