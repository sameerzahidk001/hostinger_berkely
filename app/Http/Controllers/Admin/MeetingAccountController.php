<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeetingAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class MeetingAccountController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->check(), 403);
        MeetingAccount::ensureDefaultZohoFromEnv();

        $accounts = Schema::hasTable('meeting_accounts')
            ? MeetingAccount::query()->orderByDesc('is_default')->orderBy('provider')->orderBy('label')->get()
            : collect();

        return view('admin.meeting-accounts.index', compact('accounts'));
    }

    public function create(Request $request)
    {
        abort_unless(Auth::guard('admin')->check(), 403);
        $provider = $request->query('provider', MeetingAccount::PROVIDER_ZOHO);
        if (! in_array($provider, [MeetingAccount::PROVIDER_ZOHO, MeetingAccount::PROVIDER_ZOOM], true)) {
            $provider = MeetingAccount::PROVIDER_ZOHO;
        }

        return view('admin.meeting-accounts.form', [
            'account' => new MeetingAccount(['provider' => $provider, 'is_active' => true, 'timezone' => 'Asia/Dubai']),
            'provider' => $provider,
            'credentials' => [],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->check(), 403);
        $validator = Validator::make($request->all(), $this->rules($request->input('provider')));
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $account = new MeetingAccount();
        $this->fillAccount($account, $request);
        $account->save();

        if ($request->boolean('is_default')) {
            $account->makeDefault();
        }

        return redirect()
            ->route('admin.meeting-accounts.index')
            ->with('success', 'Meeting account saved.');
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->check(), 403);
        $account = MeetingAccount::findOrFail($id);

        return view('admin.meeting-accounts.form', [
            'account' => $account,
            'provider' => $account->provider,
            'credentials' => $account->credentials(),
        ]);
    }

    public function update(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->check(), 403);
        $account = MeetingAccount::findOrFail($id);
        $validator = Validator::make($request->all(), $this->rules($account->provider, true));
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $this->fillAccount($account, $request, true);
        $account->save();

        if ($request->boolean('is_default')) {
            $account->makeDefault();
        }

        return redirect()
            ->route('admin.meeting-accounts.index')
            ->with('success', 'Meeting account updated.');
    }

    public function destroy($id)
    {
        abort_unless(Auth::guard('admin')->check(), 403);
        $account = MeetingAccount::findOrFail($id);
        $account->delete();

        return redirect()
            ->route('admin.meeting-accounts.index')
            ->with('success', 'Meeting account deleted.');
    }

    protected function rules(string $provider, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'nullable' : 'required';

        $rules = [
            'label' => 'required|string|max:255',
            'host_email' => 'nullable|email|max:255',
            'timezone' => 'nullable|string|max:64',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'provider' => 'required|in:zoho,zoom',
        ];

        if ($provider === MeetingAccount::PROVIDER_ZOOM) {
            $rules['account_id'] = $required . '|string|max:255';
            $rules['client_id'] = $required . '|string|max:255';
            $rules['client_secret'] = $required . '|string|max:255';
        } else {
            $rules['client_id'] = $required . '|string|max:255';
            $rules['client_secret'] = $required . '|string|max:255';
            $rules['refresh_token'] = $required . '|string';
            $rules['org_id'] = 'nullable|string|max:255';
            $rules['presenter_zuid'] = 'nullable|string|max:255';
            $rules['calendar_uid'] = 'nullable|string|max:255';
            $rules['workdrive_folder_id'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    protected function fillAccount(MeetingAccount $account, Request $request, bool $isUpdate = false): void
    {
        $account->provider = $request->input('provider', $account->provider ?: MeetingAccount::PROVIDER_ZOHO);
        $account->label = trim((string) $request->input('label'));
        $account->host_email = $request->input('host_email') ?: null;
        $account->timezone = $request->input('timezone') ?: 'Asia/Dubai';
        $account->is_active = $request->boolean('is_active', true);
        if (! $isUpdate) {
            $account->is_default = $request->boolean('is_default');
        }

        $existing = $account->exists ? $account->credentials() : [];
        if ($account->provider === MeetingAccount::PROVIDER_ZOOM) {
            $creds = [
                'account_id' => $request->input('account_id') ?: ($existing['account_id'] ?? null),
                'client_id' => $request->input('client_id') ?: ($existing['client_id'] ?? null),
                'client_secret' => $request->input('client_secret') ?: ($existing['client_secret'] ?? null),
            ];
        } else {
            $creds = [
                'client_id' => $request->input('client_id') ?: ($existing['client_id'] ?? null),
                'client_secret' => $request->input('client_secret') ?: ($existing['client_secret'] ?? null),
                'refresh_token' => $request->input('refresh_token') ?: ($existing['refresh_token'] ?? null),
                'org_id' => $request->input('org_id') ?: ($existing['org_id'] ?? null),
                'presenter_zuid' => $request->input('presenter_zuid') ?: ($existing['presenter_zuid'] ?? null),
                'calendar_uid' => $request->input('calendar_uid') ?: ($existing['calendar_uid'] ?? null),
                'workdrive_folder_id' => $request->input('workdrive_folder_id') ?: ($existing['workdrive_folder_id'] ?? null),
                'accounts_url' => $existing['accounts_url'] ?? config('zoho.accounts_url'),
                'meeting_url' => $existing['meeting_url'] ?? config('zoho.meeting_url'),
            ];
        }

        $account->setCredentials($creds);
    }
}
