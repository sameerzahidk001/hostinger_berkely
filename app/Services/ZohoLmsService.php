<?php

namespace App\Services;

use App\Models\ClassSchedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ZohoLmsService
{
    public function isConfigured(): bool
    {
        return filled(config('zoho.client_id'))
            && filled(config('zoho.client_secret'))
            && filled(config('zoho.refresh_token'));
    }

    public function isMeetingReady(): bool
    {
        return $this->isConfigured();
    }

    public function isWorkDriveReady(): bool
    {
        return $this->isConfigured();
    }

    public function isCalendarReady(): bool
    {
        return $this->isConfigured();
    }

    public function attachIntegrations(ClassSchedule $schedule, ?\App\Models\MeetingAccount $account = null): array
    {
        return [
            'meeting' => $this->attachMeetingIfNeeded($schedule, $account),
            'calendar' => $this->attachCalendarIfNeeded($schedule, $account),
        ];
    }

    public function attachMeetingIfNeeded(ClassSchedule $schedule, ?\App\Models\MeetingAccount $account = null): string
    {
        if (filled($schedule->zoho_link)) {
            return 'existing';
        }

        if ($account) {
            if (! $account->isZoho() || ! $account->is_active || ! $account->hasRequiredCredentials()) {
                return 'not_configured';
            }
        } elseif (! $this->isMeetingReady()) {
            return 'not_configured';
        }

        try {
            $meeting = $this->createMeetingForSchedule($schedule, $account);
        } catch (Throwable $e) {
            Log::error('Zoho Meeting create threw', ['message' => $e->getMessage()]);
            return 'failed';
        }

        if (!$meeting || empty($meeting['join_link'])) {
            return 'failed';
        }

        $schedule->zoho_link = $meeting['join_link'];
        if ($account) {
            $schedule->meeting_account_id = $account->id;
        }
        $schedule->save();

        return 'created';
    }

    public function attachCalendarIfNeeded(ClassSchedule $schedule, ?\App\Models\MeetingAccount $account = null): string
    {
        if (!$this->calendarEventColumnExists()) {
            return 'skipped';
        }

        if (filled($schedule->zoho_calendar_event_uid)) {
            return 'existing';
        }

        if ($account) {
            if (! $account->isZoho() || ! $account->hasRequiredCredentials()) {
                return 'not_configured';
            }
        } elseif (!$this->isCalendarReady()) {
            return 'not_configured';
        }

        try {
            $event = $this->createCalendarEventForSchedule($schedule, $account);
        } catch (Throwable $e) {
            Log::error('Zoho Calendar create threw', ['message' => $e->getMessage()]);
            return 'failed';
        }

        if (!$event || empty($event['uid'])) {
            return 'failed';
        }

        $schedule->zoho_calendar_event_uid = $event['uid'];
        $schedule->save();

        return 'created';
    }

    public function createMeetingForSchedule(ClassSchedule $schedule, ?\App\Models\MeetingAccount $account = null): ?array
    {
        $schedule->loadMissing(['course', 'instructor', 'students']);
        $orgId = $this->orgId($account);
        $host = $this->resolveMeetingHost($account);
        $timezone = $this->timezone($account);
        if (!$orgId || !$host) {
            Log::warning('Zoho Meeting missing org or host presenter', [
                'expected_email' => $this->hostAccountEmail($account),
                'account_id' => $account?->id,
            ]);
            return null;
        }

        $start = $schedule->scheduled_at
            ->timezone($timezone)
            ->format('M j, Y h:i A');

        $participants = $schedule->students
            ->pluck('email')
            ->filter()
            ->unique()
            ->reject(fn ($email) => strcasecmp((string) $email, (string) $host['email']) === 0)
            ->map(fn ($email) => ['email' => $email])
            ->values()
            ->all();

        $response = $this->meetingClient($account)->post('/api/v2/' . $orgId . '/sessions.json', [
            'session' => [
                'topic' => $schedule->calendarTitle(),
                'agenda' => trim(($schedule->course->title ?? '') . "\n" . ($schedule->notes ?? '')),
                'presenter' => (int) $host['zuid'],
                'startTime' => $start,
                'duration' => $schedule->durationMinutes() * 60 * 1000,
                'timezone' => $timezone,
                'participants' => $participants,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Zoho Meeting create failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'host_email' => $host['email'],
                'account_id' => $account?->id,
            ]);
            return null;
        }

        $session = $response->json('session') ?? [];

        return [
            'join_link' => $session['joinLink'] ?? null,
            'start_link' => $session['startLink'] ?? null,
            'meeting_key' => $session['meetingKey'] ?? null,
            'host_email' => $host['email'],
        ];
    }

    public function createCalendarEventForSchedule(ClassSchedule $schedule, ?\App\Models\MeetingAccount $account = null): ?array
    {
        $schedule->loadMissing(['course', 'instructor', 'students']);
        $calendarUid = $this->calendarUid($account);
        if (!$calendarUid) {
            Log::warning('Zoho Calendar uid missing');
            return null;
        }

        $timezone = $this->timezone($account);
        $start = $schedule->scheduled_at?->copy()->timezone('UTC');
        $end = $schedule->endsAt()?->copy()->timezone('UTC');
        if (!$start || !$end) {
            return null;
        }

        $attendees = $schedule->students
            ->pluck('email')
            ->filter()
            ->unique()
            ->map(fn ($email) => [
                'email' => $email,
                'permission' => 1,
                'attendance' => 1,
            ])
            ->values()
            ->all();

        $eventdata = [
            'title' => $schedule->calendarTitle(),
            'dateandtime' => [
                'timezone' => $timezone,
                'start' => $start->format('Ymd\THis\Z'),
                'end' => $end->format('Ymd\THis\Z'),
            ],
            'isallday' => false,
            'description' => trim(
                ($schedule->course->title ?? '') . "\n" .
                ($schedule->instructor?->name ? 'Instructor: ' . $schedule->instructor->name : '') . "\n" .
                ($schedule->notes ?: '') . "\n" .
                ($schedule->zoho_link ? 'Join: ' . $schedule->zoho_link : '')
            ),
            'url' => $schedule->zoho_link ?: config('app.url'),
            'reminders' => $schedule->reminderList(),
            'notify_attendee' => $attendees ? 1 : 0,
            'conference' => 'zmeeting',
        ];

        if ($rrule = $schedule->zohoRrule()) {
            $eventdata['rrule'] = $rrule;
        }

        if ($attendees) {
            $eventdata['attendees'] = $attendees;
        }

        $url = rtrim(config('zoho.calendar_url'), '/') . '/calendars/' . rawurlencode($calendarUid) . '/events';
        $response = $this->http()
            ->withToken($this->accessToken($account), 'Zoho-oauthtoken')
            ->acceptJson()
            ->withQueryParameters([
                'eventdata' => json_encode($eventdata),
            ])
            ->post($url);

        if (!$response->successful()) {
            Log::error('Zoho Calendar create failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        }

        $event = $response->json('events.0') ?? [];

        return [
            'uid' => $event['uid'] ?? null,
            'id' => $event['id'] ?? null,
        ];
    }

    public function uploadToWorkDrive(UploadedFile $file, string $displayName = ''): ?array
    {
        if (!$this->isWorkDriveReady()) {
            return null;
        }

        $filename = $displayName !== '' ? $displayName : $file->getClientOriginalName();
        $folderId = $this->workDriveFolderId();
        if (!$folderId) {
            Log::error('Zoho WorkDrive folder id missing');
            return null;
        }

        $url = rtrim(config('zoho.workdrive_upload_url'), '/')
            . '?' . http_build_query([
                'filename' => $filename,
                'parent_id' => $folderId,
                'override-name-exist' => 'true',
            ]);

        try {
            $response = $this->http()
                ->timeout(120)
                ->withToken($this->accessToken(), 'Zoho-oauthtoken')
                ->accept('application/vnd.api+json')
                ->attach('content', file_get_contents($file->getRealPath()), $filename)
                ->post($url);
        } catch (Throwable $e) {
            Log::error('Zoho WorkDrive upload threw', ['message' => $e->getMessage()]);
            throw $e;
        }

        if (!$response->successful()) {
            Log::error('Zoho WorkDrive upload failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        }

        $row = $response->json('data.0.attributes') ?? $response->json('data.attributes') ?? [];
        $resourceId = $row['resource_id'] ?? data_get($response->json(), 'data.0.id');
        $permalink = $row['Permalink'] ?? $row['permalink'] ?? null;

        if ($resourceId) {
            $permalink = $this->createWorkDriveLink((string) $resourceId, $filename) ?: $permalink;
        }

        if (!$permalink && $resourceId) {
            $permalink = 'https://workdrive.zoho.com/file/' . $resourceId;
        }

        return [
            'id' => $resourceId,
            'url' => $permalink,
            'name' => $filename,
        ];
    }

    public function fetchRemoteFile(string $url): ?array
    {
        @set_time_limit(0);

        $cachePath = $this->remoteFileCachePath($url);
        if (is_file($cachePath) && filesize($cachePath) > 32) {
            return [
                'path' => $cachePath,
                'mime' => $this->sniffMime($cachePath, null),
                'delete_after' => false,
            ];
        }

        foreach ($this->remoteFileCandidates($url) as $candidate) {
            $saved = $this->downloadRemoteToPath($candidate, $cachePath);
            if ($saved) {
                return $saved;
            }
        }

        return null;
    }

    public function workDrivePublicFileInfo(string $url): ?array
    {
        $resourceId = $this->workDriveResourceIdFromUrl($url);
        if (!$resourceId) {
            $direct = $this->workDriveDirectDownloadUrl($url);
            return $direct ? ['download_url' => $direct] : null;
        }

        $cacheKey = 'wd_info_' . $resourceId;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached['download_url'])) {
            return $cached;
        }

        try {
            $html = (string) $this->http()
                ->timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->withOptions(['allow_redirects' => true])
                ->get('https://workdrive.zoho.com/embed/' . rawurlencode($resourceId))
                ->body();
        } catch (Throwable $e) {
            Log::warning('WorkDrive embed lookup failed', ['message' => $e->getMessage()]);
            return null;
        }

        $info = ['id' => $resourceId];
        if (preg_match('#downloadUrl\s*=\s*"([^"]+)"#', $html, $match)) {
            $info['download_url'] = html_entity_decode(stripcslashes($match[1]), ENT_QUOTES);
        }
        if (preg_match('#resourceExtn\s*=\s*"([^"]*)"#', $html, $match)) {
            $info['extn'] = strtolower($match[1]);
        }
        if (preg_match('#resourceTitleName\s*=\s*"([^"]*)"#', $html, $match)) {
            $info['name'] = stripcslashes($match[1]);
        }
        if (preg_match('#"size_in_bytes":"(\d+)"#', $html, $match)) {
            $info['size'] = (int) $match[1];
        }
        $info['thumbnail'] = 'https://previewengine.zohoexternal.com/thumbnail/WD/' . $resourceId . '?size=l';

        if (empty($info['download_url'])) {
            return null;
        }

        Cache::put($cacheKey, $info, now()->addMinutes(30));

        return $info;
    }

    public function proxyRemoteStream(string $url, ?string $range, array $headers, bool $asDownload): Response
    {
        @set_time_limit(0);

        try {
            $request = $this->http()
                ->timeout(600)
                ->withOptions([
                    'stream' => true,
                    'allow_redirects' => true,
                ]);
            if ($range && !$asDownload) {
                $request = $request->withHeaders(['Range' => $range]);
            }

            $remote = $request->get($url);
        } catch (Throwable $e) {
            Log::warning('Remote study stream threw', ['message' => $e->getMessage(), 'url' => $url]);
            return response('File stream failed.', 502);
        }

        $status = $remote->status();
        if ($status < 200 || $status >= 400) {
            return response('File stream failed.', 502);
        }

        $mime = strtolower(trim(explode(';', (string) $remote->header('Content-Type'))[0]));
        if ($mime === 'application/mp4') {
            $mime = 'video/mp4';
        }

        $headers['Content-Type'] = $asDownload
            ? 'application/octet-stream'
            : ($mime ?: ($headers['Content-Type'] ?? 'application/octet-stream'));
        $headers['Accept-Ranges'] = 'bytes';
        $headers['X-Accel-Buffering'] = 'no';
        if ($remote->header('Content-Length')) {
            $headers['Content-Length'] = $remote->header('Content-Length');
        }
        if ($remote->header('Content-Range')) {
            $headers['Content-Range'] = $remote->header('Content-Range');
        }

        $body = $remote->toPsrResponse()->getBody();

        return response()->stream(function () use ($body) {
            while (!$body->eof()) {
                echo $body->read(65536);
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, $status, $headers);
    }

    public function mimeFromExtension(?string $extn): ?string
    {
        $map = [
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            'm4v' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];

        $extn = strtolower((string) $extn);

        return $map[$extn] ?? null;
    }

    public function workDrivePreviewUrl(?string $url, bool $ensureShare = false): ?string
    {
        if (!is_string($url) || $url === '' || !preg_match('#workdrive\.zoho#i', $url)) {
            return null;
        }

        if ($ensureShare) {
            $share = $this->resolveWorkDriveShareUrl($url);
            if ($share) {
                return $share;
            }
        } else {
            $direct = $this->workDriveDirectDownloadUrl($url);
            if ($direct) {
                return $url;
            }
            $resourceId = $this->workDriveResourceIdFromUrl($url);
            $cached = $resourceId ? Cache::get('wd_share_' . $resourceId) : null;
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $resourceId = $this->workDriveResourceIdFromUrl($url);
        if ($resourceId) {
            return 'https://workdrive.zoho.com/embed/' . $resourceId;
        }

        return $url;
    }

    public function workDriveDirectDownloadUrl(?string $url): ?string
    {
        if (!is_string($url) || $url === '') {
            return null;
        }

        if (preg_match('#(https://workdrive\.zohoexternal\.com/external/[A-Za-z0-9_-]+)#i', $url, $match)) {
            return rtrim($match[1], '/') . '/download?directDownload=true';
        }

        return null;
    }

    public function workDriveResourceIdFromUrl(string $url): ?string
    {
        if (preg_match('#workdrive\.zoho(?:external)?\.com/(?:file|folder|embed)/([A-Za-z0-9_-]+)#i', $url, $match)) {
            return $match[1];
        }

        return null;
    }

    public function resolveWorkDriveShareUrl(string $url): ?string
    {
        if ($this->workDriveDirectDownloadUrl($url)) {
            return $url;
        }

        $resourceId = $this->workDriveResourceIdFromUrl($url);
        if (!$resourceId || !$this->isWorkDriveReady()) {
            return null;
        }

        $cacheKey = 'wd_share_' . $resourceId;
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $link = $this->createWorkDriveLink($resourceId, 'Student access');
        } catch (Throwable $e) {
            Log::warning('WorkDrive share for student access failed', ['message' => $e->getMessage()]);
            return null;
        }

        if ($link) {
            Cache::put($cacheKey, $link, now()->addDay());
            return $link;
        }

        return null;
    }

    protected function remoteFileCandidates(string $url): array
    {
        $candidates = [];
        $public = $this->workDrivePublicFileInfo($url);
        if (!empty($public['download_url'])) {
            $candidates[] = ['url' => $public['download_url'], 'auth' => false];
        }
        $share = $this->resolveWorkDriveShareUrl($url) ?: $url;
        $direct = $this->workDriveDirectDownloadUrl($share);
        if ($direct) {
            $candidates[] = ['url' => $direct, 'auth' => false];
        }

        $resourceId = $this->workDriveResourceIdFromUrl($url);
        if ($resourceId && $this->isWorkDriveReady()) {
            $candidates[] = [
                'url' => rtrim(config('zoho.workdrive_url'), '/') . '/download/' . rawurlencode($resourceId),
                'auth' => true,
            ];
        }

        if (!$direct) {
            $candidates[] = ['url' => $url, 'auth' => false];
        }

        return $candidates;
    }

    protected function downloadRemoteToPath(array $candidate, string $path): ?array
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $tmp = $path . '.part';
        try {
            $request = $this->http()
                ->timeout(600)
                ->withOptions([
                    'sink' => $tmp,
                    'allow_redirects' => true,
                ]);
            if (!empty($candidate['auth'])) {
                $request = $request->withToken($this->accessToken(), 'Zoho-oauthtoken');
            }

            $response = $request->get($candidate['url']);
            $headerMime = strtolower((string) $response->header('Content-Type'));
            if (
                !$response->successful()
                || !is_file($tmp)
                || filesize($tmp) < 8
                || str_contains($headerMime, 'text/html')
                || str_contains($headerMime, 'application/json')
                || str_contains($headerMime, 'application/vnd.api+json')
            ) {
                @unlink($tmp);
                return null;
            }

            $magic = (string) file_get_contents($tmp, false, null, 0, 16);
            if (str_starts_with(ltrim($magic), '<') || str_starts_with(ltrim($magic), '{')) {
                @unlink($tmp);
                return null;
            }

            if (is_file($path)) {
                @unlink($path);
            }
            rename($tmp, $path);

            return [
                'path' => $path,
                'mime' => $this->sniffMime($path, $headerMime),
                'delete_after' => false,
            ];
        } catch (Throwable $e) {
            @unlink($tmp);
            Log::warning('Remote study file fetch threw', [
                'message' => $e->getMessage(),
                'url' => $candidate['url'] ?? null,
            ]);
            return null;
        }
    }

    protected function remoteFileCachePath(string $url): string
    {
        return storage_path('app/study-cache/' . sha1($url) . '.bin');
    }

    protected function sniffMime(string $path, ?string $headerMime): string
    {
        $headerMime = strtolower(trim(explode(';', (string) $headerMime)[0]));
        $magic = (string) file_get_contents($path, false, null, 0, 8);
        if (str_starts_with($magic, '%PDF')) {
            return 'application/pdf';
        }
        if (strlen($magic) >= 8 && substr($magic, 4, 4) === 'ftyp') {
            return 'video/mp4';
        }
        if (str_starts_with($magic, "\x89PNG")) {
            return 'image/png';
        }
        if (str_starts_with($magic, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (in_array($headerMime, ['', 'application/octet-stream', 'binary/octet-stream'], true)) {
            $detected = @mime_content_type($path);
            return $detected ?: 'application/octet-stream';
        }

        return $headerMime ?: 'application/octet-stream';
    }

    public function connectionStatus(): array
    {
        $status = [
            'configured' => $this->isConfigured(),
            'account_email' => config('zoho.account_email'),
            'connected_email' => null,
            'org_id' => config('zoho.org_id'),
            'presenter_zuid' => config('zoho.presenter_zuid'),
            'workdrive_folder_id' => config('zoho.workdrive_folder_id'),
            'calendar_uid' => config('zoho.calendar_uid'),
            'meeting_user' => null,
            'error' => null,
        ];

        if (!$this->isConfigured()) {
            $status['error'] = 'Missing ZOHO_CLIENT_ID, ZOHO_CLIENT_SECRET, or ZOHO_REFRESH_TOKEN.';
            return $status;
        }

        try {
            $user = $this->currentUser();
            $status['meeting_user'] = $user;
            $status['org_id'] = $status['org_id'] ?: (string) ($user['zsoid'] ?? '');
            $status['presenter_zuid'] = $status['presenter_zuid'] ?: (string) ($user['zuid'] ?? '');
            $status['connected_email'] = $this->userEmail($user);
            $expected = strtolower((string) config('zoho.account_email', 'bdm@berkeleyme.com'));
            if ($status['connected_email'] && strcasecmp($status['connected_email'], $expected) !== 0) {
                $status['error'] = 'Zoho is connected as ' . $status['connected_email']
                    . '. Reconnect OAuth while logged in as ' . $expected
                    . ' so meeting links are hosted on that account.';
            }
        } catch (Throwable $e) {
            $status['error'] = $e->getMessage();
        }

        try {
            $status['workdrive_folder_id'] = $status['workdrive_folder_id'] ?: (string) ($this->workDriveFolderId() ?: '');
        } catch (Throwable $e) {
            Log::warning('Zoho WorkDrive folder lookup failed', ['message' => $e->getMessage()]);
        }

        try {
            $status['calendar_uid'] = $status['calendar_uid'] ?: (string) ($this->calendarUid() ?: '');
        } catch (Throwable $e) {
            Log::warning('Zoho Calendar lookup failed', ['message' => $e->getMessage()]);
        }

        return $status;
    }

    public function exchangeGrantCode(string $code): array
    {
        Cache::forget('zoho_lms_access_token');
        Cache::forget('zoho_workdrive_folder_id');
        Cache::forget('zoho_calendar_uid');

        $response = $this->http()->asForm()->post(rtrim(config('zoho.accounts_url'), '/') . '/oauth/v2/token', [
            'code' => $code,
            'client_id' => config('zoho.client_id'),
            'client_secret' => config('zoho.client_secret'),
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful() || empty($response->json('refresh_token'))) {
            throw new \RuntimeException('Zoho grant exchange failed: ' . $response->body());
        }

        return $response->json();
    }

    protected function createWorkDriveLink(string $resourceId, string $name): ?string
    {
        $response = $this->http()
            ->withToken($this->accessToken(), 'Zoho-oauthtoken')
            ->accept('application/vnd.api+json')
            ->asJson()
            ->post(rtrim(config('zoho.workdrive_url'), '/') . '/links', [
                'data' => [
                    'type' => 'links',
                    'attributes' => [
                        'resource_id' => $resourceId,
                        'link_name' => mb_substr($name, 0, 80),
                        'allow_download' => true,
                        'request_user_data' => false,
                        'role_id' => 34,
                    ],
                ],
            ]);

        if (!$response->successful()) {
            Log::warning('Zoho WorkDrive share link failed', ['body' => $response->body()]);
            return null;
        }

        return data_get($response->json(), 'data.attributes.link');
    }

    protected function workDriveFolderId(): ?string
    {
        if (filled(config('zoho.workdrive_folder_id'))) {
            return (string) config('zoho.workdrive_folder_id');
        }

        return Cache::remember('zoho_workdrive_folder_id', 3600, function () {
            $user = $this->http()
                ->withToken($this->accessToken(), 'Zoho-oauthtoken')
                ->accept('application/vnd.api+json')
                ->get(rtrim(config('zoho.workdrive_url'), '/') . '/users/me');

            $zuid = data_get($user->json(), 'data.id') ?: data_get($user->json(), 'data.attributes.zuid');
            if (!$zuid) {
                return null;
            }

            $space = $this->http()
                ->withToken($this->accessToken(), 'Zoho-oauthtoken')
                ->accept('application/vnd.api+json')
                ->get(rtrim(config('zoho.workdrive_url'), '/') . '/users/' . $zuid . '/privatespace');

            return data_get($space->json(), 'data.0.id')
                ?: data_get($space->json(), 'data.id');
        });
    }

    protected function calendarUid(?\App\Models\MeetingAccount $account = null): ?string
    {
        $fromAccount = $account?->credential('calendar_uid');
        if (filled($fromAccount)) {
            return (string) $fromAccount;
        }

        if (filled(config('zoho.calendar_uid'))) {
            return (string) config('zoho.calendar_uid');
        }

        $cacheKey = 'zoho_calendar_uid' . ($account ? ('_' . $account->id) : '');

        return Cache::remember($cacheKey, 3600, function () use ($account) {
            $response = $this->authedJson($account)->get(rtrim(config('zoho.calendar_url'), '/') . '/calendars', [
                'category' => 'own',
            ]);

            if (!$response->successful()) {
                Log::error('Zoho Calendar list failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $calendars = $response->json('calendars') ?? [];
            foreach ($calendars as $calendar) {
                if (!empty($calendar['isdefault']) && !empty($calendar['uid'])) {
                    return (string) $calendar['uid'];
                }
            }

            return !empty($calendars[0]['uid']) ? (string) $calendars[0]['uid'] : null;
        });
    }

    protected function orgId(?\App\Models\MeetingAccount $account = null): ?string
    {
        $fromAccount = $account?->credential('org_id');
        if (filled($fromAccount)) {
            return (string) $fromAccount;
        }

        if (filled(config('zoho.org_id'))) {
            return (string) config('zoho.org_id');
        }

        $user = $this->currentUser($account);

        return !empty($user['zsoid']) ? (string) $user['zsoid'] : null;
    }

    /**
     * Meetings must be hosted by the account email.
     * Presenter ZUID comes from account/config, else from the OAuth user — only if email matches.
     */
    protected function resolveMeetingHost(?\App\Models\MeetingAccount $account = null): ?array
    {
        $expected = strtolower(trim($this->hostAccountEmail($account)));
        $user = $this->currentUser($account);
        $email = $this->userEmail($user);
        $presenter = $account?->credential('presenter_zuid') ?: config('zoho.presenter_zuid');
        $zuid = filled($presenter)
            ? (string) $presenter
            : (string) ($user['zuid'] ?? '');

        if ($zuid === '' || $email === '') {
            return null;
        }

        if (strcasecmp($email, $expected) !== 0) {
            Log::error('Zoho Meeting host email mismatch', [
                'connected' => $email,
                'expected' => $expected,
                'account_id' => $account?->id,
            ]);

            return null;
        }

        return [
            'email' => $email,
            'zuid' => $zuid,
        ];
    }

    public function hostAccountEmail(?\App\Models\MeetingAccount $account = null): string
    {
        if ($account && filled($account->host_email)) {
            return (string) $account->host_email;
        }

        return (string) config('zoho.account_email', 'bdm@berkeleyme.com');
    }

    protected function timezone(?\App\Models\MeetingAccount $account = null): string
    {
        if ($account && filled($account->timezone)) {
            return (string) $account->timezone;
        }

        return (string) config('zoho.timezone', 'Asia/Dubai');
    }

    protected function userEmail(array $user): string
    {
        foreach (['email', 'primaryEmail', 'loginName', 'displayName'] as $key) {
            $value = trim((string) ($user[$key] ?? ''));
            if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return strtolower($value);
            }
        }

        return '';
    }

    protected function currentUserZuid(?\App\Models\MeetingAccount $account = null): ?string
    {
        $host = null;
        try {
            $host = $this->resolveMeetingHost($account);
        } catch (Throwable $e) {
            Log::warning('Zoho host resolve failed: ' . $e->getMessage());
        }

        return $host['zuid'] ?? null;
    }

    protected function currentUser(?\App\Models\MeetingAccount $account = null): array
    {
        $response = $this->meetingClient($account)->get('/api/v2/user.json');
        if (!$response->successful()) {
            throw new \RuntimeException('Zoho Meeting user lookup failed: ' . $response->body());
        }

        return $response->json('userDetails') ?? $response->json() ?? [];
    }

    protected function meetingClient(?\App\Models\MeetingAccount $account = null)
    {
        $base = $account?->credential('meeting_url') ?: config('zoho.meeting_url');

        return $this->authedJson($account)->baseUrl(rtrim((string) $base, '/'));
    }

    protected function authedJson(?\App\Models\MeetingAccount $account = null)
    {
        return $this->http()
            ->withToken($this->accessToken($account), 'Zoho-oauthtoken')
            ->acceptJson()
            ->asJson();
    }

    protected function accessToken(?\App\Models\MeetingAccount $account = null): string
    {
        $cacheKey = 'zoho_lms_access_token' . ($account ? ('_acct_' . $account->id) : '');

        return Cache::remember($cacheKey, 50 * 60, function () use ($account) {
            $clientId = $account?->credential('client_id') ?: config('zoho.client_id');
            $clientSecret = $account?->credential('client_secret') ?: config('zoho.client_secret');
            $refreshToken = $account?->credential('refresh_token') ?: config('zoho.refresh_token');
            $accountsUrl = $account?->credential('accounts_url') ?: config('zoho.accounts_url');

            $response = $this->http()->asForm()->post(rtrim((string) $accountsUrl, '/') . '/oauth/v2/token', [
                'refresh_token' => $refreshToken,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful() || empty($response->json('access_token'))) {
                Log::error('Zoho token refresh failed', [
                    'body' => $response->body(),
                    'account_id' => $account?->id,
                ]);
                throw new \RuntimeException('Zoho OAuth token refresh failed.');
            }

            return $response->json('access_token');
        });
    }

    protected function http()
    {
        $client = Http::timeout(30);
        $caBundle = base_path('.tools/php/extras/ssl/cacert.pem');
        if (is_file($caBundle)) {
            $client = $client->withOptions(['verify' => $caBundle]);
        }

        return $client;
    }

    protected function calendarEventColumnExists(): bool
    {
        return Schema::hasTable('class_schedules')
            && Schema::hasColumn('class_schedules', 'zoho_calendar_event_uid');
    }
}

