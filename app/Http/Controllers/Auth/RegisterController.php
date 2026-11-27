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
use App\Services\JournalRoleService;
use App\Support\JournalRegistrationOptions;
use App\Models\Country;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        $pathPrefix = normalize_path_auth_prefix((string) ($data['path_prefix'] ?? path_auth_prefix()));
        $allowedNames = $pathPrefix === 'journal'
            ? ['journal_author']
            : ['student', 'instructor'];

        if ($pathPrefix === 'journal') {
            JournalRoleService::ensureRoles();
        }

        $rules = [
            'role' => [
                'required',
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($allowedNames) {
                    $allowedRoles = Role::whereIn('name', $allowedNames)->pluck('id')->toArray();
                    if (! in_array((int) $value, array_map('intval', $allowedRoles), true)) {
                        $fail('The selected role is invalid.');
                    }
                }
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'mobile_number' => ['required', 'regex:/^\+?[1-9]\d{0,2}\d{6,14}$/'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ];

        if ($pathPrefix === 'journal') {
            $rules = array_merge($rules, [
                'country' => ['required', 'string', 'max:10'],
                'profession' => ['required', 'string', Rule::in(JournalRegistrationOptions::professions())],
                'specialization' => ['required', 'string', Rule::in(JournalRegistrationOptions::specializations())],
                'work_setting' => ['required', 'string', Rule::in(JournalRegistrationOptions::workSettings())],
                'alert_prefs' => ['nullable', 'array'],
                'alert_prefs.*' => ['string', Rule::in(array_keys(JournalRegistrationOptions::alertOptions()))],
                'marketing_prefs' => ['nullable', 'array'],
                'marketing_prefs.*' => ['string', Rule::in(array_keys(JournalRegistrationOptions::marketingOptions()))],
                'terms' => ['accepted'],
            ]);
        }

        return Validator::make($data, $rules);
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

        $pathPrefix = normalize_path_auth_prefix((string) ($request->input('path_prefix') ?? path_auth_prefix()));
        $country = trim((string) $request->input('country', ''));
        if ($country === '') {
            $country = (string) ($location_details['country'] ?? '');
        }

        $payload = [
            'name' => $request->name,
            'email' => $request->email,
            'mobile_number' => $request->mobile_number,
            'password' => Hash::make($request->password),
            'country' => $country,
            'approved' => 1,
            'ip_address' => $location_details['ip'] ?? $userIp,
        ];

        if ($pathPrefix === 'journal') {
            User::ensureJournalProfileColumn();
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'journal_profile')) {
                $payload['journal_profile'] = [
                    'profession' => (string) $request->input('profession'),
                    'specialization' => (string) $request->input('specialization'),
                    'work_setting' => (string) $request->input('work_setting'),
                    'alert_prefs' => array_values(array_unique(array_map('strval', (array) $request->input('alert_prefs', [])))),
                    'marketing_prefs' => array_values(array_unique(array_map('strval', (array) $request->input('marketing_prefs', [])))),
                ];
            }
        }

        $user = User::create($payload);

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

    public function register(Request $request, ?string $pathPrefix = null)
    {
        $pathPrefix = remember_path_auth_prefix($pathPrefix ?: $request->route('pathPrefix') ?: $request->input('path_prefix'));

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

        if (session()->has('intended_page_fee_package_id') || session('intended_package_type') === 'page') {
            $packageId = session()->pull('intended_page_fee_package_id') ?: session()->pull('intended_package_id');
            session()->forget('intended_package_type');
            if ($packageId) {
                app(\App\Services\PageFeeStructureService::class)->ensureCommerceColumns();
                if (\Illuminate\Support\Facades\Schema::hasColumn('cart_items', 'page_fee_package_id')) {
                    $cartItem = CartItem::firstOrCreate([
                        'user_id' => Auth::id(),
                        'page_fee_package_id' => $packageId,
                        'course_fee_package_id' => null,
                    ], [
                        'quantity' => 0,
                    ]);
                    $cartItem->increment('quantity');

                    return redirect()->route('cart.index')->with('success', 'Item automatically added to cart after login.');
                }
            }
        }

        if (session()->has('intended_package_id')) {
            $packageId = session()->pull('intended_package_id');
            session()->forget('intended_package_type');

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

        if ($pathPrefix === 'journal' && is_journal_user($user)) {
            return redirect()->route('journal.dashboard')
                ->with('status', 'Account created. Welcome to the Academic Journal.');
        }

        if ($pathPrefix !== '') {
            return redirect()->to(path_auth_home_url($pathPrefix))
                ->with('status', 'Account created. Welcome.');
        }

        return redirect()->route('verification.notice');
    }

    public function showRegistrationForm(?string $pathPrefix = null)
    {
        $pathPrefix = remember_path_auth_prefix($pathPrefix ?: request()->route('pathPrefix'));
        if ($pathPrefix === 'journal') {
            JournalRoleService::ensureRoles();
            $roles = Role::whereIn('name', ['journal_author'])->get();
        } else {
            $roles = Role::whereIn('name', ['student', 'instructor'])->get();
        }
        $authBrand = site_header_branding(null, $pathPrefix ?: null);

        $countries = collect();
        if ($pathPrefix === 'journal') {
            try {
                $countries = Country::query()->orderBy('name')->get(['iso_code', 'name']);
            } catch (\Throwable $e) {
                $countries = collect();
            }
        }

        return view('auth.register', [
            'roles' => $roles,
            'pathPrefix' => $pathPrefix,
            'authBrand' => $authBrand,
            'registerAction' => $pathPrefix !== '' ? path_register_url($pathPrefix) : route('register'),
            'loginUrl' => $pathPrefix !== '' ? path_login_url($pathPrefix) : route('login'),
            'homeUrl' => $pathPrefix !== '' ? path_auth_home_url($pathPrefix) : route('welcome'),
            'isJournalRegister' => $pathPrefix === 'journal',
            'countries' => $countries,
            'professions' => JournalRegistrationOptions::professions(),
            'specializations' => JournalRegistrationOptions::specializations(),
            'workSettings' => JournalRegistrationOptions::workSettings(),
            'alertOptions' => JournalRegistrationOptions::alertOptions(),
            'marketingOptions' => JournalRegistrationOptions::marketingOptions(),
        ]);
    }
}